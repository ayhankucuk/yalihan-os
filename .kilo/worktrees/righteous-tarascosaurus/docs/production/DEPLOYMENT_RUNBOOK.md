# 🚀 Yalıhan AI OS — Production Deployment Runbook

**Version:** 2.1 (Cognitive)  
**Son Güncelleme:** 2026-05-15  
**Stack:** PHP 8.2 / Laravel 11 / MySQL / Redis / Supervisor / Horizon / N8N / Telegram  
**Hedef Sunucu:** Oracle Cloud (168.138.101.124) — Cloudflare Tunnel aktif  
**SAB Uyumu:** Zorunlu — deploy öncesi `php artisan sab:integrity-scan` geçmeli

---

## Ön Koşul Kontrol Listesi

Deploy başlamadan önce tüm kutular işaretlenmeli:

```
[ ] SSH erişimi doğrulandı (ssh user@168.138.101.124)
[ ] Yerel testler geçiyor: php artisan test
[ ] SAB integrity temiz: php artisan sab:integrity-scan
[ ] Bekçi anlamsal denetim temiz: php artisan bekci:audit --all
[ ] SAB.sha256 güncel (checksum eşleşiyor)
[ ] .env production değerleri hazır (aşağıya bakın)
[ ] AI_DRY_RUN=false .env'de ayarlı
[ ] DEEPSEEK_API_KEY gerçek key
[ ] OPENAI_API_KEY gerçek key (VoiceProcessor + FinanceProcessor için zorunlu)
[ ] N8N erişilebilir: https://n8n.yalihanemlak.com.tr
[ ] Telegram bot token ve admin chat ID hazır
```

---

## 1. Environment Konfigürasyonu (.env Production)

```env
# === UYGULAMA ===
APP_ENV=production
APP_DEBUG=false
APP_URL=https://panel.yalihanemlak.com.tr

# === VERİTABANI ===
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yalihan2026
DB_USERNAME=yalihan
DB_PASSWORD=<production-password>

# === CACHE / SESSION / QUEUE ===
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# === AI PROVIDER HİYERARŞİSİ ===
AI_ENABLED=true
AI_DRY_RUN=false
# 1. Birincil: DeepSeek
DEEPSEEK_API_KEY=<gerçek-key>
DEEPSEEK_MODEL=deepseek-chat          # deepseek-chat (V3) veya deepseek-reasoner (R1)
# UYARI: deepseek-v4-flash MEVCUT DEĞİL — kullanma
# 2. Fallback: OpenAI
OPENAI_API_KEY=<gerçek-key>           # FinanceProcessor + VoiceProcessor (Whisper) için zorunlu
# 3. Yerel: Ollama
OLLAMA_HOST=http://ollama.yalihanemlak.internal

# === N8N WORKFLOW AUTOMATION ===
N8N_ENABLED=true
N8N_BASE_URL=https://n8n.yalihanemlak.com.tr
N8N_WEBHOOK_NEW_LISTING=https://n8n.yalihanemlak.com.tr/webhook/d0247957-388e-4b38-8729-f25fb91e63d2
# Kalan 7 webhook — N8N'den kur, buraya ekle:
# N8N_WEBHOOK_HIGH_MATCH=
# N8N_WEBHOOK_DEMAND_FULFILLED=
# N8N_WEBHOOK_CRITICAL_UPDATE=
# N8N_WEBHOOK_TASK_DEADLINE=
# N8N_WEBHOOK_PRICE_CHANGE=
# N8N_WEBHOOK_NEW_DEMAND=
# N8N_WEBHOOK_CHURN_RISK=
# N8N_WEBHOOK_WEEKLY_REPORT=

# === TELEGRAM ===
TELEGRAM_BOT_TOKEN=<gerçek-token>      # @yalihanx_bot
TELEGRAM_ADMIN_CHAT_ID=515406829       # Ayhan Küçük
TELEGRAM_TEAM_CHANNEL_ID=515406829    # Geçici; production'da ayrı channel ID kullan
TELEGRAM_WEBHOOK_URL=https://panel.yalihanemlak.com.tr/api/telegram/webhook
```

---

## 2. Sunucu Kurulum Adımları (İlk Deploy — Task #20-22)

> Bu bölüm yalnızca ilk kurulum için. Sonraki deploy'lar için bölüm 3'e geç.

### 2.1 PHP 8.2 + Nginx + MySQL + Redis + Supervisor Kurulumu (Task #20)

