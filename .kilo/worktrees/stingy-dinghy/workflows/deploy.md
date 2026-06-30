# Deploy Workflow

> Yalıhan Emlak — Production deploy prosedürü

## Sunucu

- **Host**: Hetzner CX33
- **IP**: 157.180.116.63
- **SSH**: `ssh ubuntu@157.180.116.63`

## Deploy Adımları

```bash
# 1. SSH bağlantısı
ssh ubuntu@157.180.116.63

# 2. PHP 8.2 + Nginx + MySQL + Redis + Supervisor kurulumu
# Bkz: ROADMAP.md #20

# 3. Laravel dosyalarını rsync ile gönder
rsync -avz --exclude='vendor' --exclude='node_modules' . ubuntu@157.180.116.63:/var/www/yalihan/

# 4. Composer install
composer install --no-dev --optimize-autoloader

# 5. Migration
php artisan migrate --force

# 6. Config cache
php artisan config:cache
php artisan route:cache

# 7. Horizon başlat
php artisan horizon:start  # veya supervisor

# 8. Telegram webhook
php artisan telegram:set-webhook
```

## Servisler

| Servis | URL | Durum |
|--------|-----|-------|
| N8N | https://n8n.yalihanemlak.com.tr | ✅ Aktif |
| Panel | https://panel.yalihanemlak.com.tr | ⏳ Deploy bekliyor |

## Quality Gate

Deploy öncesi:

```bash
php artisan test
php artisan sab:integrity-scan
./scripts/guards/quality-gate.sh
```
