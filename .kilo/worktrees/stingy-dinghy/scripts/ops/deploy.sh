#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"
git pull --ff-only
php -v >/dev/null
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan cache:clear
php artisan route:cache
php artisan view:cache
php artisan config:cache
php artisan ups:health --performance
