<?php

declare(strict_types=1);

final class ArduinoSensorClient
{
    public function __construct(
        private readonly string $url,
        private readonly int $timeout = 5
    ) {
    }

    public function capture(): array
    {
        $payload = $this->fetchPayload();
        $reading = $this->parsePayload($payload);
        $reading['source_url'] = $this->url;
        $reading['captured_at'] = gmdate('c');
        $reading['raw_payload'] = $payload;

        return $reading;
    }

    private function fetchPayload(): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'ignore_errors' => true,
                'header' => "Accept: application/json,text/html,text/plain\r\n",
            ],
        ]);

        $payload = @file_get_contents($this->url, false, $context);

        if ($payload === false || trim($payload) === '') {
            throw new RuntimeException('No se pudo leer la IP del Arduino.');
        }

        return $payload;
    }

    private function parsePayload(string $payload): array
    {
        $trimmed = trim($payload);
        $json = json_decode($trimmed, true);

        if (is_array($json)) {
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
            'object_state' => $this->matchText('/Objeto\s+([A-ZÁÉÍÓÚÑ ]+?)(?:\s+Actualiza|\s*$)/iu', $plainText),
        ];
    }

    private function parseJson(array $json): array
    {
        return [
            'light_value' => $this->nullableInt($json['light_value'] ?? $json['luz'] ?? $json['light'] ?? null),
            'light_state' => $this->nullableString($json['light_state'] ?? $json['estado_luz'] ?? null),
            'temperature_c' => $this->nullableFloat($json['temperature_c'] ?? $json['temperatura'] ?? $json['temperature'] ?? null),
            'humidity_percent' => $this->nullableFloat($json['humidity_percent'] ?? $json['humedad'] ?? $json['humidity'] ?? null),
            'sound_d0' => $this->nullableInt($json['sound_d0'] ?? $json['d0'] ?? null),
            'sound_a1' => $this->nullableInt($json['sound_a1'] ?? $json['a1'] ?? null),
            'object_state' => $this->nullableString($json['object_state'] ?? $json['objeto'] ?? $json['object'] ?? null),
        ];
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
