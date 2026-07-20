#!/bin/bash
# Standard Laravel production setup (run from project root)
set -e

composer install --no-dev --optimize-autoloader

php artisan storage:link --force
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "Done. Set nginx root to: $(pwd)/public"
