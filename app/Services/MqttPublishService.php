<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\MqttClientException;
use Illuminate\Support\Facades\Log;

class MqttPublishService
{
    protected $client;

    public function __construct()
    {
        // Create a separate client for publishing
        $this->client = new MqttClient(
            config('mqtt.host'), 
            config('mqtt.port'), 
            config('mqtt.client_id') . '_publish' // Unique client ID
        );
    }

    public function connect()
    {
        $settings = (new ConnectionSettings())
            ->setUsername(config('mqtt.username'))
            ->setPassword(config('mqtt.password'))
            ->setKeepAliveInterval(config('mqtt.keep_alive_interval', 60));

        $this->client->connect($settings);
    }

    public function publish($topic, $message)
    {
        try {
            $this->connect();
            $this->client->publish($topic, $message, config('mqtt.qos', 0), config('mqtt.retain', false));
        } catch (MqttClientException $e) {
            throw new \Exception('Could not publish MQTT message: ' . $e->getMessage());
        } finally {
            $this->disconnect();
        }
    }

    public function disconnect()
    {
        if ($this->client->isConnected()) {
            $this->client->disconnect();
        }
    }
}
