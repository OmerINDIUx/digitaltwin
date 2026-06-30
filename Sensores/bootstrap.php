<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/ArduinoSensorClient.php';

$sensorConfig = require __DIR__ . '/config.php';
$sensorDatabase = new Database($sensorConfig['database_path'], $sensorConfig['fallback_database_path']);
$sensorClient = new ArduinoSensorClient(
    $sensorConfig['arduino_urls'] ?? $sensorConfig['arduino_url'],
    $sensorConfig['request_timeout'],
    $sensorConfig['api_headers'] ?? []
);
