<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\MqttService;
use Illuminate\Support\Facades\Log;
use App\Events\ChargingStatus;

class MqttSubscribeJobPort2 implements ShouldQueue
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
            Log::info('(MqttSubscribeJobPort2) MQTT Subscription started.');

            // Connect to MQTT service
            $mqttService->connect();

            // Subscribe to port 2's topic
            $this->subscribeToPort($mqttService, 2);

            // Keep the MQTT connection alive and listening
            $mqttService->loop();
        } catch (\Exception $e) {
            Log::error('(MqttSubscribeJobPort2) Failed to subscribe to MQTT topics: ' . $e->getMessage());
        }
    }

    // Subscribe to a specific port
    protected function subscribeToPort(MqttService $mqttService, $port)
    {
        Log::info("(MqttSubscribeJobPort2) Subscribing to port $port");
        $mqttService->subscribe("charging/port{$port}/status", function ($message) use ($port) {
            $this->handleMessage($port, $message);
        });
    }

    // Handle incoming messages from ports
    protected function handleMessage($port, $message)
    {
        Log::info("(MqttSubscribeJobPort2) Received MQTT message from port {$port}: " . $message);

        $data = json_decode($message, true);

        if (!$data || !isset($data['status'])) {
            Log::warning("Invalid message format received from port {$port}");
            return;
        }

        switch ($data['status']) {
            case 'charging':
                $this->handleStartCharging($port);
                break;
            default:
                Log::warning("Unknown status '{$data['status']}' received from port {$port}");
        }
    }

    // Handle the start charging process
    protected function handleStartCharging($port)
    {
        try {
            Log::info("(MqttSubscribeJobPort2) Starting charging process for port {$port}");
            
            // Dispatch the ChargingStatus event
            event(new ChargingStatus('charging_started', $port));
            
        } catch (\Exception $e) {
            Log::error("(MqttSubscribeJobPort2) Error starting charging for port {$port}: " . $e->getMessage());
        }
    }
}