```bash
# PHP 8.2
sudo apt update && sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-mysql php8.2-redis \
    php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath php8.2-intl

# Nginx
sudo apt install -y nginx

# MySQL 8
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Redis
sudo apt install -y redis-server
sudo systemctl enable redis-server

# Supervisor
sudo apt install -y supervisor
sudo systemctl enable supervisor
```

### 2.2 Laravel Projesi Sunucuya Gönder (Task #21)

```bash
# Yerel makineden rsync (ilk deploy)
rsync -avz --exclude='.env' --exclude='node_modules' --exclude='vendor' \
    /Users/macbookpro/dev/yalihan2026/ \
    user@168.138.101.124:/var/www/yalihan2026/
```

### 2.3 Composer + Migrate + Cache + İzinler (Task #22)

```bash
# Sunucuda:
cd /var/www/yalihan2026

composer install --no-dev --optimize-autoloader
cp .env.example .env   # .env'i yukarıdaki değerlerle doldur

php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder  # varsa

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# İzinler
sudo chown -R www-data:www-data /var/www/yalihan2026
sudo chmod -R 755 /var/www/yalihan2026/storage
sudo chmod -R 755 /var/www/yalihan2026/bootstrap/cache
```

---

## 3. Rutin Deploy Prosedürü (Sıfır Kesinti)

```bash
# 1. SAB integrity kontrolü (yerel)
php artisan sab:integrity-scan
php artisan test
# Her ikisi de PASS olmadan devam etme

# 2. Sunucuya kod gönder
rsync -avz --exclude='.env' ... user@168.138.101.124:/var/www/yalihan2026/

# 3. Sunucuda:
cd /var/www/yalihan2026

# Bağımlılıklar
composer install --no-dev --optimize-autoloader

# Bakım modu (migration varsa)
php artisan down

# Migration
php artisan migrate --force

# Cache yenile
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Queue worker'larını yeniden başlat (yeni kodu alması için zorunlu)
php artisan queue:restart

# Bakımdan çık
php artisan up

# 4. Sağlık kontrolü
php artisan projection:health
php artisan bekci:audit --all 🧠
```

---

## 4. Nginx Konfigürasyonu (Task #23)

```nginx
server {
    listen 80;
    server_name panel.yalihanemlak.com.tr;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name panel.yalihanemlak.com.tr;

    root /var/www/yalihan2026/public;
    index index.php;

    # Cloudflare Tunnel üzerinden geliyor — SSL upstream'de
    ssl_certificate /etc/ssl/certs/yalihan.crt;
    ssl_certificate_key /etc/ssl/private/yalihan.key;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }

    # Telegram webhook endpoint — rate limit
    location /api/telegram/webhook {
        limit_req zone=telegram_webhook burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

---

## 5. Supervisor — Laravel Horizon (Task #24)

```ini
# /etc/supervisor/conf.d/yalihan-horizon.conf
[program:yalihan-horizon]
process_name=%(program_name)s
command=php /var/www/yalihan2026/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/yalihan-horizon.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start yalihan-horizon

# Durum kontrolü
sudo supervisorctl status yalihan-horizon
php artisan horizon:status
```

---

## 6. Telegram Webhook Kurulumu (Task #25)

```bash
# Deploy sonrası — tek seferlik
php artisan telegram:set-webhook

# Doğrulama
curl https://api.telegram.org/bot<TOKEN>/getWebhookInfo

# Beklenen çıktı:
# "url": "https://panel.yalihanemlak.com.tr/api/telegram/webhook"
# "pending_update_count": 0
```

---

## 7. N8N Workflow Kurulumu (Task #27)

N8N arayüzü: `https://n8n.yalihanemlak.com.tr`

Kurulacak 8 workflow (1 hazır, 7 kalan):

| # | Workflow | Webhook Key | Durum |
|---|---|---|---|
| 1 | Yeni İlan Bildirimi | `N8N_WEBHOOK_NEW_LISTING` | ✅ Kurulu |
| 2 | Yüksek Eşleşme | `N8N_WEBHOOK_HIGH_MATCH` | ❌ Eksik |
| 3 | Talep Karşılandı | `N8N_WEBHOOK_DEMAND_FULFILLED` | ❌ Eksik |
| 4 | Kritik Güncelleme | `N8N_WEBHOOK_CRITICAL_UPDATE` | ❌ Eksik |
| 5 | Görev Deadline | `N8N_WEBHOOK_TASK_DEADLINE` | ❌ Eksik |
| 6 | Fiyat Değişikliği | `N8N_WEBHOOK_PRICE_CHANGE` | ❌ Eksik |
| 7 | Yeni Talep | `N8N_WEBHOOK_NEW_DEMAND` | ❌ Eksik |
| 8 | Churn Risk | `N8N_WEBHOOK_CHURN_RISK` | ❌ Eksik |

