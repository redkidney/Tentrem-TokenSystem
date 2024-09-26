<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MqttService;
use Illuminate\Support\Facades\Log;

class MqttSubscribe extends Command
{
    // Define the name and signature of the console command
    protected $signature = 'mqtt:subscribe';

    // Define the command description
    protected $description = 'Subscribe to MQTT topic and receive messages';

    protected $mqtt;

    // Constructor to inject the MQTT service
    public function __construct(MqttService $mqtt)
    {
        parent::__construct();
        $this->mqtt = $mqtt;
    }

    // Handle the console command execution
    public function handle()
    {
        $this->info('Subscribing to the MQTT topic...');

        // Subscribe to the topic where the ESP32 will publish messages
        $this->mqtt->subscribe('esp32/messages', function ($message) {
            // Log the incoming message to the terminal
            Log::info("Received message: " . $message);

            // Display it in the terminal
            $this->info("Received message: " . $message);
        });
    }
}
