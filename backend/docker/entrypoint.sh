#!/bin/sh
set -e

# APP_KEY estable: se genera una sola vez y se persiste en el volumen de storage,
# para no invalidar las sesiones en cada reinicio del contenedor.
if [ -z "$APP_KEY" ]; then
    APP_KEY_FILE="storage/app_key"
    if [ -f "$APP_KEY_FILE" ]; then
        APP_KEY="$(cat "$APP_KEY_FILE")"
    else
        APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
        echo "$APP_KEY" > "$APP_KEY_FILE"
    fi
    export APP_KEY
fi

# Migraciones de base de datos (idempotentes). Desactívalas con RUN_MIGRATIONS=false.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

exec "$@"
