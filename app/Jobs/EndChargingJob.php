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

    protected $tokenId;
    protected $chargingPort;

    public function __construct($tokenId, $chargingPort)
    {
        $this->tokenId = $tokenId;
        $this->chargingPort = $chargingPort;
    }

    public function handle(MqttPublishService $mqttService)
    {
        try {
            // Log at the start of the job
            Log::info("(EndChargingJob) Job started for token {$this->tokenId} on port {$this->chargingPort}.");

            // Find the token
            $token = Token::find($this->tokenId);

            if (!$token) {
                Log::warning("(EndChargingJob) Token not found: ID {$this->tokenId}. Unable to end charging session.");
                return;
            }

            if (!$token->used) {
                Log::warning("(EndChargingJob) Token has not been used or charging is already complete: Token {$token->token}");
                return;
            }

            // Log before attempting MQTT connection
            Log::info("(EndChargingJob) Attempting to connect to MQTT broker to stop charging on port {$this->chargingPort}.");

            // Send MQTT stop command to the charging port
            $mqttService->connect();
            $message = json_encode(['action' => 'stop', 'duration' => null]);
            $mqttService->publish("charging/port{$this->chargingPort}", $message);
            // Optionally disconnect
            // $mqttService->disconnect();

            // Log successful completion of the charging session
            Log::info("(EndChargingJob) Charging session for token {$token->token} on port {$this->chargingPort} completed. Relay turned off.");

            // Now update the port status to 'idle'
            $port = Port::find($this->chargingPort);

            if ($port && $port->status === 'running') {
                // Reset port details
                $port->update([
                    'status' => 'idle',
                    'current_token' => null,
                    'remaining_time' => 0
                ]);

                Log::info("(EndChargingJob) Port {$this->chargingPort} status set to idle, token cleared, and remaining time reset.");
            } else {
                Log::warning("(EndChargingJob) Port {$this->chargingPort} not found or not in running state.");
            }

        } catch (\Exception $e) {
            Log::error("(EndChargingJob) Failed to send MQTT command to stop charging for port {$this->chargingPort}: " . $e->getMessage());
        }
    }
}
