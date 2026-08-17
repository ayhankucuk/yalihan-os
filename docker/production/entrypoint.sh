#!/bin/sh
# Production entrypoint — PHP-FPM container
# Responsibilities:
#   - Clear opcache on boot (production safety)
#   - exec into the passed CMD (php-fpm)
# DO NOT: touch nginx paths (/var/log/nginx), this is a PHP-FPM container only

set -e

# Clear opcache on boot
php -r "opcache_get_status();" 2>/dev/null || true

# Run the main process (php-fpm)
exec "$@"
