<?php

declare(strict_types=1);

final class ArduinoSensorClient
{
    /** @var string[] */
    private array $urls;
    private string $activeUrl;

    public function __construct(
        string|array $url,
        private readonly int $timeout = 5,
        private readonly array $headers = []
    ) {
        $urls = is_array($url) ? $url : [$url];
        $this->urls = array_values(array_filter(array_map('strval', $urls)));

        if (empty($this->urls)) {
            throw new InvalidArgumentException('No hay fuentes configuradas para sensores.');
        }

        $this->activeUrl = $this->urls[0];
    }

    public function capture(): array
    {
        $payload = $this->fetchPayload();
        $reading = $this->parsePayload($payload);
        $reading['source_url'] = $this->activeUrl;
        $reading['captured_at'] = gmdate('c');
        $reading['raw_payload'] = $payload;

        return $reading;
    }

    public function captureLatestReadings(): array
    {
        $payload = $this->fetchPayload();
        $readings = $this->parsePayloadMany($payload);
        $capturedAt = gmdate('c');

        return array_map(function (array $reading) use ($payload, $capturedAt): array {
            $reading['source_url'] = $this->activeUrl;
            $reading['captured_at'] = $reading['captured_at'] ?? $capturedAt;
            $reading['raw_payload'] = $payload;

            return $reading;
        }, $readings);
    }

