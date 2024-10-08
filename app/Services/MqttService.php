<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class MqttService
{
    protected $client;
    private $subscriptions = [];

    public function __construct()
    {
        $this->client = new MqttClient(
            config('mqtt.host'), 
            config('mqtt.port'), 
            config('mqtt.client_id')
        );
    }

    public function connect()
    {
        try {
            $settings = (new ConnectionSettings())
                ->setUsername(config('mqtt.username'))
                ->setPassword(config('mqtt.password'))
                ->setUseTls(config('mqtt.use_tls', false))
                ->setKeepAliveInterval(config('mqtt.keep_alive_interval', 60));

            $this->client->connect($settings, config('mqtt.clean_session', true));
            Log::info('(MqttService) Connected to MQTT broker.');

        } catch (MqttClientException $e) {
            Log::error('(MqttService) Could not connect to MQTT broker: ' . $e->getMessage());
            $this->reconnect(); // Attempt reconnection on failure
        }
    }

    public function reconnect()
    {
        // Try reconnecting after a delay
        sleep(5);  // Wait 5 seconds before retrying
        try {
            $this->connect();
            Log::info('(MqttService) Reconnected to MQTT broker.');
        } catch (\Exception $e) {
            Log::error('(MqttService) Reconnection failed: ' . $e->getMessage());
            $this->reconnect();  // Continue attempting to reconnect
        }
    }

    public function publish($topic, $message)
    {
        try {
            $this->client->publish(
                $topic, 
                $message, 
                config('mqtt.qos', 0),  // QoS level, default 0
                config('mqtt.retain', false)  // Retain flag, default false
            );
            Log::info("(MqttService) Published message to topic {$topic}.");
        } catch (MqttClientException $e) {
            throw new \Exception('Could not publish MQTT message: ' . $e->getMessage());
        }
    }

    public function disconnect()
    {
        try {
            $this->client->disconnect();
            Log::info('(MqttService) Disconnected from MQTT broker.');
        } catch (MqttClientException $e) {
            throw new \Exception('Could not disconnect from MQTT broker: ' . $e->getMessage());
        }
    }

    public function subscribe($topic, $callback)
    {
        $this->subscriptions[$topic] = $callback;
    }

    public function startListening()
    {
        try {
            if (!$this->client->isConnected()) {
                $this->connect();
            }

            foreach ($this->subscriptions as $topic => $callback) {
                $this->client->subscribe($topic, function ($topic, $message) use ($callback) {
                    $callback($message);
                }, config('mqtt.qos', 0));
                Log::info("(MqttService) Subscribed to topic {$topic}.");
            }

            while (true) {
                $this->client->loop(0);
                // Check for disconnections and attempt reconnection if needed
                if (!$this->client->isConnected()) {
                    Log::warning('(MqttService) Connection lost. Attempting to reconnect.');
                    $this->reconnect();
                }
            }

        } catch (MqttClientException $e) {
            Log::error('(MqttService) Failed to subscribe to MQTT topic: ' . $e->getMessage());
            $this->reconnect();
        }
    }

    public function loop()
    {
        try {
            $this->client->loop(0);
        } catch (MqttClientException $e) {
            Log::error('(MqttService) Error in MQTT loop: ' . $e->getMessage());
            $this->reconnect();
        }
    }
}
