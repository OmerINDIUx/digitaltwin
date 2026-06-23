<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$latest = $sensorDatabase->latest();
$stats = $sensorDatabase->stats();
$history = $sensorDatabase->history(40);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel de Sensores</title>
    <link rel="stylesheet" href="sensor-panel.css">
</head>
<body>
    <main class="sensor-shell">
        <header class="sensor-header">
            <div>
                <p class="eyebrow">Arduino UNO R4 WiFi</p>
                <h1>Panel de Sensores</h1>
                <p class="source">Fuente: <?= htmlspecialchars($sensorConfig['arduino_url'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="header-actions">
                <span id="connectionBadge" class="badge badge-muted">Esperando lectura</span>
                <button id="captureNow" type="button">Capturar ahora</button>
            </div>
        </header>

        <section class="status-grid" aria-label="Lecturas actuales">
            <article class="metric metric-light">
                <span>Luz</span>
                <strong id="lightValue"><?= value_or_dash($latest['light_value'] ?? null) ?></strong>
                <small id="lightState"><?= htmlspecialchars((string) ($latest['light_state'] ?? 'Sin dato'), ENT_QUOTES, 'UTF-8') ?></small>
            </article>
            <article class="metric metric-temp">
                <span>Temperatura</span>
                <strong><span id="temperatureValue"><?= value_or_dash($latest['temperature_c'] ?? null) ?></span> °C</strong>
                <small>Promedio: <span id="avgTemperature"><?= value_or_dash($stats['avg_temperature_c'] ?? null) ?></span> °C</small>
            </article>
            <article class="metric metric-humidity">
                <span>Humedad</span>
                <strong><span id="humidityValue"><?= value_or_dash($latest['humidity_percent'] ?? null) ?></span>%</strong>
                <small>Promedio: <span id="avgHumidity"><?= value_or_dash($stats['avg_humidity_percent'] ?? null) ?></span>%</small>
            </article>
            <article class="metric metric-object">
                <span>Objeto</span>
                <strong id="objectState"><?= htmlspecialchars((string) ($latest['object_state'] ?? 'Sin dato'), ENT_QUOTES, 'UTF-8') ?></strong>
                <small>Detecciones: <span id="objectDetections"><?= (int) ($stats['object_detections'] ?? 0) ?></span></small>
            </article>
        </section>

        <section class="dashboard-grid">
            <article class="panel chart-panel">
                <div class="panel-title">
                    <div>
                        <h2>Tendencia en vivo</h2>
                        <p id="lastCapture">Última captura: <?= htmlspecialchars((string) ($latest['captured_at'] ?? 'sin registros'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <span id="totalReadings"><?= (int) ($stats['total_readings'] ?? 0) ?> lecturas</span>
                </div>
                <canvas id="sensorChart" width="1100" height="420"></canvas>
                <div class="legend">
                    <span><i class="line temp"></i>Temperatura</span>
                    <span><i class="line humidity"></i>Humedad</span>
                    <span><i class="line light"></i>Luz</span>
                </div>
            </article>

            <aside class="panel side-panel">
                <h2>Estado de captura</h2>
                <dl>
                    <div>
                        <dt>Sonido D0</dt>
                        <dd id="soundD0"><?= value_or_dash($latest['sound_d0'] ?? null) ?></dd>
                    </div>
                    <div>
                        <dt>Sonido A1</dt>
                        <dd id="soundA1"><?= value_or_dash($latest['sound_a1'] ?? null) ?></dd>
                    </div>
                    <div>
                        <dt>Máx. temperatura</dt>
                        <dd><span id="maxTemperature"><?= value_or_dash($stats['max_temperature_c'] ?? null) ?></span> °C</dd>
                    </div>
                    <div>
                        <dt>Mín. temperatura</dt>
                        <dd><span id="minTemperature"><?= value_or_dash($stats['min_temperature_c'] ?? null) ?></span> °C</dd>
                    </div>
                </dl>
            </aside>
        </section>

        <section class="panel">
            <div class="panel-title">
                <div>
                    <h2>Bitácora</h2>
                    <p>Lecturas almacenadas en la base propia de Sensores.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Luz</th>
                            <th>Temp.</th>
                            <th>Humedad</th>
                            <th>Sonido</th>
                            <th>Objeto</th>
                        </tr>
                    </thead>
                    <tbody id="historyRows">
                        <?php foreach ($history as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars(format_time($row['captured_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= value_or_dash($row['light_value'] ?? null) ?> <?= htmlspecialchars((string) ($row['light_state'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= value_or_dash($row['temperature_c'] ?? null) ?> °C</td>
                                <td><?= value_or_dash($row['humidity_percent'] ?? null) ?>%</td>
                                <td>D0 <?= value_or_dash($row['sound_d0'] ?? null) ?> / A1 <?= value_or_dash($row['sound_a1'] ?? null) ?></td>
                                <td><?= htmlspecialchars((string) ($row['object_state'] ?? 'Sin dato'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        window.initialSensorHistory = <?= json_encode($history, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="sensor-panel.js"></script>
</body>
</html>
<?php

function value_or_dash(mixed $value): string
{
    if ($value === null || $value === '') {
        return '--';
    }

    if (is_numeric($value)) {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function format_time(?string $value): string
{
    if (!$value) {
        return '--';
    }

    try {
        return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('H:i:s');
    } catch (Throwable) {
        return $value;
    }
}
