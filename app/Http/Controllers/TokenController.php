<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Token;
use App\Models\ChargingSession;
use App\Models\Port;
use Illuminate\Http\Request;
use App\Services\MqttPublishService;
use App\Jobs\EndChargingJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class TokenController extends Controller
{
    protected $mqtt;

    public function __construct(MqttPublishService $mqtt)
    {
        $this->mqtt = $mqtt;
    }

    // Show registry page with the latest 10 tokens
    public function showRegistry()
    {
        $tokens = Token::latest()->take(10)->get();
        $charging_sessions = ChargingSession::all();

        return view('registry', compact('tokens', 'charging_sessions'));
    }

    public function generateToken(Request $request)
    {
        $request->validate([
            'expiry' => 'required|integer|min:1|max:1440',
            'duration' => 'required|integer|min:1|max:1440',
            'guest_name' => 'required|string|max:255',
            'room_no' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
        ]);

        // Generate a unique 5-digit token
        do {
            $token = mt_rand(10000, 99999);
        } while (Token::where('token', $token)->exists());

        $expiry = (int) $request->expiry;

        // Create the token entry in the database
        Token::create([
            'token' => $token,
            'expiry' => now()->addMinutes($expiry),
            'duration' => $request->duration,
            'used' => false,
            'guest_name' => $request->input('guest_name'),
            'room_no' => $request->input('room_no'),
            'phone' => $request->input('phone')
        ]);

        // Try to print the token
        try {
            $connector = new WindowsPrintConnector("ZJ-58");
            $printer = new Printer($connector);

            $header = "Hotel Tentrem Yogyakarta \n";
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text($header);
            $printer->setEmphasis(false);
            $printer->text("-----------------------------\n");
            $printer->text("Guest: " . $request->input('guest_name') . "\n");
            $printer->feed();
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize(2, 2);
            $printer->setEmphasis(true);
            $printer->text("TOKEN: " . $token . "\n");
            $printer->setEmphasis(false);
            $printer->setTextSize(1, 1);
            $printer->text("Expiry: " . now()->addMinutes($expiry)->format('Y-m-d H:i') . "\n");
            $printer->text("Duration: " . $request->duration . " minutes\n");
            $printer->feed(2);
            $printer->cut();
            $printer->close();

        } catch (\Exception $e) {
            Log::error("(controller) Failed to print receipt for token $token. Error: " . $e->getMessage());
            return redirect()->route('registry')->with('error', 'Failed to print receipt. Token generated successfully.');
        }

        return redirect()->route('registry')->with('success', "Token $token generated and printed successfully!");
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

        $this->resumeSessions();

        foreach ($ports as $port) {
            if ($port->status === 'running') {
                // $port->remaining_time = $this->calculateTimeAmount($port->end_time);
                $port->start_time;
                $port->end_time;
                now();
            }
        }

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
            return response()->json(['success' => false, 'message' => $mqttResponse['message']], 500);
        }

        return response()->json(['success' => true, 'duration' => $token->duration]);
    }

    public function startCharging(Request $request)
    {
        $token = Token::where('token', $request->token)->first();

        Log::info("(controller)startCharging method called");

        if (!$token || !$token->used) {
            return response()->json(['success' => false, 'message' => 'Invalid or unused token'], 400);
        }

        // Calculate start and end time
        $startTime = now();
        $endTime = (clone $startTime)->addMinutes($token->duration);

        // Insert a new charging session record
        ChargingSession::create([
            'token' => $request->token,
            'charging_port' => $request->port,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'guest_name' => $token->guest_name,
            'room_no' => $token->room_no,
            'phone' => $token->phone,
        ]);

        // Token::where('token')->update('start_time' -> $startTime);

        // Update port status and end time
        Port::where('id', $request->port)->update([
            'status' => 'running',
            'current_token' => $request->token,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        $timeAmount = $this->calculateTimeAmount($endTime);
        Log::info('(controller)Calculated timeAmount for EndChargingJob: ' . $timeAmount);

        $cacheKey = 'port_' . $request->port . '_job_dispatched';
        try {
            EndChargingJob::dispatch($token->id, $request->port)
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
