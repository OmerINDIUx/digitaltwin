<?php

declare(strict_types=1);

return [
    'arduino_url' => 'http://192.168.101.198/',
    'database_path' => __DIR__ . '/storage/sensores.sqlite',
    'fallback_database_path' => __DIR__ . '/storage/sensores.jsonl',
    'request_timeout' => 5,
    'history_limit' => 80,
];
