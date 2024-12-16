<?php

namespace App\Http\Controllers;

use App\Models\ChargingSession;
use App\Models\Port;
use App\Models\Token;
use App\Models\Voucher;
use App\Events\ChargingStatus;
use App\Events\MonitorUpdate;
use App\Jobs\EndChargingJob;
use App\Jobs\PauseExpirationJob;
use App\Services\MqttPublishService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TokenController extends Controller
{
    protected $mqtt;

    public function __construct(MqttPublishService $mqtt)
    {
        $this->mqtt = $mqtt;
    }

    public function showRegistry()
    {
        $tokens = Token::latest()->take(10)->get();
        $vouchers = Voucher::all();
        return view('admin.registry', compact('tokens', 'vouchers'));
    }

    public function generateToken(Request $request)
    {
        $request->validate([
            'voucher_id' => 'required|exists:vouchers,id',
            'guest_name' => 'required|string|max:255',
        ]);

        $voucher = Voucher::find($request->voucher_id);

        // Generate a unique 5-digit token
        do {
            $newToken = mt_rand(10000, 99999);
        } while (Token::where('token', $newToken)->exists());

        $tokenData = Token::create([
            'token'          => $newToken,
            'expiry'         => now()->addDay(), // 24 hours
            'duration'       => $voucher->duration,
            'remaining_time' => $voucher->duration * 60,
            'used'           => false,
            'guest_name'     => $request->guest_name,
            'room_no'        => $request->room_no,
            'phone'          => $request->phone,
            'voucher'        => $voucher->id,
            'car_type'       => $request->car_type,
        ]);

        return redirect()->route('registry')->with([
            'success' => "Token {$newToken} generated successfully using voucher: {$voucher->voucher_name}!",
            'tokenData' => [
                'token'       => $tokenData->token,
                'guest_name'  => $tokenData->guest_name,
                'room_no'     => $tokenData->room_no,
                'phone'       => $tokenData->phone,
                'expiry'      => $tokenData->expiry->format('Y-m-d H:i'),
                'duration'    => $tokenData->duration,
                'price'       => number_format($voucher->price, 2),
                'voucher_id'  => $voucher->id,
            ],
        ]);
    }

    public function showCustomer($port)
    {
        if (!in_array($port, [1, 2])) {
            abort(404, 'Invalid port.');
        }

        return view("customer_port{$port}");
    }

    public function showBoth()
    {
        // Try to resume any running sessions
        $this->resumeSessions();

        // Get all ports and update their remaining times if they are running
        $ports = Port::all()->each(function ($port) {
            if ($port->status === 'running') {
                $port->remaining_time = $this->calculateTimeAmount($port->end_time);
            }
        });

        return view('charging_ports', compact('ports'));
    }

    public function validateToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'port'  => 'required|integer|in:1,2',
        ]);

        $token = Token::where('token', $request->token)->first();
        $port = Port::find($request->port);

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token is incorrect'], 400);
        }

        if ($token->expiry < now()) {
            return response()->json(['success' => false, 'message' => 'Token has expired'], 400);
        }

        if ($token->remaining_time <= 0) {
            return response()->json(['success' => false, 'message' => 'No charging time remaining'], 400);
        }

        if (!$port || ($port->status !== 'idle' && !$port->isPauseExpired())) {
            return response()->json(['success' => false, 'message' => 'Port unavailable'], 400);
        }

        // Command the charging hardware to start
        $mqttResponse = $this->sendMQTTCommand($request->port, 'start', $token->duration);
        if (!$mqttResponse['success']) {
            return response()->json(['success' => false, 'message' => $mqttResponse['message']], 500);
        }

        return response()->json([
            'success'        => true,
            'remaining_time' => $token->remaining_time,
            'is_resuming'    => $port->status === 'paused'
        ]);
    }

    public function startCharging(Request $request)
    {
        $port = Port::find($request->port);
        if (!$port || !in_array($port->status, ['idle', 'paused'])) {
            return response()->json(['success' => false, 'message' => 'Invalid or inactive port'], 400);
        }

        $token = Token::where('token', $request->token)->first();
        $voucher = Voucher::find($token->voucher);

        $startTime = now();

        // Check for an existing session for this token today
        $existingSession = ChargingSession::where('token', $token->token)
            ->where('guest_name', $token->guest_name)
            ->whereBetween('created_at', [
                $token->created_at->startOfDay(),
                $token->created_at->clone()->addDay()->endOfDay()
            ])
            ->first();

        if (!$existingSession) {
            ChargingSession::create([
                'token'            => $token->token,
                'charging_port'    => $request->port,
                'start_time'       => $startTime,
                'guest_name'       => $token->guest_name,
                'room_no'          => $token->room_no,
                'phone'            => $token->phone,
                'car_type'         => $token->car_type,
                'voucher_name'     => $voucher->voucher_name,
                'voucher_duration' => $voucher->duration,
                'voucher_price'    => $voucher->price,
                'port_history'     => [[
                    'port' => $request->port,
                    'start_time' => $startTime->toDateTimeString(),
                    'end_time' => null
                ]]
            ]);
        } else {
            // Add new port usage to history
            $portHistory = $existingSession->port_history ?? [];
            $portHistory[] = [
                'port' => $request->port,
                'start_time' => $startTime->toDateTimeString(),
                'end_time' => null
            ];
            
            $existingSession->update([
                'port_history' => $portHistory
            ]);
        }

        $timeAmount = $token->remaining_time;
        $endTime = (clone $startTime)->addSeconds($timeAmount);

        $port->update([
            'status'         => 'running',
            'current_token'  => $token->token,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'remaining_time' => $timeAmount
        ]);

        event(new MonitorUpdate('charging_started', $port->id, $token->token, $voucher->duration, $timeAmount));

        // Create a unique session ID so we can ensure the correct job ends this session
        $sessionId = Str::uuid()->toString();
        Cache::put("port_{$request->port}_session_id", $sessionId);

        $cacheKey = "port_{$request->port}_job_dispatched";
        try {
            EndChargingJob::dispatch($token->token, $request->port, $sessionId)
                ->onQueue('high_priority')
                ->delay($endTime);

            Cache::put($cacheKey, true, $timeAmount);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch EndChargingJob', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to start charging session'], 500);
        }

        $token->update(['used' => true]);

        return response()->json([
            'success'        => true,
            'message'        => 'Charging started',
            'remaining_time' => $timeAmount,
            'duration'       => $voucher->duration
        ]);
    }

    public function endCharging(Request $request, $port)
    {
        Log::info("(TokenController) endCharging called", [
            'port' => $port,
            'request_data' => $request->all()
        ]);

        try {
            if (!in_array($port, [1, 2])) {
                return response()->json(['success' => false, 'message' => 'Invalid port'], 400);
            }

            $portData = Port::find($port);
            if (!$portData || !$portData->current_token) {
                return response()->json(['success' => false, 'message' => 'No active session found'], 404);
            }

            Cache::forget("port_{$port}_job_dispatched");

            $token = Token::where('token', $portData->current_token)->first();
            if ($token) {
                if (!$portData->start_time) {
                    return response()->json(['success' => false, 'message' => 'Invalid port start time'], 400);
                }

                // For paused sessions, use the stored remaining_time directly
                if ($portData->status === 'paused') {
                    $token->remaining_time = max(0, $portData->remaining_time);
                } else {
                    // For running sessions, calculate based on start/end times
                    $startTime = Carbon::parse($portData->start_time);
                    $usedTime = (int)$startTime->diffInRealSeconds(now());
                    $oldRemaining = $token->remaining_time;
                    $token->remaining_time = max(0, $oldRemaining - $usedTime);
                }
                
                $token->save();

                $totalDurationSeconds = $token->duration * 60;
                $totalUsedSeconds = $totalDurationSeconds - $token->remaining_time;

                $session = ChargingSession::where('token', $token->token)
                    ->where('guest_name', $token->guest_name)
                    ->whereBetween('created_at', [
                        $token->created_at->startOfDay(),
                        $token->created_at->clone()->addDay()->endOfDay()
                    ])
                    ->first();

                if ($session) {
                    // Update the latest port history entry with end time
                    $portHistory = $session->port_history;
                    $lastIndex = count($portHistory) - 1;
                    if ($lastIndex >= 0) {
                        $portHistory[$lastIndex]['end_time'] = now()->toDateTimeString();
                    }

                    $session->update([
                        'end_time' => now(),
                        'used_time' => $totalUsedSeconds,
                        'port_history' => $portHistory
                    ]);
                }
            }

            $portData->update([
                'status' => 'idle',
                'current_token' => null,
                'remaining_time' => 0,
                'start_time' => null,
                'end_time' => null,
                'pause_expiry' => null
            ]);

            // Clear the session ID for this port now that the session ended
            Cache::forget("port_{$port}_session_id");

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error in endCharging:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to stop charging'], 500);
        }
    }

    public function cancelCharging(Request $request, $port)
    {
        try {
            if (!in_array($port, [1, 2])) {
                return response()->json(['success' => false, 'message' => 'Invalid port'], 400);
            }

            $portData = Port::where('id', $port)->first();
            if (!$portData || !$portData->current_token) {
                return response()->json(['success' => false, 'message' => 'No active session found'], 404);
            }

            // Send events before ending the charging
            event(new MonitorUpdate('charging_cancelled', $portData->id, '', 0, 0));
            event(new ChargingStatus('charging_cancelled', $portData->id, 0));

            // Stop hardware charging
            $this->sendMQTTCommand($port, 'stop');

            // Clear job-related cache
            Cache::forget("port_{$port}_job_dispatched");
            Cache::forget("port_{$port}_session_id");

            // End charging with the same request
            return $this->endCharging($request, $port);

        } catch (\Exception $e) {
            Log::error('Error canceling charging session', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred'], 500);
        }
    }

    public function resumeSessions()
    {
        $ports = Port::where('status', 'running')
            ->orWhere('status', 'paused')
            ->get();

        foreach ($ports as $port) {
            if ($port->status === 'paused') {
                if ($port->pause_expiry <= 0) {
                    $port->update([
                        'status' => 'idle',
                        'current_token' => null,
                        'remaining_time' => 0,
                        'pause_expiry' => null
                    ]);
                    
                    event(new ChargingStatus('pause_expired', $port->id));
                    continue;
                }

                if ($port->current_token) {
                    $token = Token::where('token', $port->current_token)->first();
                    if ($token) {
                        if ($port->remaining_time !== $token->remaining_time) {
                            $port->update(['remaining_time' => $token->remaining_time]);
                        }

                        event(new MonitorUpdate(
                            'charging_paused',
                            $port->id,
                            $port->current_token,
                            $token->duration,
                            $token->remaining_time
                        ));

                        event(new ChargingStatus(
                            'charging_paused',
                            $port->id,
                            $token->remaining_time,
                            $port->pause_expiry
                        ));
                    }
                }
                continue;
            }

            // Handle running ports
            if ($port->status === 'running') {
                $remainingTime = $this->calculateTimeAmount($port->end_time);
                $cacheKey = "port_{$port->id}_job_dispatched";

                if ($remainingTime <= 0) {
                    $port->update([
                        'status' => 'idle',
                        'current_token' => null,
                        'remaining_time' => 0,
                        'start_time' => null,
                        'end_time' => null
                    ]);
                    Cache::forget($cacheKey);
                    Cache::forget("port_{$port->id}_session_id");
                    continue;
                }

                if ($port->current_token) {
                    $token = Token::where('token', $port->current_token)->first();
                    if ($token) {
                        $token->update(['remaining_time' => $remainingTime]);
                    }
                }

                if (!Cache::has($cacheKey)) {
                    $sessionId = Cache::get("port_{$port->id}_session_id");
                    if (!$sessionId) {
                        $sessionId = Str::uuid()->toString();
                        Cache::put("port_{$port->id}_session_id", $sessionId);
                    }

                    try {
                        EndChargingJob::dispatch($port->current_token, $port->id, $sessionId)
                            ->onQueue('high_priority')
                            ->delay(now()->addSeconds($remainingTime));

                        Cache::put($cacheKey, true, $remainingTime);
                        Log::info("Resumed session for port {$port->id} with session ID {$sessionId} and remaining time {$remainingTime} seconds.");
                    } catch (\Exception $e) {
                        Log::error("Failed to dispatch EndChargingJob for port {$port->id}", [
                            'error' => $e->getMessage(),
                            'remaining_time' => $remainingTime
                        ]);
                    }
                }

                event(new MonitorUpdate(
                    'charging_started',
                    $port->id,
                    $port->current_token,
                    $token->duration ?? 0,
                    $remainingTime
                ));

                event(new ChargingStatus(
                    'charging_started',
                    $port->id,
                    $remainingTime
                ));
            }
        }
    }

    public function pauseCharging(int $port)
    {
        Log::info("(controller)pauseCharging method called for port {$port}");

        $portInstance = Port::find($port);

        if ($portInstance && $portInstance->status === 'running') {
            $remainingTime = $this->calculateTimeAmount($portInstance->end_time);
            $pauseExpiry = 1 * 60;
            
            $portInstance->update([
                'status' => 'paused',
                'remaining_time' => $remainingTime,
                'pause_expiry' => $pauseExpiry
            ]);

            if ($portInstance->current_token) {
                Token::where('token', $portInstance->current_token)
                    ->update(['remaining_time' => $remainingTime]);
            }

            event(new MonitorUpdate('charging_paused', $portInstance->id, $portInstance->current_token, $portInstance->duration, $remainingTime));
            
            Cache::forget("port_{$port}_job_dispatched");
            Cache::forget("port_{$port}_session_id");

            PauseExpirationJob::dispatch($port)->delay(now()->addSeconds($pauseExpiry));

            return [
                'success' => true,
                'remaining_time' => $remainingTime,
                'pause_expiry' => $pauseExpiry
            ];
        }

        return ['success' => false, 'message' => 'Invalid port or port not running'];
    }

    public function resumeCharging($portId)
    {
        Log::info("(controller)resumeCharging method called for port {$portId}");

        $port = Port::where('id', $portId)->where('status', 'paused')->first();
        if ($port && $port->remaining_time > 0) {
            $remainingTime = $port->remaining_time;
            $newEndTime = now()->addSeconds($remainingTime);

            $token = Token::where('token', $port->current_token)->first();
            $duration = $token ? $token->duration : 0;

            // First update the port
            $port->update([
                'status'         => 'running',
                'end_time'       => $newEndTime,
                'remaining_time' => 0,  // Clear port's remaining_time as it's now running
                'pause_expiry'   => null // Clear pause expiry
            ]);

            // Then update the token's remaining time to match
            if ($port->current_token) {
                Token::where('token', $port->current_token)
                    ->update(['remaining_time' => $remainingTime]);
            }

            event(new MonitorUpdate('charging_resumed', $port->id, $port->current_token, $duration, $remainingTime));

            $cacheKey = "port_{$port->id}_job_dispatched";

            // Generate a new session ID
            $sessionId = Str::uuid()->toString();
            Cache::put("port_{$port->id}_session_id", $sessionId);

            if (!Cache::has($cacheKey)) {
                try {
                    Log::info("Resuming charging for port {$port->id} with token {$port->current_token}");

                    EndChargingJob::dispatch($port->current_token, $port->id, $sessionId)
                        ->onQueue('high_priority')
                        ->delay(now()->addSeconds($remainingTime));

                    Cache::put($cacheKey, true, $remainingTime);
                    Log::info("Port {$port->id} resumed with remaining time {$remainingTime} seconds and session ID {$sessionId}.");

                    return ['success' => true, 'remaining_time' => $remainingTime];
                } catch (\Exception $e) {
                    Log::error("Failed to dispatch EndChargingJob for port {$port->id}", ['error' => $e->getMessage()]);
                    return ['success' => false, 'message' => 'Failed to dispatch EndChargingJob'];
                }
            }

            return ['success' => true, 'remaining_time' => $remainingTime];
        }

        return ['success' => false, 'message' => 'Port not paused or no remaining time'];
    }

    public function showMonitor()
    {
        $ports = Port::all()->map(function ($port) {
            $token = Token::where('token', $port->current_token)->first();
            $duration = $token ? $token->duration : 0;

            // Calculate initial state data
            if ($port->status === 'running') {
                $remainingTime = $this->calculateTimeAmount($port->end_time);
                $port->remaining_time = $remainingTime;
                $port->duration = $duration; // Add duration to match event format
                $port->event_type = 'charging_started'; // Add event type to match format
            } else if ($port->status === 'paused') {
                if ($port->pause_expiry === null) {
                    $port->pause_expiry = 0;
                }
                $port->duration = $duration; // Add duration to match event format
                $port->event_type = 'charging_paused'; // Add event type to match format
            } else {
                $port->event_type = 'idle'; // For consistency
                $port->duration = 0;
                $port->remaining_time = 0;
            }

            // Add other fields that might be needed by the view
            $port->token = $port->current_token;
            
            return $port;
        });

        return view('admin.monitor', compact('ports'));
    }

    public function getCurrent($port)
    {
        $cacheKey = "port_{$port}_current";
        return response()->json(['current' => Cache::get($cacheKey, 0.0)]);
    }

    protected function sendMQTTCommand($port, $action, $duration = null)
    {
        try {
            $this->mqtt->connect();
            $topic = "charging/port{$port}";
            $message = json_encode(['action' => $action, 'duration' => $duration]);

            $this->mqtt->publish($topic, $message);
            $this->mqtt->disconnect();

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error("Failed to send MQTT {$action} command for port {$port}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to communicate with charging port'];
        }
    }

    protected function calculateTimeAmount($endTime)
    {
        $now = now();
        if (!$endTime instanceof Carbon) {
            $endTime = Carbon::parse($endTime);
        }

        return $endTime->greaterThan($now) ? floor($now->diffInSeconds($endTime)) : 0;
    }
}
