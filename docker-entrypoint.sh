#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
    echo "ERROR: falta la variable de entorno APP_KEY."
    echo "Genérala una sola vez con 'php artisan key:generate --show' y ponla fija"
    echo "en la configuración de tu hosting. NUNCA se genera sola aquí adentro:"
    echo "si cambiara en cada arranque, todos los datos financieros ya cifrados"
    echo "en la base de datos quedarían ilegibles para siempre."
    exit 1
fi

php artisan package:discover --ansi
php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
