#!/bin/bash
# Standard Laravel production deploy
set -e

APP_DIR="${APP_DIR:-/var/www/cmoai}"
cd "$APP_DIR"

git pull origin main

composer install --no-dev --optimize-autoloader --no-interaction

php artisan migrate --force
php artisan storage:link --force
php artisan optimize
php artisan queue:restart

if command -v supervisorctl &> /dev/null; then
    sudo supervisorctl restart cmoai-worker:* 2>/dev/null || true
fi

sudo systemctl reload php8.3-fpm 2>/dev/null || true

echo "Deploy complete."
