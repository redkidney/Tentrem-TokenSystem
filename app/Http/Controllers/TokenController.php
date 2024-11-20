<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

use App\Models\Token;
use App\Models\ChargingSession;
use App\Models\Port;
use App\Models\Voucher;
use App\DataTables\ChargingSessionsDataTable;

use App\Services\MqttPublishService;
use App\Jobs\EndChargingJob;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

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
        $charging_sessions = ChargingSession::all();

        return view('registry', compact('tokens', 'charging_sessions', 'vouchers'));
    }

    public function generateToken(Request $request)
    {
        $request->validate([
            'voucher_id' => 'required|exists:vouchers,id',
            'guest_name' => 'required|string|max:255',
            'room_no' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
        ]);

        // Retrieve selected voucher details
        $voucher = Voucher::find($request->voucher_id);

        // Generate a unique 5-digit token
        do {
            $token = mt_rand(10000, 99999);
        } while (Token::where('token', $token)->exists());

        // Create the token entry in the database
        $tokenData = Token::create([
            'token' => $token,
            'expiry' => now()->addMinutes($voucher->duration),
            'duration' => $voucher->duration,
            'used' => false,
            'guest_name' => $request->input('guest_name'),
            'room_no' => $request->input('room_no'),
            'phone' => $request->input('phone'),
            'voucher' => $voucher->id,
        ]);

        // Redirect with success message and token data
        return redirect()->route('registry')->with([
            'success' => "Token $token generated successfully using voucher: {$voucher->voucher_name}!",
            'tokenData' => [
                'token' => $tokenData->token,
                'guest_name' => $tokenData->guest_name,
                'room_no' => $tokenData->room_no,
                'phone' => $tokenData->phone,
                'expiry' => $tokenData->expiry->format('Y-m-d H:i'),
                'duration' => $tokenData->duration,
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
        $ports = Port::all();

        // Resume any running sessions as needed
        $this->resumeSessions();

        // Update each port's remaining time based on its status
        $ports->each(function ($port) {
            if ($port->status === 'paused') {
                // If the port is paused, use the pre-existing remaining time
                $port->remaining_time;
            } elseif ($port->status === 'running') {
                // If the port is running, calculate the remaining time
                $port->remaining_time = $this->calculateTimeAmount($port->end_time);

                // Optionally access additional data if needed
                $port->start_time;
                $port->end_time;
                now();
            }
        });

        return view('charging_ports', compact('ports'));
    }

    public function validateToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'port' => 'required|integer|in:1,2',
        ]);

        $token = Token::where('token', $request->token)->first();

        if (!$token || $token->used || $token->expiry < now()) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired token'], 400);
        }

        // Mark the token as used
        $token->update(['used' => true]);

        // Send MQTT command to start charging
        $mqttResponse = $this->sendMQTTCommand($request->port, 'start', $token->duration);
        if (!$mqttResponse['success']) {
            Log::info("(controller) MQTT start message sent successfully");
            return response()->json(['success' => false, 'message' => $mqttResponse['message']], 500);
        }

        return response()->json(['success' => true, 'duration' => $token->duration]);
    }

    public function startCharging(Request $request)
    {
        $port = Port::find($request->port);

        if (!$port || !in_array($port->status, ['idle', 'paused'])) {
            // Only allow starting if the port is 'idle' or 'paused'
            return response()->json(['success' => false, 'message' => 'Invalid or inactive port'], 400);
        }

        $token = Token::where('token', $request->token)->first();

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Invalid token'], 400);
        }

        // Retrieve voucher
        $voucher = Voucher::find($token->voucher);
        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Voucher not found for the token'], 400);
        }

        Log::info("(controller) startCharging method called");

        // Calculate start and end time
        $startTime = now();
        $endTime = (clone $startTime)->addMinutes($voucher->duration);

        // Insert a new charging session record
        ChargingSession::create([
            'token' => $token->token,
            'charging_port' => $request->port,
            'guest_name' => $token->guest_name,
            'room_no' => $token->room_no,
            'phone' => $token->phone,
            'voucher' => $voucher->id,
        ]);

        // Update port status to 'running' and set end time
        $port->update([
            'status' => 'running',
            'current_token' => $token->token,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        $timeAmount = $this->calculateTimeAmount($endTime);
        Log::info('(controller) Calculated timeAmount for EndChargingJob: ' . $timeAmount);

        $cacheKey = 'port_' . $request->port . '_job_dispatched';
        try {
            EndChargingJob::dispatch($token->token, $request->port)
                ->onQueue('high_priority')
                ->delay(now()->addSeconds($timeAmount));
            Cache::put($cacheKey, true, $timeAmount);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch EndChargingJob', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to start charging session'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Charging started']);
    }

    public function endCharging(Request $request, $port)
    {
        try {
            if (!in_array($port, [1, 2])) {
                return response()->json(['success' => false, 'message' => 'Invalid port'], 400);
            }

            $token = Token::where('used', true)->first();
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Session already stopped or invalid token'], 404);
            }

            // Update the charging session with end time
            $chargingSession = ChargingSession::where('token', $request->token)
                ->where('charging_port', $port)
                ->first();

            if ($chargingSession) {
                $chargingSession->update(['end_time' => now()]);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error("(controller) Failed to send MQTT command to end charging for port $port: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to stop charging'], 500);
        }
    }

    public function resumeSessions()
    {
        $ports = Port::where('status', 'running')->get();

        foreach ($ports as $port) {
            $remainingTime = $this->calculateTimeAmount($port->end_time);

            // Create a cache key for this port to track job dispatch
            $cacheKey = 'port_' . $port->id . '_job_dispatched';

            // Check if the job has already been dispatched for this port
            if ($remainingTime > 0) {
                if (!Cache::has($cacheKey)) {
                    try {
                        // Re-dispatch EndChargingJob with the remaining time
                        EndChargingJob::dispatch($port->current_token, $port->id)
                            ->onQueue('high_priority')
                            ->delay(now()->addSeconds($remainingTime));

                        // Store the dispatch status in the cache with expiration time equal to the remaining time
                        Cache::put($cacheKey, true, $remainingTime);
                    } catch (\Exception $e) {
                        Log::error('Failed to dispatch EndChargingJob for port ' . $port->id, ['error' => $e->getMessage()]);
                    }
                } else {
                    Log::info("Job for port {$port->id} is already dispatched.");
                }
            } else {
                // If the remaining time is 0 or less, reset the port to idle
                $port->update(['status' => 'idle', 'current_token' => null]);

                // Clear the cache since the job should no longer be dispatched
                Cache::forget($cacheKey);
            }
        }
    }

    public function pauseCharging(int $port)
    {
        $portInstance = Port::find($port);

        if ($portInstance && $portInstance->status === 'running') {
            // Calculate remaining time
            $remainingTime = $this->calculateTimeAmount($portInstance->end_time);

            // Update port status and save remaining time
            $portInstance->update([
                'status' => 'paused',
                'remaining_time' => $remainingTime,
            ]);

            Cache::forget('port_' . $port . '_job_dispatched');

            Log::info("Charging for port {$port} paused with {$remainingTime} seconds remaining.");

            return ['success' => true, 'remaining_time' => $remainingTime];
        }

        Log::warning("Failed to pause charging for port {$port}. Invalid port or not running.");
        return ['success' => false, 'message' => 'Invalid port or port not running'];
    }

    public function resumeCharging($portId)
    {
        $port = Port::where('id', $portId)->where('status', 'paused')->first();

        if ($port && $port->remaining_time > 0) {
            $remainingTime = $port->remaining_time;
            $newEndTime = now()->addSeconds($remainingTime);

            $port->update([
                'status' => 'running',
                'end_time' => $newEndTime,
                'remaining_time' => 0,
            ]);

            $cacheKey = 'port_' . $port->id . '_job_dispatched';

            if (!Cache::has($cacheKey)) {
                try {
                    // Log the current_token before dispatching the job
                    Log::info("Resuming charging for port {$port->id} with token {$port->current_token}");

                    EndChargingJob::dispatch($port->current_token, $port->id)
                        ->onQueue('high_priority')
                        ->delay(now()->addSeconds($remainingTime));

                    Cache::put($cacheKey, true, $remainingTime);
                    Log::info("Port {$port->id} resumed with remaining time {$remainingTime} seconds.");
                } catch (\Exception $e) {
                    Log::error('Failed to dispatch EndChargingJob for port ' . $port->id, ['error' => $e->getMessage()]);
                    return ['success' => false, 'message' => 'Failed to dispatch EndChargingJob'];
                }
            }

            return ['success' => true, 'remaining_time' => $remainingTime];
        }

        return ['success' => false, 'message' => 'Port not paused or no remaining time'];
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
            Log::error("(controller) Failed to send MQTT {$action} command for port {$port}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to communicate with charging port'];
        }
    }

    protected function calculateTimeAmount($endTime)
    {
        $now = now();
        Log::info("Current time (now()): {$now}");
        Log::info("End time (endTime): {$endTime}");

        // Ensure endTime is a Carbon instance
        if (!$endTime instanceof \Carbon\Carbon) {
            $endTime = \Carbon\Carbon::parse($endTime);
        }

        if ($endTime->greaterThan($now)) {
            return floor($now->diffInSeconds($endTime)); // Round down to avoid fractions
        } else {
            return 0; // If endTime is less than now, return 0
        }
    }

}