Her workflow kurulunca webhook URL'yi `.env`'e ekle ve `php artisan config:cache` çalıştır.

---

## 8. Operasyonel İzleme

### Sağlık Kontrol Komutları

```bash
# Genel sistem sağlığı
php artisan projection:health
php artisan bekci:run
php artisan horizon:status

# Queue durumu
php artisan queue:monitor default,ai,telegram,governance

# Governance telemetri
php artisan governance:health-check

# DLQ kontrolü
php artisan projection:dlq:replay --dry-run
```

### İzleme Endpointleri

| Endpoint | Amaç |
|---|---|
| `/advisor/analytics` | AI telemetri dashboard |
| `/horizon` | Queue ve job izleme |
| `storage/logs/laravel.log` | Uygulama logları |

### Telemetri Tabloları (MySQL)

```sql
-- AI sorgu analitik
SELECT * FROM ai_query_telemetry ORDER BY created_at DESC LIMIT 50;

-- Fiyatlandırma feedback
SELECT * FROM valuation_signal_logs ORDER BY created_at DESC LIMIT 20;

-- Hata logları
SELECT intent_type, COUNT(*) FROM ai_query_failures GROUP BY intent_type;
```

---

## 9. Güvenlik

| Alan | Kural |
|---|---|
| AI endpoint throttle | 60 istek/dakika/IP |
| Telegram webhook | Yalnızca Telegram IP aralığından kabul |
| CSRF | Tüm panel ve web formlarında zorunlu |
| DB yazma | Yalnızca Repository katmanından (SAB Rule #2) |
| Observer bypass | Yasak (SAB Rule #3) |
| Silent catch | Yasak — Fail-Fast zorunlu (SAB Rule #4) |
| Cross-tenant erişim | En ağır ihlal (SAB Rule #1) |

---

## 10. Sorun Giderme

| Belirti | Kontrol | Aksiyon |
|---|---|---|
| AI cevap vermiyor | `AI_DRY_RUN` değeri | `.env` → `AI_DRY_RUN=false` + `config:cache` |
| DeepSeek 400 hatası | Model adı | `DEEPSEEK_MODEL=deepseek-chat` (v4-flash değil) |
| Telegram webhook çalışmıyor | Bot token | `telegram:set-webhook` yeniden çalıştır |
| N8N tetiklenmiyor | `N8N_ENABLED` | `true` + webhook URL'ler dolu mu? |
| Queue işlenmiyor | Horizon | `supervisorctl restart yalihan-horizon` |
| Yüksek gecikme (>2s) | Redis/AI bağlantısı | `redis-cli ping`, AI provider latency |
| Tüm intents UNKNOWN | Validator | `ai_query_failures` tablosunu incele |
| "Engine Unavailable" | Servis | İlgili servis log'larını kontrol et |
| Projection hatalı | DLQ | `php artisan projection:dlq:replay` |

---

## 11. Rollback Prosedürü

```bash
# 1. Maintenance modu aç
php artisan down

# 2. Önceki commit'e dön
git checkout <previous-commit-hash>

# 3. Bağımlılıkları yenile
composer install --no-dev --optimize-autoloader

# 4. Migration geri al (gerekirse)
php artisan migrate:rollback

# 5. Cache temizle ve yenile
php artisan optimize:clear
php artisan config:cache

# 6. Queue yeniden başlat
php artisan queue:restart

# 7. Servisi aç
php artisan up
```

---

## 12. SAB Uyum Doğrulaması (Her Deploy Sonrası)

```bash
php artisan sab:integrity-scan    # Tüm SAB kuralları
php artisan sab:guard             # Guard kontrol
php artisan quality:gate          # Kalite kapısı
php artisan bekci:audit --all     # Bekçi anlamsal tarama
php artisan test                  # Test suite

# Hepsi PASS ise:
# PRODUCTION SEAL: ACTIVE ✅
```

---

**Runbook Versiyonu:** 2.1 (Cognitive)  
**SAB Uyumu:** v1.1.0 (Cognitive Seal)  
**Sonraki İnceleme:** 2026-06-01
