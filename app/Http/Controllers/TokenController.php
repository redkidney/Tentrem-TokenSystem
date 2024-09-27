<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Token;
use App\Models\ChargingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\MqttService;
use App\Jobs\EndChargingJob;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TokenController extends Controller
{
    protected $mqtt;

    public function __construct(MqttService $mqtt)
    {
        $this->mqtt = $mqtt;
    }

    // Show registry page with the latest 10 tokens
    public function showRegistry()
    {
        $user = Auth::user();

        $tokens = Token::latest()->take(10)->get();
        return view('registry', compact('tokens'));
    }

    // Generate a new token
    public function generateToken(Request $request)
    {
        $request->validate([
            'expiry' => 'required|integer|min:1|max:1440', // Expiry in minutes
            'duration' => 'required|integer|min:1|max:1440', // Charging duration in minutes
            'guest_name' => 'required|string|max:255',
            'room_no' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
        ]);

        // Generate a random 5-digit token (number)
        $token = mt_rand(10000, 99999);

        // Create the token entry in the database
        Token::create([
            'token' => $token,
            'expiry' => now()->addMinutes($request->expiry),
            'duration' => $request->duration,
            'used' => false,
            'guest_name' => $request->input('guest_name'),
            'room_no' => $request->input('room_no'),
            'phone' => $request->input('phone')
        ]);

        return redirect()->route('registry')->with('success', "Token $token generated successfully!");
    }


    // Show customer form for Port 1
    public function showCustomer($port)
    {
        // Validate the port to ensure it is either 1 or 2
        if (!in_array($port, [1, 2])) {
            abort(404, 'Invalid port.');
        }

        // Dynamically load the correct view based on the port
        return view("customer_port{$port}");
    }

    public function validateToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'port' => 'required|integer|in:1,2', // Validate the port input
        ]);

        // Fetch the token
        $token = Token::where('token', $request->token)->first();

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Invalid Token'], 400);
        }

        if ($token->used) {
            return response()->json(['success' => false, 'message' => 'Token already used'], 400);
        }

        if ($token->expiry < now()) {
            return response()->json(['success' => false, 'message' => 'Token expired'], 400);
        }

        // Mark the token as used, but don't set the start time yet
        $token->update(['used' => true]);

        // Return success and allow the user to proceed
        return response()->json(['success' => true, 'duration' => $token->duration]);
    }

    // Start charging when the user presses "start"
    public function startCharging(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'port' => 'required|integer|in:1,2', // Validate the port
        ]);

        // Fetch the token
        $token = Token::where('token', $request->token)->first();

        if (!$token || !$token->used) {
            return response()->json(['success' => false, 'message' => 'Invalid or unused token'], 400);
        }

        // Set start time for charging
        $startTime = now();
        $token->update(['start_time' => $startTime]);

        // Insert a new charging session record using the model, including guest details
        ChargingSession::create([
            'token' => $request->token,
            'charging_port' => $request->port,
            'start_time' => $startTime,
            'guest_name' => $token->guest_name, // Adding guest name from token
            'room_no' => $token->room_no,       // Adding room number from token
            'phone' => $token->phone,           // Adding phone number from token
        ]);

        // Dispatch the job to stop charging after the duration
        try {
            EndChargingJob::dispatch($token->id, $request->port)->delay($startTime->addMinutes($token->duration));
            Log::info("Dispatched EndChargingJob for token {$token->id} and port {$request->port}");
        } catch (\Exception $e) {
            Log::error("Failed to dispatch EndChargingJob for token {$token->id} and port {$request->port}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to start charging session'], 500);
        }

        // Send MQTT command to start charging for the specified port
        $mqttResponse = $this->sendMQTTCommand($request->port, 'start', $token->duration);
        if ($mqttResponse['success']) {
            return response()->json(['success' => true, 'message' => 'Charging started']);
        } else {
            return response()->json(['success' => false, 'message' => $mqttResponse['message']], 500);
        }
    }


    // End charging when the session ends or user cancels
    public function endCharging(Request $request, $port)
    {
        try {
            // Validate the port number
            if (!in_array($port, [1, 2])) {
                return response()->json(['success' => false, 'message' => 'Invalid port'], 400);
            }

            // Log to ensure we're receiving the right port
            Log::info("Attempting to stop charging for port: $port");

            // Find the first active token that is currently in use
            $token = Token::where('used', true)->first();

            if (!$token) {
                Log::warning("No active token found for stopping charging.");
                return response()->json(['success' => false, 'message' => 'Session already stopped or invalid token.'], 404);
            }

            // Update the charging_sessions record with the end time using the model
            $chargingSession = ChargingSession::where('token', $request->token)  // Use 'token' field
                              ->where('charging_port', $port)
                              ->first();
            Log::info('Charging Session found: ', ['session' => $chargingSession]);

            if ($chargingSession) {
                $chargingSession->update(attributes: [
                    'end_time' => now(),
                ]);
                Log::info("Charging session for token {$token->id} on port {$port} has ended.");
            } else {
                Log::warning("No charging session found for token {$token->id} and port {$port}.");
            }

            // Send MQTT stop message for the specified port
            // $this->sendMQTTCommand($port, 'stop');

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error("Failed to send MQTT command to end charging for port $port: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to stop charging'], 500);
        }
    }

    protected function sendMQTTCommand($port, $action, $duration = null)
    {
        try {
            $this->mqtt->connect();

            // Publish the appropriate message (start or stop) for the port
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
}
