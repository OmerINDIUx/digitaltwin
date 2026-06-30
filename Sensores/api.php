<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$action = $_GET['action'] ?? 'latest';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = json_decode((string) file_get_contents('php://input'), true);

        if (!is_array($payload)) {
            throw new InvalidArgumentException('El cuerpo POST debe ser JSON.');
        }

        $reading = [
            'source_url' => 'manual-post',
            'captured_at' => gmdate('c'),
            'light_value' => isset($payload['light_value']) ? (int) $payload['light_value'] : null,
            'light_state' => $payload['light_state'] ?? null,
            'temperature_c' => isset($payload['temperature_c']) ? (float) $payload['temperature_c'] : null,
            'humidity_percent' => isset($payload['humidity_percent']) ? (float) $payload['humidity_percent'] : null,
            'sound_d0' => isset($payload['sound_d0']) ? (int) $payload['sound_d0'] : null,
            'sound_a1' => isset($payload['sound_a1']) ? (int) $payload['sound_a1'] : null,
            'noise_state' => $payload['noise_state'] ?? $payload['noise_label'] ?? $payload['ruido'] ?? $payload['estado_ruido'] ?? $payload['ruido_estado'] ?? $payload['clasificacion_ruido'] ?? null,
            'object_state' => $payload['object_state'] ?? null,
            'raw_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        respond(['ok' => true, 'reading' => $sensorDatabase->insertReading($reading)]);
    }

    match ($action) {
        'poll' => respond(poll_payload($sensorDatabase, $sensorClient)),
        'history' => respond([
            'ok' => true,
            'driver' => $sensorDatabase->driver(),
            'readings' => $sensorDatabase->history((int) ($_GET['limit'] ?? $sensorConfig['history_limit'])),
            'stats' => $sensorDatabase->stats(),
        ]),
        'stats' => respond(['ok' => true, 'driver' => $sensorDatabase->driver(), 'stats' => $sensorDatabase->stats()]),
        default => respond([
            'ok' => true,
            'driver' => $sensorDatabase->driver(),
            'reading' => $sensorDatabase->latest(),
            'stats' => $sensorDatabase->stats(),
        ]),
    };
} catch (Throwable $exception) {
    http_response_code(200);
    respond([
        'ok' => false,
        'error' => $exception->getMessage(),
        'reading' => latest_valid_reading($sensorDatabase),
        'recent_readings' => sanitize_readings_for_response(recent_valid_readings($sensorDatabase, 5)),
        'diagnostics' => sensor_diagnostics($sensorConfig),
        'stats' => $sensorDatabase->stats(),
    ]);
}

function respond(array $payload): never
{
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function poll_payload(Database $database, ArduinoSensorClient $client): array
{
    $latestReadings = $client->captureLatestReadings();

    foreach ($latestReadings as $latestReading) {
        $database->insertReading($latestReading);
    }

    $reading = $latestReadings[0] ?? $database->latest();

    return [
        'ok' => true,
        'driver' => $database->driver(),
        'reading' => sanitize_reading_for_response($reading),
        'latest_readings' => sanitize_readings_for_response($latestReadings),
        'recent_readings' => sanitize_readings_for_response(recent_valid_readings($database, 100)),
        'stats' => $database->stats(),
    ];
}

function latest_valid_reading(Database $database): ?array
{
    $readings = recent_valid_readings($database, 1);

    return sanitize_reading_for_response($readings[0] ?? null);
}

function recent_valid_readings(Database $database, int $limit): array
{
    $history = $database->history(80);
    $validReadings = array_values(array_filter($history, static fn (array $reading): bool => is_valid_sensor_reading($reading)));

    return array_slice($validReadings, -$limit);
}

function is_valid_sensor_reading(array $reading): bool
{
    foreach (['light_value', 'light_state', 'temperature_c', 'humidity_percent', 'sound_d0', 'sound_a1', 'noise_state', 'object_state'] as $key) {
        if (($reading[$key] ?? null) !== null && $reading[$key] !== '') {
            return true;
        }
    }

    return false;
}

function sensor_diagnostics(array $config): array
{
    $apiKey = (string) ($config['api_headers']['X-API-Key'] ?? '');

    return [
        'source_url' => $config['arduino_url'] ?? null,
        'api_key_loaded' => $apiKey !== '',
        'api_key_length' => strlen($apiKey),
    ];
}

function sanitize_readings_for_response(array $readings): array
{
    return array_values(array_map(
        static fn (array $reading): array => sanitize_reading_for_response($reading) ?? [],
        $readings
    ));
}

function sanitize_reading_for_response(?array $reading): ?array
{
    if ($reading === null) {
        return null;
    }

    unset($reading['raw_payload']);

    return $reading;
}