    private function fetchPayload(): string
    {
        $headers = ["Accept: application/json,text/html,text/plain"];

        foreach ($this->headers as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $headers[] = $name . ': ' . $value;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'ignore_errors' => true,
                'header' => implode("\r\n", $headers) . "\r\n",
            ],
        ]);

        $lastError = null;

        foreach ($this->urls as $url) {
            $payload = @file_get_contents($url, false, $context);

            if (($payload === false || trim($payload) === '') && function_exists('curl_init')) {
                $payload = $this->fetchWithCurl($url, $headers);
            }

            if ($payload !== false && trim($payload) !== '') {
                $this->activeUrl = $url;

                return $payload;
            }

            $lastError = error_get_last()['message'] ?? null;
        }

        throw new RuntimeException('No se pudo leer ninguna fuente de sensores.' . ($lastError ? ' ' . $lastError : ''));
    }

    private function fetchWithCurl(string $url, array $headers): string|false
    {
        $curl = curl_init($url);

        if ($curl === false) {
            return false;
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $payload = curl_exec($curl);
        curl_close($curl);

        return is_string($payload) ? $payload : false;
    }

    private function parsePayload(string $payload): array
    {
        $trimmed = trim($payload);
        $json = json_decode($trimmed, true);

        if (is_array($json)) {
            $this->throwIfApiError($json);

            return $this->parseJson($json);
        }

        $plainText = $this->htmlToText($payload);

        return [
            'light_value' => $this->matchInt('/Luz\s+(\d+)/i', $plainText),
            'light_state' => $this->matchText('/Luz\s+\d+\s*-\s*([A-ZÁÉÍÓÚÑ ]+?)(?:\s+Clima|\s+Sonido|\s+Objeto|\s*$)/iu', $plainText),
            'temperature_c' => $this->matchFloat('/(-?\d+(?:[.,]\d+)?)\s*°\s*C/i', $plainText),
            'humidity_percent' => $this->matchFloat('/(-?\d+(?:[.,]\d+)?)\s*%/i', $plainText),
            'sound_d0' => $this->matchInt('/D0\s*:\s*(\d+)/i', $plainText),
            'sound_a1' => $this->matchInt('/A1\s*:\s*(\d+)/i', $plainText),
            'noise_state' => $this->matchText('/Ruido\s+([A-ZÁÉÍÓÚÑ ]+?)(?:\s+Objeto|\s+Actualiza|\s*$)/iu', $plainText),
            'object_state' => $this->matchText('/Objeto\s+([A-ZÁÉÍÓÚÑ ]+?)(?:\s+Actualiza|\s*$)/iu', $plainText),
        ];
    }

    private function parsePayloadMany(string $payload): array
    {
        $trimmed = trim($payload);
        $json = json_decode($trimmed, true);

        if (is_array($json)) {
            $this->throwIfApiError($json);

            $readings = $this->parseJsonMany($json);

            return !empty($readings) ? $readings : [$this->parseJson($json)];
        }

        return [$this->parsePayload($payload)];
    }

    private function parseJson(array $json): array
    {
        if (array_is_list($json)) {
            $firstReading = $this->latestJsonReading($json);

            return is_array($firstReading) ? $this->parseJson($firstReading) : [];
        }

        if (isset($json['reading']) && is_array($json['reading'])) {
            return $this->parseJson($json['reading']);
        }

        if (isset($json['data']) && is_array($json['data'])) {
            return $this->parseJson($json['data']);
        }

        if (isset($json['readings']) && is_array($json['readings'])) {
            return $this->parseJson($json['readings']);
        }

        if (isset($json['sensores']) && is_array($json['sensores'])) {
            return $this->parseJson($json['sensores']);
        }

        return [
            'light_value' => $this->nullableInt($json['light_value'] ?? $json['luz_adc'] ?? $json['luz'] ?? $json['light'] ?? $json['valor_luz'] ?? $json['luz_valor'] ?? null),
            'light_state' => $this->nullableString($json['light_state'] ?? $json['estado_luz'] ?? $json['estado'] ?? $json['luz_estado'] ?? null),
            'temperature_c' => $this->nullableFloat($json['temperature_c'] ?? $json['temperatura_dht'] ?? $json['temperatura'] ?? $json['temperature'] ?? $json['temp'] ?? $json['temperatura_c'] ?? null),
            'humidity_percent' => $this->nullableFloat($json['humidity_percent'] ?? $json['humedad'] ?? $json['humidity'] ?? $json['hum'] ?? $json['humedad_percent'] ?? null),
            'sound_d0' => $this->nullableInt($json['sound_d0'] ?? $json['d0'] ?? $json['sonido_d0'] ?? $json['sonido_digital'] ?? null),
            'sound_a1' => $this->nullableInt($json['sound_a1'] ?? $json['a1'] ?? $json['rms'] ?? $json['sonido_a1'] ?? $json['sonido_analogico'] ?? null),
            'noise_state' => $this->nullableString($json['noise_state'] ?? $json['noise_label'] ?? $json['ruido'] ?? $json['estado_ruido'] ?? $json['ruido_estado'] ?? $json['clasificacion_ruido'] ?? $json['sound_state'] ?? null),
            'object_state' => $this->nullableString($json['object_state'] ?? $json['objeto'] ?? $json['object'] ?? $json['estado_objeto'] ?? null),
            'sensor_key' => $this->nullableString($json['sensor_key'] ?? $json['sensor'] ?? $json['sensor_id'] ?? $json['device_id'] ?? $json['dispositivo'] ?? $json['nombre'] ?? null),
            'sensor_name' => $this->nullableString($json['sensor_name'] ?? $json['nombre_sensor'] ?? $json['nombre'] ?? $json['dispositivo'] ?? null),
            'battery_percent' => $this->nullableFloat($json['battery_percent'] ?? $json['bateria'] ?? $json['battery'] ?? null),
            'captured_at' => $this->nullableString($json['captured_at'] ?? $json['created_at'] ?? $json['fecha_iso'] ?? $json['fecha_hora'] ?? $json['timestamp'] ?? null),
        ];
    }

    private function throwIfApiError(array $json): void
    {
        if (($json['ok'] ?? null) === false) {
            throw new RuntimeException((string) ($json['message'] ?? $json['error'] ?? 'La API de sensores rechazo la lectura.'));
        }
    }

    private function parseJsonMany(array $json): array
    {
        $rows = $this->extractJsonRows($json);

        if (empty($rows)) {
            return [];
        }

        $grouped = [];

        foreach ($rows as $index => $row) {
            $reading = $this->parseJson($row);
            $key = $reading['sensor_key'] ?? 'sensor';
            $existing = $grouped[$key] ?? null;

            if (
                $existing === null ||
                $this->jsonReadingTimestamp($row) >= $this->jsonReadingTimestamp($existing['_raw'] ?? [])
            ) {
                $reading['_raw'] = $row;
                $grouped[$key] = $reading;
            }
        }

        return array_map(function (array $reading): array {
            unset($reading['_raw']);

            return $reading;
        }, array_values($grouped));
    }

    private function extractJsonRows(array $json): array
    {
        if (array_is_list($json)) {
            return array_values(array_filter($json, 'is_array'));
        }

        foreach (['readings', 'sensores', 'data', 'items', 'rows', 'registros'] as $key) {
            if (isset($json[$key]) && is_array($json[$key])) {
                return $this->extractJsonRows($json[$key]);
            }
        }

        return [];
    }

    private function latestJsonReading(array $readings): array
    {
        $rows = array_values(array_filter($readings, 'is_array'));

        usort($rows, function (array $left, array $right): int {
            $leftTime = $this->jsonReadingTimestamp($left);
            $rightTime = $this->jsonReadingTimestamp($right);

            if ($leftTime !== $rightTime) {
                return $rightTime <=> $leftTime;
            }

            return ((int) ($right['id'] ?? 0)) <=> ((int) ($left['id'] ?? 0));
        });

        return $rows[0] ?? [];
    }

    private function jsonReadingTimestamp(array $reading): int
    {
        $value = $reading['captured_at']
            ?? $reading['created_at']
            ?? $reading['fecha_iso']
            ?? $reading['fecha']
            ?? $reading['fecha_hora']
            ?? $reading['timestamp']
            ?? null;

        if (!$value) {
            return 0;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? 0 : $timestamp;
    }

    private function htmlToText(string $payload): string
    {
        $payload = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $payload) ?? $payload;
        $payload = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $payload) ?? $payload;
        $payload = preg_replace('/<\/(h1|h2|div|p|br)>/i', "\n", $payload) ?? $payload;
        $text = html_entity_decode(strip_tags($payload), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/[ \t\r\n]+/', ' ', trim($text)) ?? trim($text);
    }

    private function matchInt(string $pattern, string $text): ?int
    {
        if (!preg_match($pattern, $text, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function matchFloat(string $pattern, string $text): ?float
    {
        if (!preg_match($pattern, $text, $matches)) {
            return null;
        }

        return (float) str_replace(',', '.', $matches[1]);
    }

    private function matchText(string $pattern, string $text): ?string
    {
        if (!preg_match($pattern, $text, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }
}
