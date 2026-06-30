<?php

declare(strict_types=1);

$envPath = dirname(__DIR__) . '/.env';
$envValues = is_file($envPath) ? parse_ini_file($envPath, false, INI_SCANNER_RAW) : [];
$sensorApiKey = clean_env_value(getenv('SENSORES_API_KEY') ?: ($envValues['SENSORES_API_KEY'] ?? ''));

function clean_env_value(mixed $value): string
{
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    $first = $value[0];
    $last = $value[strlen($value) - 1];

    if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
        return trim(substr($value, 1, -1));
    }

    return trim($value, "\"' \t\n\r\0\x0B");
}

return [
    'arduino_url' => 'https://sensores.xn--diseoygestion-lkb.com/api/sensores_seguro.php?limit=100',
    'arduino_urls' => [
        'https://sensores.xn--diseoygestion-lkb.com/api/sensores_seguro.php?limit=100',
        'https://sensores.xn--diseoygestion-lkb.com/api.php?action=poll',
        'https://sensores.xn--diseoygestion-lkb.com/',
        'http://192.168.101.198/',
    ],
    'api_headers' => array_filter([
        'X-API-Key' => $sensorApiKey,
    ]),
    'database_path' => __DIR__ . '/storage/sensores.sqlite',
    'fallback_database_path' => __DIR__ . '/storage/sensores.jsonl',
    'request_timeout' => 5,
    'history_limit' => 80,
];
