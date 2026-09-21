#!/bin/sh
set -eu

render_port="${PORT:-10000}"

# Render supplies the public port at runtime. Apache's image defaults to 80.
sed -ri "s/^Listen [0-9]+$/Listen ${render_port}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${render_port}>/" /etc/apache2/sites-available/000-default.conf

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is required. Generate one with: php artisan key:generate --show" >&2
    exit 1
fi

# Laravel's default connection is sqlite. On a container with an ephemeral,
# root-owned filesystem that default does not fail at boot: migrations run as
# root, then every request that touches the session hits "attempt to write a
# readonly database" and returns 500. Refusing to start is far easier to
# diagnose than a service that reports itself live and then 500s.
db_connection="${DB_CONNECTION:-}"

if [ "${APP_ENV:-production}" = 'production' ] && [ "$db_connection" != 'pgsql' ]; then
    echo "DB_CONNECTION must be 'pgsql' in production (current: '${db_connection:-unset}')." >&2
    echo "Set DB_CONNECTION=pgsql, DB_URL=<connection string> and DB_SSLMODE=require" >&2
    echo "in the Render service environment, then redeploy." >&2
    exit 1
fi

if [ "$db_connection" = 'pgsql' ] && [ -z "${DB_URL:-}" ] && [ -z "${DB_HOST:-}" ]; then
    echo "DB_CONNECTION=pgsql needs either DB_URL or DB_HOST/DB_DATABASE credentials." >&2
    exit 1
fi

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

# Non-production runs of this image may legitimately use sqlite. Apache serves
# as www-data, so both the file and its directory have to be writable by it.
if [ "$db_connection" = 'sqlite' ]; then
    mkdir -p database
    touch database/database.sqlite
    chown -R www-data:www-data database
fi

# The local link is useful for development/fallback storage. Production media
# and exports should use the S3 disk backed by Cloudflare R2.
php artisan storage:link >/dev/null 2>&1 || true
php artisan config:cache
php artisan view:cache
php artisan migrate --force

exec apache2-foreground
