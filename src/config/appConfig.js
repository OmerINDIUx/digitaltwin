const appBasePath = window.location.pathname
  .replace(/\/[^/]*$/, "")
  .replace(/\/dist$/, "");

const isViteDevServer =
  (window.location.hostname === "localhost" ||
    window.location.hostname === "127.0.0.1") &&
  window.location.port &&
  window.location.port !== "80";

export const API_BASE_URL = isViteDevServer
  ? "http://localhost:8000"
  : `${window.location.origin}${appBasePath}/back-api/public`;

export const SENSOR_API_URL = isViteDevServer
  ? `${window.location.protocol}//${window.location.hostname}/mi-proyecto-3d/Sensores/api.php`
  : `${window.location.origin}${appBasePath}/Sensores/api.php`;

export const LATITUDE = 19.4326;
export const LONGITUDE = -99.1332;
