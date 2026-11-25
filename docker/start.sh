#!/bin/sh
set -e

cd /var/www/html

# Render inyecta el puerto en $PORT (10000 por defecto).
PORT="${PORT:-10000}"
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/http.d/default.conf

# APP_URL automatico con el dominio que asigna Render.
if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
    export APP_URL="$RENDER_EXTERNAL_URL"
fi

# Directorios de escritura (tambien cuando se monta un disco en storage/app).
mkdir -p \
    bootstrap/cache \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

chown -R www-data:www-data storage bootstrap/cache

# Migraciones con reintentos (la base puede tardar en aceptar conexiones).
intentos=0
until su-exec www-data php artisan migrate --force --no-interaction; do
    intentos=$((intentos + 1))
    if [ "$intentos" -ge 10 ]; then
        echo "No se pudo conectar a la base de datos. Abortando."
        exit 1
    fi
    echo "Esperando la base de datos... intento $intentos"
    sleep 3
done

# Cache de configuracion/vistas (no se usa route:cache porque hay closures).
su-exec www-data php artisan config:cache
su-exec www-data php artisan view:cache
su-exec www-data php artisan event:cache || true

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
