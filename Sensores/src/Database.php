<?php

declare(strict_types=1);

final class Database
{
    private ?PDO $pdo = null;
    private string $filePath;
    private string $driver;

    public function __construct(string $databasePath, ?string $fallbackPath = null)
    {
        $this->filePath = $fallbackPath ?: preg_replace('/\.[^.]+$/', '.jsonl', $databasePath);

        if (in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->driver = 'sqlite';
            $this->openSqlite($databasePath);
            return;
        }

        $this->driver = 'jsonl';
        $this->openJsonl($this->filePath);
    }

    public function driver(): string
    {
        return $this->driver;
    }

    public function insertReading(array $reading): array
    {
        if ($this->pdo instanceof PDO) {
            return $this->insertSqlite($reading);
        }

        return $this->insertJsonl($reading);
    }

    public function latest(): ?array
    {
        if ($this->pdo instanceof PDO) {
            $statement = $this->pdo->query('SELECT * FROM sensor_readings ORDER BY captured_at DESC, id DESC LIMIT 1');
            $row = $statement->fetch();

            return $row ?: null;
        }

        $rows = $this->allJsonlRows();
        usort($rows, $this->latestSort(...));

        return $rows[0] ?? null;
    }

    public function history(int $limit = 80): array
    {
        $limit = max(1, min($limit, 500));

        if ($this->pdo instanceof PDO) {
            $statement = $this->pdo->prepare('SELECT * FROM sensor_readings ORDER BY captured_at DESC, id DESC LIMIT :limit');
            $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
            $statement->execute();

            return array_reverse($statement->fetchAll());
        }

        $rows = $this->allJsonlRows();
        usort($rows, $this->latestSort(...));

        return array_reverse(array_slice($rows, 0, $limit));
    }

    public function stats(): array
    {
        if ($this->pdo instanceof PDO) {
            $statement = $this->pdo->query(
                'SELECT
                    COUNT(*) AS total_readings,
                    MIN(captured_at) AS first_capture,
                    MAX(captured_at) AS last_capture,
                    ROUND(AVG(temperature_c), 2) AS avg_temperature_c,
                    ROUND(AVG(humidity_percent), 2) AS avg_humidity_percent,
                    ROUND(AVG(light_value), 2) AS avg_light_value,
                    MAX(temperature_c) AS max_temperature_c,
                    MIN(temperature_c) AS min_temperature_c,
                    SUM(CASE WHEN object_state IS NOT NULL AND UPPER(object_state) NOT LIKE "%SIN OBJETO%" THEN 1 ELSE 0 END) AS object_detections
                FROM sensor_readings'
            );

            return $statement->fetch() ?: [];
        }

        return $this->jsonlStats();
    }

    private function openSqlite(string $databasePath): void
    {
        $directory = dirname($databasePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $this->pdo = new PDO('sqlite:' . $databasePath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->migrate();
    }

    private function openJsonl(string $filePath): void
    {
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!is_file($filePath)) {
            touch($filePath);
        }
    }

    private function insertSqlite(array $reading): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO sensor_readings (
                source_url,
                captured_at,
                sensor_key,
                sensor_name,
                light_value,
                light_state,
                temperature_c,
                humidity_percent,
                sound_d0,
                sound_a1,
                noise_state,
                object_state,
                battery_percent,
                raw_payload
            ) VALUES (
                :source_url,
                :captured_at,
                :sensor_key,
                :sensor_name,
                :light_value,
                :light_state,
                :temperature_c,
                :humidity_percent,
                :sound_d0,
                :sound_a1,
                :noise_state,
                :object_state,
                :battery_percent,
                :raw_payload
            )'
        );

        $statement->execute([
            ':source_url' => $reading['source_url'] ?? null,
            ':captured_at' => $reading['captured_at'],
            ':sensor_key' => $reading['sensor_key'] ?? null,
            ':sensor_name' => $reading['sensor_name'] ?? null,
            ':light_value' => $reading['light_value'] ?? null,
            ':light_state' => $reading['light_state'] ?? null,
            ':temperature_c' => $reading['temperature_c'] ?? null,
            ':humidity_percent' => $reading['humidity_percent'] ?? null,
            ':sound_d0' => $reading['sound_d0'] ?? null,
            ':sound_a1' => $reading['sound_a1'] ?? null,
            ':noise_state' => $reading['noise_state'] ?? null,
            ':object_state' => $reading['object_state'] ?? null,
            ':battery_percent' => $reading['battery_percent'] ?? null,
            ':raw_payload' => $reading['raw_payload'] ?? null,
        ]);

