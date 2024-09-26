<?php

return [
    'host'          => env('MQTT_HOST', 'broker.hivemq.com'),
    'port'          => env('MQTT_PORT', 1883),
    'client_id'     => env('MQTT_CLIENT_ID', 'laravel_mqtt_client_' . uniqid()),
    'username'      => env('MQTT_USERNAME', null),
    'password'      => env('MQTT_PASSWORD', null),
    'clean_session' => env('MQTT_CLEAN_SESSION', true),
    'qos'           => env('MQTT_QOS', 0),  // Quality of Service (0, 1, or 2)
    'retain'        => env('MQTT_RETAIN', false),  // Retain flag (true or false)
    'use_tls'       => env('MQTT_USE_TLS', false), // Use TLS (true or false)
    'keep_alive_interval' => env('MQTT_KEEP_ALIVE_INTERVAL', 60), // Keep-alive interval in seconds
];
