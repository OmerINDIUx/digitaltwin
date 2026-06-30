# Sensores

Modulo independiente para capturar lecturas del Arduino UNO R4 WiFi y almacenarlas en una base propia antes de integrarlas al sistema 3D.

## Uso local

Con Laragon, abre:

```text
http://localhost/mi-proyecto-3d/Sensores/
```

La pantalla consulta las fuentes configuradas en `config.php` y guarda una lectura cada 5 segundos. La fuente principal actual es:

```text
https://sensores.xn--diseoygestion-lkb.com/api/sensores_seguro.php?limit=100
```

Si el servidor no responde, el lector intenta la pagina raiz del dominio y despues la IP local del Arduino.

La llave del endpoint seguro se configura en el `.env` de la raiz:

```text
SENSORES_API_KEY=tu_llave_larga
```

## API

```text
GET /Sensores/api.php?action=poll
GET /Sensores/api.php?action=latest
GET /Sensores/api.php?action=history&limit=80
GET /Sensores/api.php?action=stats
POST /Sensores/api.php
```

El `POST` acepta JSON con estos campos:

```json
{
  "light_value": 135,
  "light_state": "CLARO",
  "temperature_c": 22.0,
  "humidity_percent": 45.1,
  "sound_d0": 0,
  "sound_a1": 0,
  "object_state": "SIN OBJETO"
}
```

## Base propia

El modulo usa SQLite si el PHP del servidor tiene el driver activo. Si no lo tiene, guarda automaticamente en:

```text
Sensores/storage/sensores.jsonl
```

La API no cambia entre un modo y otro.
