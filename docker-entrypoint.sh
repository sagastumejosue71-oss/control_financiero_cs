#!/bin/sh
set -e

echo "[entrypoint] 1/6 - verificando APP_KEY..."
if [ -z "$APP_KEY" ]; then
    echo "ERROR: falta la variable de entorno APP_KEY."
    echo "Genérala una sola vez con 'php artisan key:generate --show' y ponla fija"
    echo "en la configuración de tu hosting. NUNCA se genera sola aquí adentro:"
    echo "si cambiara en cada arranque, todos los datos financieros ya cifrados"
    echo "en la base de datos quedarían ilegibles para siempre."
    exit 1
fi
echo "[entrypoint] APP_KEY presente (longitud: ${#APP_KEY})"

echo "[entrypoint] 2/6 - probando conexión a la base de datos..."
php artisan db:show || echo "[entrypoint] ADVERTENCIA: db:show falló, pero seguimos para ver más detalle"

echo "[entrypoint] 3/6 - package:discover..."
php artisan package:discover --ansi

echo "[entrypoint] 4/6 - migrate --force..."
php artisan migrate --force

echo "[entrypoint] 5/6 - cacheando config/rutas/vistas..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[entrypoint] 6/6 - arrancando Apache..."
exec apache2-foreground
