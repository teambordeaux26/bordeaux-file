#!/bin/bash
set -e

echo "Running Hostinger post-deploy setup..."

php artisan storage:link --force || true
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

chmod -R 775 storage bootstrap/cache || true

echo "Post-deploy setup complete."
