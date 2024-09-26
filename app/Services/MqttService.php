<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\ConnectionSettings;

class MqttService
{
    protected $client;

    // Constructor to initialize MQTT client
    public function __construct()
    {
        // Initialize the MQTT client with broker details from the config
        $this->client = new MqttClient(
            config('mqtt.host'), 
            config('mqtt.port'), 
            config('mqtt.client_id')
        );
    }

    // Method to connect to the MQTT broker using ConnectionSettings
    public function connect()
    {
        try {
            // Create connection settings object with username, password, and other details
            $settings = (new ConnectionSettings())
                ->setUsername(config('mqtt.username'))
                ->setPassword(config('mqtt.password'))
                ->setUseTls(config('mqtt.use_tls', false)) // Default to 'false' if not set
                ->setKeepAliveInterval(config('mqtt.keep_alive_interval', 60)); // Optional, default is 60 seconds

            // Connect to the MQTT broker
            $this->client->connect($settings, config('mqtt.clean_session', true)); // Pass clean_session directly here
        } catch (MqttClientException $e) {
            throw new \Exception('Could not connect to MQTT broker: ' . $e->getMessage());
        }
    }

    // Method to publish a message to a specific MQTT topic
    public function publish($topic, $message)
    {
        try {
            $this->client->publish(
                $topic, 
                $message, 
                config('mqtt.qos', 0),  // QoS level, default 0
                config('mqtt.retain', false)  // Retain flag, default false
            );
        } catch (MqttClientException $e) {
            throw new \Exception('Could not publish MQTT message: ' . $e->getMessage());
        }
    }

    // Method to disconnect from the MQTT broker
    public function disconnect()
    {
        try {
            $this->client->disconnect();
        } catch (MqttClientException $e) {
            throw new \Exception('Could not disconnect from MQTT broker: ' . $e->getMessage());
        }
    }

    // Optional: Method to subscribe to a topic and handle incoming messages
    public function subscribe($topic, $callback)
    {
        try {
            $this->connect();  // Connect to the MQTT broker

            // Subscribe to the given topic
            $this->client->subscribe($topic, function ($topic, $message) use ($callback) {
                // Handle the incoming message with a callback function
                $callback($message);
            }, config('mqtt.qos', 0));

            // Keep the connection open to listen for incoming messages
            $this->client->loop(true);  // This will block and keep the loop running

        } catch (MqttClientException $e) {
            Log::error('Failed to subscribe to MQTT topic: ' . $e->getMessage());
        }
    }
}
