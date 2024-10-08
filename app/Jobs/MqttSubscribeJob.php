<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\MqttSubscribeService;
use App\Services\MqttService;
use Illuminate\Support\Facades\Log;
use App\Events\ChargingStatus;

class MqttSubscribeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        // No constructor parameters required
    }

    public function handle(MqttSubscribeService $mqttService)
    {
        try {
            Log::info('(MqttSubscribeJob) MQTT Subscription started.');

            // Subscribe to topics for both ports
            $mqttService->subscribe("charging/port1/status", function ($message) {
                Log::info("(MqttSubscribeJob) Received MQTT message from port 1: " . $message);
                $this->handleMessage(1, $message);
            });

            $mqttService->subscribe("charging/port2/status", function ($message) {
                Log::info("(MqttSubscribeJob) Received MQTT message from port 2: " . $message);
                $this->handleMessage(2, $message);
            });

            // Start listening for messages
            $mqttService->startListening();

        } catch (\Exception $e) {
            Log::error('(MqttSubscribeJob) Failed to subscribe to MQTT topics: ' . $e->getMessage());
        }
    }

    protected function handleMessage($port, $message)
    {
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

    protected function handleStartCharging($port)
    {
        try {
            Log::info("(MqttSubscribeJob) Starting charging process for port {$port}");
            event(new ChargingStatus('charging_started', $port));
        } catch (\Exception $e) {
            Log::error("(MqttSubscribeJob) Error starting charging for port {$port}: " . $e->getMessage());
        }
    }
}
