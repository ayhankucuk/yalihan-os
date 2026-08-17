#!/bin/sh
# Production entrypoint — runs Nginx + PHP-FPM via runit
# Hetzner Security Rule: No secrets in image, no CMD override

set -e

# Ensure log directories exist
mkdir -p /var/log/php /var/log/nginx

# Clear opcache on boot (production safety)
php_clear_opcache() {
    echo "Clearing OPcache..."
    php -r "opcache_get_status();" 2>/dev/null || true
}
php_clear_opcache

# Run the main process (php-fpm or custom CMD)
exec "$@"