        return $this->latest() ?? [];
    }

    private function insertJsonl(array $reading): array
    {
        $rows = $this->allJsonlRows();
        $reading['id'] = empty($rows) ? 1 : ((int) max(array_column($rows, 'id')) + 1);
        $reading['created_at'] = gmdate('c');

        file_put_contents(
            $this->filePath,
            json_encode($reading, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        return $reading;
    }

    private function allJsonlRows(): array
    {
        if (!is_file($this->filePath)) {
            return [];
        }

        $rows = [];
        $lines = file($this->filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $row = json_decode($line, true);

            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function jsonlStats(): array
    {
        $rows = $this->allJsonlRows();
        $temperatures = $this->numericColumn($rows, 'temperature_c');
        $humidity = $this->numericColumn($rows, 'humidity_percent');
        $light = $this->numericColumn($rows, 'light_value');
        $captureTimes = array_values(array_filter(array_column($rows, 'captured_at')));

        return [
            'total_readings' => count($rows),
            'first_capture' => empty($captureTimes) ? null : min($captureTimes),
            'last_capture' => empty($captureTimes) ? null : max($captureTimes),
            'avg_temperature_c' => $this->average($temperatures),
            'avg_humidity_percent' => $this->average($humidity),
            'avg_light_value' => $this->average($light),
            'max_temperature_c' => empty($temperatures) ? null : max($temperatures),
            'min_temperature_c' => empty($temperatures) ? null : min($temperatures),
            'object_detections' => count(array_filter($rows, function (array $row): bool {
                $state = strtoupper((string) ($row['object_state'] ?? ''));

                return $state !== '' && !str_contains($state, 'SIN OBJETO');
            })),
        ];
    }

    private function numericColumn(array $rows, string $key): array
    {
        return array_values(array_filter(array_map(
            static fn (array $row): ?float => is_numeric($row[$key] ?? null) ? (float) $row[$key] : null,
            $rows
        ), static fn (?float $value): bool => $value !== null));
    }

    private function average(array $values): ?float
    {
        if (empty($values)) {
            return null;
        }

        return round(array_sum($values) / count($values), 2);
    }

    private function latestSort(array $left, array $right): int
    {
        $timeCompare = strcmp((string) ($right['captured_at'] ?? ''), (string) ($left['captured_at'] ?? ''));

        if ($timeCompare !== 0) {
            return $timeCompare;
        }

        return ((int) ($right['id'] ?? 0)) <=> ((int) ($left['id'] ?? 0));
    }

    private function migrate(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS sensor_readings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                source_url TEXT,
                captured_at TEXT NOT NULL,
                sensor_key TEXT,
                sensor_name TEXT,
                light_value INTEGER,
                light_state TEXT,
                temperature_c REAL,
                humidity_percent REAL,
                sound_d0 INTEGER,
                sound_a1 INTEGER,
                noise_state TEXT,
                object_state TEXT,
                battery_percent REAL,
                raw_payload TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $this->ensureSqliteColumns([
            'sensor_key' => 'TEXT',
            'sensor_name' => 'TEXT',
            'noise_state' => 'TEXT',
            'battery_percent' => 'REAL',
        ]);
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS sensor_readings_captured_at_idx ON sensor_readings (captured_at)');
    }

    private function ensureSqliteColumns(array $columns): void
    {
        $statement = $this->pdo->query('PRAGMA table_info(sensor_readings)');
        $existingColumns = array_column($statement->fetchAll(), 'name');

        foreach ($columns as $name => $type) {
            if (!in_array($name, $existingColumns, true)) {
                $this->pdo->exec(sprintf('ALTER TABLE sensor_readings ADD COLUMN %s %s', $name, $type));
            }
        }
    }
}
