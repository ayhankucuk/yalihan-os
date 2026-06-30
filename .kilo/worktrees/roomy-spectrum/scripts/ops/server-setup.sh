#!/bin/bash
# =============================================================================
# Yalıhan Emlak — Hermes Sunucu Kurulum Scripti
# Tarih: 2026-06-24 | Ubuntu 22.04 LTS
# Stack: PHP 8.2 + Nginx + MySQL 8 + Redis + Supervisor + Composer + Node 20
# =============================================================================

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_step() { echo -e "\n${BLUE}▶ $1${NC}"; }
log_ok()   { echo -e "${GREEN}✅ $1${NC}"; }
log_warn() { echo -e "${YELLOW}⚠️  $1${NC}"; }
log_err()  { echo -e "${RED}❌ $1${NC}"; exit 1; }

# ─────────────────────────────────────────────
# 1. APT HAZIRLIK
# ─────────────────────────────────────────────
log_step "1/10 — APT güncelleniyor..."
export DEBIAN_FRONTEND=noninteractive
sudo apt-get update -qq
sudo apt-get upgrade -y -qq
sudo apt-get install -y -qq \
    curl wget gnupg2 ca-certificates lsb-release \
    software-properties-common apt-transport-https \
    unzip zip git acl ufw fail2ban
log_ok "APT hazır"

# ─────────────────────────────────────────────
# 2. PHP 8.2
# ─────────────────────────────────────────────
log_step "2/10 — PHP 8.2 kuruluyor..."
sudo add-apt-repository -y ppa:ondrej/php 2>/dev/null
sudo apt-get update -qq
sudo apt-get install -y -qq \
    php8.2 php8.2-fpm php8.2-cli \
    php8.2-mysql php8.2-pgsql php8.2-sqlite3 \
    php8.2-redis php8.2-mbstring php8.2-xml \
    php8.2-curl php8.2-zip php8.2-bcmath \
    php8.2-gd php8.2-intl php8.2-tokenizer \
    php8.2-fileinfo php8.2-dom php8.2-opcache
sudo systemctl enable php8.2-fpm
sudo systemctl start php8.2-fpm
php -v | head -1
log_ok "PHP 8.2 kuruldu"

# ─────────────────────────────────────────────
# 3. Nginx
# ─────────────────────────────────────────────
log_step "3/10 — Nginx kuruluyor..."
sudo apt-get install -y -qq nginx
sudo systemctl enable nginx
sudo systemctl start nginx
nginx -v 2>&1
log_ok "Nginx kuruldu"

# ─────────────────────────────────────────────
# 4. MySQL 8
# ─────────────────────────────────────────────
log_step "4/10 — MySQL 8 kuruluyor..."
sudo apt-get install -y -qq mysql-server
sudo systemctl enable mysql
sudo systemctl start mysql

# Root şifresiz erişim için (production'da sonra güvenlik ayarı yapılacak)
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'Yalihan2026!Secure';" 2>/dev/null || true
sudo mysql -u root -pYalihan2026!Secure -e "
  CREATE DATABASE IF NOT EXISTS yalihan2026 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER IF NOT EXISTS 'yalihan'@'localhost' IDENTIFIED BY 'Yalihan2026!DB';
  GRANT ALL PRIVILEGES ON yalihan2026.* TO 'yalihan'@'localhost';
  FLUSH PRIVILEGES;
" 2>/dev/null
mysql --version
log_ok "MySQL 8 kuruldu — DB: yalihan2026, USER: yalihan"

# ─────────────────────────────────────────────
# 5. Redis
# ─────────────────────────────────────────────
log_step "5/10 — Redis kuruluyor..."
sudo apt-get install -y -qq redis-server
# Bind sadece localhost
sudo sed -i 's/^bind 127.0.0.1 -::1/bind 127.0.0.1/' /etc/redis/redis.conf 2>/dev/null || true
sudo systemctl enable redis-server
sudo systemctl start redis-server
redis-cli ping
log_ok "Redis kuruldu"

# ─────────────────────────────────────────────
# 6. Supervisor
# ─────────────────────────────────────────────
log_step "6/10 — Supervisor kuruluyor..."
sudo apt-get install -y -qq supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
supervisord --version
log_ok "Supervisor kuruldu"

# ─────────────────────────────────────────────
# 7. Composer
# ─────────────────────────────────────────────
log_step "7/10 — Composer kuruluyor..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --quiet
composer --version
log_ok "Composer kuruldu"

# ─────────────────────────────────────────────
# 8. Node.js 20 + NPM
# ─────────────────────────────────────────────
log_step "8/10 — Node.js 20 kuruluyor..."
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash - 2>/dev/null
sudo apt-get install -y -qq nodejs
node --version
npm --version
log_ok "Node.js 20 kuruldu"

# ─────────────────────────────────────────────
# 9. PHP.INI Production Ayarları
# ─────────────────────────────────────────────
log_step "9/10 — PHP production ayarları yapılandırılıyor..."
sudo tee /etc/php/8.2/fpm/conf.d/99-yalihan.ini > /dev/null <<'PHPINI'
; Yalihan Production PHP Settings
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 120
max_input_vars = 3000
memory_limit = 256M
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 60
opcache.validate_timestamps = 0
expose_php = Off
PHPINI
sudo systemctl restart php8.2-fpm
log_ok "PHP.ini ayarlandı"

# ─────────────────────────────────────────────
# 10. /var/www/yalihan2026 dizini + izinler
# ─────────────────────────────────────────────
log_step "10/10 — Web dizini ve izinler hazırlanıyor..."
sudo mkdir -p /var/www/yalihan2026
sudo chown -R ubuntu:www-data /var/www/yalihan2026
sudo chmod -R 775 /var/www/yalihan2026
# Ubuntu kullanıcısını www-data grubuna ekle
sudo usermod -aG www-data ubuntu
log_ok "Dizin hazır: /var/www/yalihan2026"

# ─────────────────────────────────────────────
# ÖZET
# ─────────────────────────────────────────────
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${GREEN}🎉 Sunucu Kurulumu Tamamlandı!${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Servis durumları:"
systemctl is-active php8.2-fpm  && echo "  ✅ PHP 8.2-FPM    aktif" || echo "  ❌ PHP 8.2-FPM    hata"
systemctl is-active nginx        && echo "  ✅ Nginx          aktif" || echo "  ❌ Nginx          hata"
systemctl is-active mysql        && echo "  ✅ MySQL          aktif" || echo "  ❌ MySQL          hata"
systemctl is-active redis-server && echo "  ✅ Redis          aktif" || echo "  ❌ Redis          hata"
systemctl is-active supervisor   && echo "  ✅ Supervisor     aktif" || echo "  ❌ Supervisor     hata"
echo ""
echo "DB Bilgileri:"
echo "  Veritabanı : yalihan2026"
echo "  Kullanıcı  : yalihan"
echo "  Şifre      : Yalihan2026!DB"
echo "  Root Şifre : Yalihan2026!Secure"
echo ""
echo "Sonraki adım: git clone + .env + migrate"
echo ""
