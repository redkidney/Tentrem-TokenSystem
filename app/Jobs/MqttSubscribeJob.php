<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\MqttSubscribeService;
use App\Services\MqttService;
use App\Http\Controllers\TokenController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Events\ChargingStatus;
use App\Events\CurrentUpdate;

class MqttSubscribeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tokenController;

    public function __construct()
    {
    
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

            // Add current monitoring subscriptions
            $mqttService->subscribe("charging/port1/current", function ($message) {
                $this->handleCurrentUpdate(1, $message);
            });

            $mqttService->subscribe("charging/port2/current", function ($message) {
                $this->handleCurrentUpdate(2, $message);
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

            case 'paused':
                $this->handlePauseCharging($port);
                break;
            
            case 'resumed':
                $this->handleResumeCharging($port);
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

    protected function handlePauseCharging($port)
    {
        $result = app(TokenController::class)->pauseCharging($port);

        if ($result['success']) {
            Log::info("Successfully paused charging for port {$port} via controller.");
            event(new ChargingStatus('charging_paused', $port, $result['remaining_time'], $result['pause_expiry']));
        } else {
            Log::warning("Failed to pause charging for port {$port}: " . $result['message']);
        }
    }

    protected function handleResumeCharging($port)
    {
        $result = app(TokenController::class)->resumeCharging($port);

        if ($result['success']) {
            event(new ChargingStatus('charging_resumed', $port, $result['remaining_time']));
            Log::info("Frontend resume event triggered for port {$port} with remaining time {$result['remaining_time']} seconds.");
        } else {
            Log::warning("Failed to resume charging for port {$port}: " . $result['message']);
        }
    }

    protected function handleCurrentUpdate($port, $message)
    {
        try {
            $data = json_decode($message, true);
            if (isset($data['current'])) {
                $current = $data['current'];
                Cache::put("port_{$port}_current", $current, now()->addMinutes(5));
                event(new CurrentUpdate($port, $current));
            }
        } catch (\Exception $e) {
            Log::error("Error handling current update for port {$port}: " . $e->getMessage());
        }
    }

}
