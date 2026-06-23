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
    http_response_code(500);
    respond([
        'ok' => false,
        'error' => $exception->getMessage(),
        'reading' => $sensorDatabase->latest(),
        'recent_readings' => $sensorDatabase->history(5),
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
    $reading = $database->insertReading($client->capture());

    return [
        'ok' => true,
        'driver' => $database->driver(),
        'reading' => $reading,
        'recent_readings' => $database->history(5),
        'stats' => $database->stats(),
    ];
}
