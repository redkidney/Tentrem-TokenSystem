<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Token;
use Illuminate\Support\Facades\Log;
use App\Services\MqttService;  // Import the MqttService

class HandleExpiredTokens extends Command
{
    protected $signature = 'tokens:expire';
    protected $description = 'Handle expired tokens and turn off relays';

    protected $mqttService; // Add the MqttService instance

    public function __construct(MqttService $mqttService) // Inject the MqttService
    {
        parent::__construct();
        $this->mqttService = $mqttService; // Set the instance
    }

    public function handle()
    {
        $now = now();  // Get the current time
        // Fetch tokens that have an active charging session
        $tokens = Token::whereNotNull('start_time')
                        ->get();  // We are no longer checking the expiry here

        foreach ($tokens as $token) {
            // Check if the duration has been reached (charging timer)
            if ($now->diffInMinutes($token->start_time) >= $token->duration) {
                // Only turn off the relay if the charging duration is over
                $this->sendMQTTCommand($token->charging_port, 'stop');
                $token->delete(); // Remove the token once the charging session has ended
            }
        }

        Log::info('Expired tokens processed.');
    }


    protected function sendMQTTCommand($chargingPort, $action)
    {
        // Publish an MQTT message to turn off charging on the specific port
        $topic = "charging_port_$chargingPort/$action";
        $message = $action;

        try {
            // Connect to MQTT broker, publish the message, and then disconnect
            $this->mqttService->connect();
            $this->mqttService->publish($topic, $message);
            $this->mqttService->disconnect();
        } catch (\Exception $e) {
            Log::error("Failed to send MQTT command for charging port $chargingPort: " . $e->getMessage());
        }
    }
}
