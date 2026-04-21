# 🚀 Guía de Despliegue: digitaltwin.diseñoygestion.com

Esta guía utiliza la configuración real de tu subdominio en Hostinger.

## 📂 Estructura de Carpetas en el Servidor (Root del Subdominio)

Debes subir los archivos a la ruta `/public_html/digitaltwin` siguiendo este orden:

```text
public_html/
└── digitaltwin/           <--- Raíz de tu subdominio (HOSTING)
    ├── index.html         <--- (Contenido de la carpeta 'dist' de Vite)
    ├── assets/            <--- (Contenido de la carpeta 'dist' de Vite)
    └── back-api/          <--- (Tu carpeta de Laravel completa)
        ├── public/        <--- Tu API vivirá aquí
        ├── .env           <--- ¡Configurar después de subir!
        └── ...
```

---

## 💻 Paso 1. Preparar y Subir el Backend (`back-api`)

1.  **Subida**: Sube la carpeta `back-api` completa a `/public_html/digitaltwin/`.
2.  **Configura el archivo `.env`**:
    *   Crea una base de datos en el panel de Hostinger y anota los datos.
    *   Edita el `.env` en el servidor:
        *   `APP_URL=https://digitaltwin.diseñoygestion.com/back-api/public`
        *   `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (los de Hostinger).
3.  **Instala y Migra (SSH)**:
    ```bash
    cd public_html/digitaltwin/back-api
    composer install --no-dev --optimize-autoloader
    php artisan migrate --force
    ```

---

## 🎨 Paso 2. Preparar y Subir el Frontend (Vite)

1.  **Compilar**: En tu PC, ejecuta `npm run build`.
2.  **Subida**: Sube todo lo que esté **DENTRO** de la carpeta `dist` directamente a `/public_html/digitaltwin/`.

---

## 🔗 Paso 3. Conexión de Datos

**¡No tienes que tocar el código!** He configurado `main.js` para que detecte automáticamente tu subdominio. 
*   Cuando entres a `digitaltwin.diseñoygestion.com`, el sistema sabrá que debe buscar los datos en `/back-api/public`.

---

## 🔒 Paso 4. Protección .htaccess (Recomendado)

Crea un archivo llamado `.htaccess` dentro de la carpeta `back-api` (`/public_html/digitaltwin/back-api/.htaccess`) para proteger tus archivos de configuración y redirigir el tráfico a la carpeta `public` de Laravel.

**Crea este archivo en `/public_html/digitaltwin/back-api/.htaccess`**:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    # Redirigir todas las peticiones a la carpeta public de Laravel
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>

# Bloquear acceso a archivos sensibles
<Files .env>
    Order allow,deny
    Deny from all
</Files>
```

---

### 🛡️ Nota sobre SSL:
Asegúrate de que el certificado **SSL (HTTPS)** esté activo para el subdominio en el panel de Hostinger para que las peticiones a la API sean seguras y no se bloqueen por contenido mixto.

### 📚 Comandos Útiles (SSH Hostinger):
*   Ir a la carpeta: `cd public_html/digitaltwin/back-api`
*   Refrescar caché: `php artisan config:cache` y `php artisan view:clear`
*   Ver logs de error: `tail -f storage/logs/laravel.log`
