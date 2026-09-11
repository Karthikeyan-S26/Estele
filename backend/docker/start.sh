#!/bin/sh
set -e

export PORT="${PORT:-8080}"

# Render the nginx config with Railway's assigned $PORT — nginx.conf itself
# can't read env vars directly, so this templates it once at container start.
envsubst '${PORT}' < /etc/nginx/templates/nginx.conf.template > /etc/nginx/conf.d/default.conf

# php-fpm workers run as www-data (see docker/www.conf), but the old
# 'php artisan serve' setup ran everything as root, so storage/bootstrap/cache
# were only ever chmod'd, never chowned — and Railway's persistent volume
# (mounted at /app/storage/app) resets ownership at mount time regardless of
# what the image build set anyway. Must run this at container start, after
# the volume is attached, not just as a Dockerfile build step.
chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link || true
php artisan config:cache
php artisan view:cache
php artisan migrate --force
# NOTE: route:cache deliberately NOT run — Filament v5's admin panel routes
# are registered dynamically at boot (resource/page discovery) and are not
# reliably compatible with Laravel's cached route file; caching them here
# caused /admin/login to 500 on first deploy of this hardening pass.

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
