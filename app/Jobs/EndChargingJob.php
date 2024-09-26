<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Token;
use App\Services\MqttService;
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

    public function handle(MqttService $mqttService)
    {
        try {
            // Log at the start of the job
            Log::info("Job started for token {$this->tokenId} on port {$this->chargingPort}.");

            // Find the token
            $token = Token::find($this->tokenId);

            if (!$token) {
                Log::warning("Token not found: ID {$this->tokenId}. Unable to end charging session.");
                return;
            }

            if (!$token->used) {
                Log::warning("Token has not been used or charging is already complete: Token {$token->token}");
                return;
            }

            // Log before attempting MQTT connection
            Log::info("Attempting to connect to MQTT broker to stop charging on port {$this->chargingPort}.");

            // Send MQTT stop command to the charging port
            $mqttService->connect();

            // Send a consistent JSON-formatted message
            $message = json_encode(['action' => 'stop', 'duration' => null]);
            $mqttService->publish("charging/port{$this->chargingPort}", $message);
            $mqttService->disconnect();

            // Log successful completion of the charging session
            Log::info("Charging session for token {$token->token} on port {$this->chargingPort} completed. Relay turned off.");

        } catch (\Exception $e) {
            Log::error("Failed to send MQTT command to stop charging for port {$this->chargingPort}: " . $e->getMessage());
        }
    }
}
