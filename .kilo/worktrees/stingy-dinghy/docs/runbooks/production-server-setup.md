# Production Server Setup — Yalıhan Emlak

**Tarih:** 2026-04-14
**Stack:** PHP 8.1+ | Nginx | MySQL 8 | Redis | Node 18+ | Composer | Supervisor (Horizon)
**OS:** Ubuntu 22.04 LTS (önerilen)
**Production IP:** `168.138.101.124`
**Panel Domain:** `panel.yalihanemlak.com.tr`
**N8N Domain:** `n8n.yalihanemlak.com.tr`

---

## Gereksinimler

| Bileşen | Minimum | Önerilen |
|---------|---------|----------|
| PHP | 8.1 | 8.2 (Prod) |
| Node.js | 18 | 20 LTS |
| MySQL | 8.0 | 8.0+ |
| Redis | 6.0 | 7.0+ |
| RAM | 2 GB | 4 GB |
| Disk | 20 GB | 40 GB |

---

## Adım 1 — Sistem Güncelleme

```bash
sudo apt update && sudo apt upgrade -y
```

---

## Adım 2 — PHP 8.2 + Eklentiler

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

sudo apt install -y php8.2 php8.2-fpm php8.2-cli \
  php8.2-mysql php8.2-redis php8.2-curl php8.2-gd \
  php8.2-mbstring php8.2-xml php8.2-zip php8.2-bcmath \
  php8.2-intl php8.2-readline php8.2-tokenizer \
  php8.2-fileinfo php8.2-dom php8.2-opcache

# Doğrula
php -v
php -m | grep -E "redis|mysql|gd|mbstring|xml|zip|bcmath|intl"
```

---

## Adım 3 — Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

---

## Adım 4 — Node.js 20 LTS + npm

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

---

## Adım 5 — Nginx

```bash
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

---

## Adım 6 — MySQL 8

> Eğer MySQL ayrı sunucuda veya managed DB kullanıyorsan bu adımı atla.

```bash
sudo apt install -y mysql-server
sudo systemctl enable mysql
sudo systemctl start mysql

# Güvenlik ayarı
sudo mysql_secure_installation

# Veritabanı + kullanıcı oluştur
sudo mysql -u root <<EOF
CREATE DATABASE yalihan2026 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'yalihan'@'localhost' IDENTIFIED BY 'GÜÇLÜ_ŞİFRE_BURAYA';
GRANT ALL PRIVILEGES ON yalihan2026.* TO 'yalihan'@'localhost';
FLUSH PRIVILEGES;
EOF
```

---

## Adım 7 — Redis

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
redis-cli ping  # PONG dönmeli
```

---

## Adım 8 — Supervisor (Horizon için)

```bash
sudo apt install -y supervisor
sudo systemctl enable supervisor
```

---

## Adım 9 — Repo Clone

```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone git@github.com:ayhankucuk/yalihan2026.git yalihan2026
sudo chown -R www-data:www-data yalihan2026
cd yalihan2026
```

> SSH key yoksa: `ssh-keygen -t ed25519` → public key'i GitHub'a ekle.
> Veya HTTPS ile: `git clone https://github.com/ayhankucuk/yalihan2026.git yalihan2026`

---

## Adım 10 — .env Konfigürasyonu

```bash
cp .env.example .env
nano .env
```

**Kritik .env değerleri:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://DOMAIN

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yalihan2026
DB_USERNAME=yalihan
DB_PASSWORD=GÜÇLÜ_ŞİFRE_BURAYA

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

---

## Adım 11 — Composer + npm Install

```bash
cd /var/www/yalihan2026

composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
```

---

## Adım 12 — Laravel Setup

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Adım 13 — Nginx Site Config

```bash
sudo nano /etc/nginx/sites-available/yalihan2026
```

İçerik:

```nginx
server {
    listen 80;
    server_name DOMAIN_BURAYA;
    root /var/www/yalihan2026/public;

    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 64M;
}
```

Aktifleştir:

```bash
sudo ln -s /etc/nginx/sites-available/yalihan2026 /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

---

## Adım 14 — SSL (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d DOMAIN_BURAYA
```

---

## Adım 15 — Horizon (Queue Worker)

```bash
sudo nano /etc/supervisor/conf.d/yalihan-horizon.conf
```

İçerik:

```ini
[program:yalihan-horizon]
process_name=%(program_name)s
command=php /var/www/yalihan2026/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/yalihan2026/storage/logs/horizon.log
stopwaitsecs=3600
```

Aktifleştir:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start yalihan-horizon
```

---

## Adım 16 — Dosya İzinleri

```bash
cd /var/www/yalihan2026
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## Adım 17 — Cron (Laravel Scheduler)

```bash
sudo crontab -u www-data -e
```

Ekle:

```
* * * * * cd /var/www/yalihan2026 && php artisan schedule:run >> /dev/null 2>&1
```

---

## Adım 18 — Doğrulama

```bash
# Sistem kontrolü
whoami && hostname && php -v

# Laravel kontrolü
cd /var/www/yalihan2026
php artisan --version
php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"

# Son commit
git log -1 --oneline
# Beklenen: f2f10a8

# Servisler
sudo systemctl status nginx php8.3-fpm redis-server mysql supervisor

# HTTP testi
curl -I http://localhost
```

---

## Adım 19 — Deploy Script Testi

```bash
cd /var/www/yalihan2026
ls scripts/deploy-production.sh
# İlk deploy:
./scripts/deploy-production.sh
# Backup sorusuna: evet
```

---

## Sunucu Hazır Teyid Formatı

Tamamlandığında şu bilgiyi paylaş:

```
Production server hazır:
- host/ip: ...
- ssh user: ...
- repo path: /var/www/yalihan2026
- php version: ...
- git commit: f2f10a8
- app path doğrulandı: yes
```

→ Deploy akışına geçilir.
