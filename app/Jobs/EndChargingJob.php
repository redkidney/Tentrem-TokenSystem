<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Token;
use App\Models\Port;
use App\Services\MqttPublishService;
use Illuminate\Support\Facades\Log;

class EndChargingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $token;
    protected $chargingPort;

    public function __construct($token, $chargingPort)
    {
        $this->token = $token;
        $this->chargingPort = $chargingPort;
    }

    public function handle(MqttPublishService $mqttService)
    {
        try {
            Log::info("(EndChargingJob) Job started for token {$this->token} on port {$this->chargingPort}.");

            // Locate the port and check its status and current token
            $port = Port::find($this->chargingPort);

            if ($port && $port->status === 'running' && $port->current_token === $this->token) {
                Log::info("(EndChargingJob) Attempting to connect to MQTT broker to stop charging on port {$this->chargingPort}.");

                // Send MQTT stop command to the charging port
                $mqttService->connect();
                $message = json_encode(['action' => 'stop', 'duration' => null]);
                $mqttService->publish("charging/port{$this->chargingPort}", $message);

                // Update the port status to 'idle' and clear token data
                $port->update([
                    'status' => 'idle',
                    'current_token' => null,
                    'remaining_time' => 0
                ]);

                Log::info("(EndChargingJob) Charging session for token {$this->token} on port {$this->chargingPort} completed. Port set to idle and token cleared.");
            } else {
                Log::warning("(EndChargingJob) Port {$this->chargingPort} not found, not running, or token mismatch.");
            }

        } catch (\Exception $e) {
            Log::error("(EndChargingJob) Failed to send MQTT command to stop charging for port {$this->chargingPort}: " . $e->getMessage());
        }
    }
}
