#!/bin/bash
set -e

# Writable runtime dirs
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache

php artisan migrate --force
php artisan storage:link || true

# Confirm the Vite manifest made it into the image
ls -la public/build || echo "WARNING: public/build missing — dashboard will be unstyled"

php artisan serve --host=0.0.0.0 --port=${PORT:-8080}