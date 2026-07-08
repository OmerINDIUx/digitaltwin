# Despliegue en Hostinger: UtopiaJapon

Esta guia deja el proyecto Laravel funcionando dentro de la carpeta `public_html/UtopiaJapon` y conectado a la base `u119343571_UtopiaJapon`.

## 1. Preparar el servidor

Entra por SSH y ve a la carpeta del subdominio o sitio:

```bash
cd ~/domains/indixlab.com/public_html/UtopiaJapon
```

Si esa carpeta ya tiene archivos viejos del proyecto, respalda lo necesario antes de reemplazarlos.

## 2. Bajar el codigo desde Git

Si la carpeta esta vacia:

```bash
git clone TU_REPO_GIT .
```

Si ya es un clon del repo:

```bash
git pull origin main
```

Si trabajan con otra rama, cambia `main` por la rama correcta.

## 3. Instalar dependencias de PHP

```bash
composer install --no-dev --optimize-autoloader
```

Si Hostinger no tiene Composer global, usa el binario que te muestre su panel o su ruta local.

## 4. Crear el archivo `.env`

Duplica el ejemplo:

```bash
cp .env.example .env
```

Edita `.env` y deja, como minimo, estos valores:

```env
APP_NAME="UtopiaJapon"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://TU-SUBDOMINIO.indixlab.com
APP_TIMEZONE=America/Mexico_City

ADMIN_EMAIL=admin@digitaltwin.mx
ADMIN_PASSWORD=CAMBIAR_ESTA_CLAVE

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u119343571_UtopiaJapon
DB_USERNAME=u119343571_UtopiaJapon
DB_PASSWORD=COLOCAR_AQUI_LA_CLAVE_PROVISIONAL_Y_LUEGO_ROTARLA

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Si el subdominio realmente no apunta a la raiz de `UtopiaJapon` y se abre como carpeta, usa esta variante:

```env
APP_URL=https://indixlab.com/UtopiaJapon
```

No deje la clave provisional guardada en el repo. Pongala solo en el `.env` del servidor y cambienla despues de validar la conexion.

## 5. Generar clave de la app

```bash
php artisan key:generate
```

Si ya existe `APP_KEY` en produccion y el sistema ya estaba en uso, no la regeneres.

## 6. Crear tablas

```bash
php artisan migrate --force
```

Si tambien quieres cargar catalogos iniciales:

```bash
php artisan db:seed --force
```

Usa seeders solo si quieres datos base y sabes que no van a duplicar informacion existente.

## 7. Compilar frontend

Este proyecto usa Vite. En el servidor:

```bash
npm install
npm run build
```

Si prefieren no compilar en Hostinger, tambien pueden subir ya generada la carpeta `public/build`.

## 8. Ajustar permisos

Laravel necesita escritura en `storage` y `bootstrap/cache`:

```bash
chmod -R 775 storage bootstrap/cache
```

## 9. Optimizar configuracion

```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 10. Subir el archivo 3D obligatorio

El mapa 3D depende de este archivo:

```text
public/japonutopia_capasrenovadas.glb
```

Ese archivo no esta hoy dentro del repositorio, asi que hay que copiarlo manualmente al servidor en esa ruta exacta.

Sin ese archivo, la vista `utopia-japon/mapa-3d` no va a cargar el modelo.

## 11. Probar rutas principales

Revisa estas URLs:

1. `https://TU-SUBDOMINIO.indixlab.com/`
2. `https://TU-SUBDOMINIO.indixlab.com/panel`
3. `https://TU-SUBDOMINIO.indixlab.com/utopia-japon/mapa-3d`
4. `https://TU-SUBDOMINIO.indixlab.com/admin/login`

Si usan la variante por carpeta, agrega `/UtopiaJapon` antes de cada ruta.

## 12. Flujo de actualizacion despues del primer despliegue

Cada vez que suban cambios:

```bash
cd ~/domains/indixlab.com/public_html/UtopiaJapon
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Checklist rapido

1. El repo esta en `public_html/UtopiaJapon`.
2. `.env` apunta a `u119343571_UtopiaJapon`.
3. La clave de base de datos se puso solo en servidor.
4. `storage` y `bootstrap/cache` tienen permisos.
5. Las migraciones corrieron.
6. Existe `public/japonutopia_capasrenovadas.glb`.
7. El panel `/admin/login` abre correctamente.
