<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\MqttService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\TokenController;
use Illuminate\Support\Facades\App; // Needed to resolve the controller

class MqttSubscribeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mqttService;

    public function __construct()
    {
        // No constructor parameters required
    }

    public function handle(MqttService $mqttService)
    {
        try {
            Log::info('MQTT Subscription started.');

            // Subscribe to topics for both ports to handle status messages
            $mqttService->connect();

            // Subscribe to the relevant ESP32 topics
            $mqttService->subscribe('charging/port1/status', function ($message) {
                $this->handleMessage(1, $message);
            });

            $mqttService->subscribe('charging/port2/status', function ($message) {
                $this->handleMessage(2, $message);
            });

            // Keep the MQTT connection alive and listening
            $mqttService->loop();

        } catch (\Exception $e) {
            Log::error('Failed to subscribe to MQTT topics: ' . $e->getMessage());
        }
    }

    // Function to handle incoming messages from both ports
    protected function handleMessage($port, $message)
    {
        Log::info("Received MQTT message from port {$port}: " . $message);

        // Decode the JSON message
        $data = json_decode($message, true);

        if (!$data || !isset($data['status'])) {
            Log::warning("Invalid message format received from port {$port}");
            return;
        }

        switch ($data['status']) {
            case 'charging':
                $this->handleStartCharging($port);
                break;

            case 'current_flowing':
                $this->handleCurrentFlowing($port);
                break;

            case 'stop':
                $this->handleStopCharging($port);
                break;

            default:
                Log::warning("Unknown status '{$data['status']}' received from port {$port}");
        }
    }

    // Handle starting the charging process
    protected function handleStartCharging($port)
    {
        Log::info("Charging status received for port {$port}.");

        // Resolve the TokenController and call the startCharging method
        $tokenController = App::make(TokenController::class);

        // Dummy token for now - Replace this with the actual token logic or payload
        $token = 'YOUR_TOKEN_FROM_ES32';

        // Create a fake request object to simulate calling the controller
        $request = new \Illuminate\Http\Request();
        $request->merge([
            'token' => $token,  // Replace this with the actual token from the payload
            'port' => $port,
        ]);

        // Call the startCharging method in the TokenController
        $response = $tokenController->startCharging($request);

        Log::info("Start charging process called for port {$port}. Response: " . json_encode($response));
    }
}
