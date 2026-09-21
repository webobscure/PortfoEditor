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

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

# The local link is useful for development/fallback storage. Production media
# and exports should use the S3 disk backed by Cloudflare R2.
php artisan storage:link >/dev/null 2>&1 || true
php artisan config:cache
php artisan view:cache
php artisan migrate --force

exec apache2-foreground
