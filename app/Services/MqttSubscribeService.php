<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\MqttClientException;
use Illuminate\Support\Facades\Log;

class MqttSubscribeService
{
    protected $client;
    private $subscriptions = [];

    public function __construct()
    {
        $this->client = new MqttClient(
            config('mqtt.host'), 
            config('mqtt.port'), 
            config('mqtt.client_id') . '_subscribe' // Unique client ID for subscribing
        );
    }

    public function connect()
    {
        try {
            $settings = (new ConnectionSettings())
                ->setUsername(config('mqtt.username'))
                ->setPassword(config('mqtt.password'))
                ->setKeepAliveInterval(config('mqtt.keep_alive_interval', 60));

            $this->client->connect($settings);
        } catch (MqttClientException $e) {
            Log::error('(MqttSubscribeService) Could not connect to MQTT broker: ' . $e->getMessage());
            throw new \Exception('Could not connect to MQTT broker: ' . $e->getMessage());
        }
    }

    public function subscribe($topic, $callback)
    {
        $this->subscriptions[$topic] = $callback;
    }

    public function startListening()
    {
        try {
            // Connect to the MQTT broker
            $this->connect();

            foreach ($this->subscriptions as $topic => $callback) {
                $this->client->subscribe($topic, function ($topic, $message) use ($callback) {
                    $callback($message);
                }, config('mqtt.qos', 0));
            }

            // Loop to keep the connection alive and listening
            $this->client->loop(true);  // Passing `true` to keep the connection persistent
        } catch (MqttClientException $e) {
            Log::error('(MqttSubscribeService) Failed to listen to MQTT topics: ' . $e->getMessage());
        }
    }

    public function disconnect()
    {
        try {
            $this->client->disconnect();
        } catch (MqttClientException $e) {
            Log::error('(MqttSubscribeService) Could not disconnect from MQTT broker: ' . $e->getMessage());
        }
    }
}
