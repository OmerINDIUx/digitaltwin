# 🚀 Guía de Despliegue en Hostinger (Digital Twin + Laravel)

Esta guía detalla los pasos para subir tu proyecto a Hostinger para que el Modelo 3D y la base de datos Laravel funcionen correctamente en producción.

## 1. Preparar el Backend (Laravel - `back-api`)

Hostinger suele usar el panel hPanel y el servidor corre Apache.

1.  **Sube los archivos**:
    - Crea una carpeta llamada `back-api` en el directorio raíz de tu hosting (fuera de `public_html` por seguridad).
    - Copia todo el contenido de tu carpeta local `back-api` ahí (excepto `/vendor` y `/node_modules`).
2.  **Configura el archivo `.env`**:
    - Edita el archivo `.env` en el servidor con los datos de tu base de datos de Hostinger (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD).
    - Cambia `APP_ENV=production` y `APP_DEBUG=false`.
    - Cambia `APP_URL=https://tudominio.com/back-api/public`.
3.  **Instala Dependencias**:
    - Entra por SSH a tu Hostinger.
    - Navega a `back-api` y ejecuta: `composer install --no-dev --optimize-autoloader`.
4.  **Base de Datos**:
    - Ejecuta las migraciones: `php artisan migrate --force`.

## 2. Preparar el Frontend (Modelo 3D - Vite)

1.  **Compilar para Producción**:
    - En tu máquina local, ejecuta: `npm run build`.
    - Esto generará una carpeta llamada `dist`.
2.  **Sube los archivos**:
    - Sube el contenido de la carpeta `dist` directamente a la carpeta `public_html` de tu Hostinger.
    - Esto hará que al entrar a `tudominio.com` se cargue el modelo 3D.

## 3. Conexión de Datos (API)

He actualizado `main.js` para que detecte automáticamente el entorno.

- **Importante**: Antes de hacer el `npm run build`, verifica en `main.js` que la variable `API_BASE_URL` apunte a la ruta donde pondrás tu Laravel. Por defecto, he puesto que busque en `/back-api/public`.

## 4. Archivo .htaccess (Estructura recomendada)

Para que Laravel y Vite convivan sin problemas en el mismo dominio de Hostinger (Shared Hosting):

```text
/
├── back-api/              <-- El código de Laravel (Seguro)
└── public_html/           <-- El contenido de la carpeta 'dist' (Frontend)
    ├── index.html         <--- Tu Digital Twin
    ├── assets/
    └── models/
```

### 5. ¿Cómo subirlo a un SUBDOMINIO? (Ej: digitaltwin.tudominio.com)

Si vas a usar un subdominio, los pasos son casi iguales pero con estas diferencias:

1.  **Directorio en Hostinger**: Hostinger creará una carpeta con el nombre de tu subdominio dentro de `public_html` (ej: `public_html/digitaltwin`). Ahí es donde debes subir los archivos de la carpeta `dist`.
2.  **Configuración de API**:
    - Si tu backend también está en el subdominio (`digitaltwin.tudominio.com/back-api`), la variable `API_BASE_URL` en `main.js` seguirá funcionando tal cual está (usando `window.location.origin`).
    - Si quieres un subdominio limpio para la API (ej: `api.tudominio.com`), deberás entrar a `main.js` y cambiar `API_BASE_URL` a esa URL exacta antes de hacer el `npm run build`.
3.  **Laravel .env**: Recuerda cambiar `APP_URL` en el `.env` de producción para que coincida exactamente con la URL de tu subdominio.

### Comandos Útiles (SSH Hostinger):
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan migrate`
