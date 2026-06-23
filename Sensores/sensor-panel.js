const state = {
    history: Array.isArray(window.initialSensorHistory) ? window.initialSensorHistory : [],
    busy: false,
};

const $ = (id) => document.getElementById(id);
const chart = $("sensorChart");
const ctx = chart.getContext("2d");
const captureButton = $("captureNow");
const badge = $("connectionBadge");

function numberText(value) {
    if (value === null || value === undefined || value === "") return "--";
    const numeric = Number(value);
    if (!Number.isFinite(numeric)) return String(value);
    return numeric.toFixed(2).replace(/\.?0+$/, "");
}

function localTime(value) {
    if (!value) return "--";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
}

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function setBadge(text, kind = "muted") {
    badge.textContent = text;
    badge.className = `badge badge-${kind}`;
}

function updateMetric(reading, stats) {
    if (!reading) return;

    $("lightValue").textContent = numberText(reading.light_value);
    $("lightState").textContent = reading.light_state || "Sin dato";
    $("temperatureValue").textContent = numberText(reading.temperature_c);
    $("humidityValue").textContent = numberText(reading.humidity_percent);
    $("objectState").textContent = reading.object_state || "Sin dato";
    $("soundD0").textContent = numberText(reading.sound_d0);
    $("soundA1").textContent = numberText(reading.sound_a1);
    $("lastCapture").textContent = `Última captura: ${localTime(reading.captured_at)}`;

    if (stats) {
        $("avgTemperature").textContent = numberText(stats.avg_temperature_c);
        $("avgHumidity").textContent = numberText(stats.avg_humidity_percent);
        $("maxTemperature").textContent = numberText(stats.max_temperature_c);
        $("minTemperature").textContent = numberText(stats.min_temperature_c);
        $("objectDetections").textContent = numberText(stats.object_detections);
        $("totalReadings").textContent = `${numberText(stats.total_readings)} lecturas`;
    }
}

function updateHistoryRows() {
    const rows = state.history.slice(-40).reverse().map((row) => `
        <tr>
            <td>${localTime(row.captured_at)}</td>
            <td>${numberText(row.light_value)} ${escapeHtml(row.light_state || "")}</td>
            <td>${numberText(row.temperature_c)} °C</td>
            <td>${numberText(row.humidity_percent)}%</td>
            <td>D0 ${numberText(row.sound_d0)} / A1 ${numberText(row.sound_a1)}</td>
            <td>${escapeHtml(row.object_state || "Sin dato")}</td>
        </tr>
    `);

    $("historyRows").innerHTML = rows.join("");
}

function normalizeSeries(rows, key) {
    const values = rows.map((row) => Number(row[key])).filter(Number.isFinite);
    const min = Math.min(...values);
    const max = Math.max(...values);

    return { min: Number.isFinite(min) ? min : 0, max: Number.isFinite(max) && max !== min ? max : min + 1 };
}

function drawLine(rows, key, color, bounds) {
    const width = chart.width;
    const height = chart.height;
    const pad = 36;
    const points = rows.map((row, index) => {
        const value = Number(row[key]);
        if (!Number.isFinite(value)) return null;

        return {
            x: pad + (index * (width - pad * 2)) / Math.max(rows.length - 1, 1),
            y: height - pad - ((value - bounds.min) * (height - pad * 2)) / (bounds.max - bounds.min),
        };
    });

    ctx.beginPath();
    ctx.strokeStyle = color;
    ctx.lineWidth = 3;

    points.forEach((point) => {
        if (!point) return;
        if (!ctx.currentPointStarted) {
            ctx.moveTo(point.x, point.y);
            ctx.currentPointStarted = true;
        } else {
            ctx.lineTo(point.x, point.y);
        }
    });

    ctx.stroke();
    ctx.currentPointStarted = false;
}

function drawChart() {
    const rows = state.history.slice(-50);
    const width = chart.width;
    const height = chart.height;

    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = "#0d131b";
    ctx.fillRect(0, 0, width, height);

    ctx.strokeStyle = "rgba(255,255,255,0.08)";
    ctx.lineWidth = 1;
    for (let i = 1; i < 5; i += 1) {
        const y = (height / 5) * i;
        ctx.beginPath();
        ctx.moveTo(28, y);
        ctx.lineTo(width - 28, y);
        ctx.stroke();
    }

    if (rows.length < 2) {
        ctx.fillStyle = "#9aa9bb";
        ctx.font = "22px Arial";
        ctx.fillText("Esperando más lecturas", 36, 72);
        return;
    }

    drawLine(rows, "temperature_c", "#ff6d73", normalizeSeries(rows, "temperature_c"));
    drawLine(rows, "humidity_percent", "#54a7ff", normalizeSeries(rows, "humidity_percent"));
    drawLine(rows, "light_value", "#f5c84c", normalizeSeries(rows, "light_value"));
}

async function capture() {
    if (state.busy) return;

    state.busy = true;
    captureButton.disabled = true;
    setBadge("Leyendo Arduino", "muted");

    try {
        const response = await fetch("api.php?action=poll", { cache: "no-store" });
        const payload = await response.json();

        if (!payload.ok) {
            throw new Error(payload.error || "No se pudo capturar la lectura.");
        }

        state.history.push(payload.reading);
        state.history = state.history.slice(-80);
        updateMetric(payload.reading, payload.stats);
        updateHistoryRows();
        drawChart();
        setBadge("Captura activa", "ok");
    } catch (error) {
        setBadge(error.message, "error");
    } finally {
        state.busy = false;
        captureButton.disabled = false;
    }
}

captureButton.addEventListener("click", capture);
drawChart();
if (state.history.length) {
    updateHistoryRows();
}

capture();
setInterval(capture, 5000);
