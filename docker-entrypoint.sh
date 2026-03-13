#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/html"
cd "$APP_DIR"

if [ -f .env ]; then
    set -a
    # shellcheck source=/dev/null
    source .env
    set +a
fi

WWWUSER=${WWWUSER:-1000}
WWWGROUP=${WWWGROUP:-1000}

mkdir -p storage bootstrap/cache
chown -R "$WWWUSER:$WWWGROUP" storage bootstrap/cache || true
chmod -R ug+rw storage bootstrap/cache || true

if [ -f composer.json ] && [ ! -f vendor/autoload.php ]; then
    composer install --ignore-platform-reqs
fi

if [ -f package.json ] && [ ! -d node_modules ]; then
    npm install
    npm run build
fi

APP_KEY_VALUE="${APP_KEY:-}"
if [ -z "$APP_KEY_VALUE" ]; then
    php artisan key:generate --force
fi

if [ ! -L public/storage ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

DB_HOST=${DB_HOST:-mysql}
DB_PORT=${DB_PORT:-3306}
DB_USERNAME=${DB_USERNAME:-sail}
DB_PASSWORD=${DB_PASSWORD:-password}

if command -v mysqladmin >/dev/null 2>&1; then
    echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."
    until mysqladmin ping -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" --silent; do
        sleep 2
    done
fi

php artisan migrate --force

exec /usr/local/bin/start-container "$@"
