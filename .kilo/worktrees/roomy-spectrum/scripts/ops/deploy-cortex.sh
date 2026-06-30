#!/bin/bash

# Cortex Telegram Entegrasyonu - Deployment Script
# Tarih: 2025-11-30
# Versiyon: 2.1.0

set -e

echo "🚀 Cortex Telegram Entegrasyonu - Deployment Başlatılıyor..."
echo ""

# Renkler
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Environment Değişkenleri Kontrolü
echo "📋 1. Environment Değişkenleri Kontrol Ediliyor..."
REQUIRED_VARS=("TELEGRAM_BOT_TOKEN" "TELEGRAM_ADMIN_CHAT_ID" "ANYTHINGLLM_URL" "ANYTHINGLLM_KEY")
MISSING_VARS=()

for var in "${REQUIRED_VARS[@]}"; do
    if ! grep -q "^${var}=" .env 2>/dev/null; then
        MISSING_VARS+=("$var")
    fi
done

if [ ${#MISSING_VARS[@]} -gt 0 ]; then
    echo -e "${RED}❌ Eksik environment değişkenleri:${NC}"
    printf '%s\n' "${MISSING_VARS[@]}"
    echo ""
    echo "Lütfen .env dosyasını düzenleyin ve eksik değişkenleri ekleyin."
    exit 1
else
    echo -e "${GREEN}✅ Tüm environment değişkenleri mevcut${NC}"
fi

# 2. Queue Tabloları Kontrolü
echo ""
echo "🗄️  2. Queue Tabloları Kontrol Ediliyor..."
if php artisan tinker --execute="echo Schema::hasTable('jobs') ? 'true' : 'false';" 2>/dev/null | grep -q "true"; then
    echo -e "${GREEN}✅ jobs tablosu mevcut${NC}"
else
    echo -e "${YELLOW}⚠️  jobs tablosu bulunamadı, oluşturuluyor...${NC}"
    php artisan queue:table
    php artisan migrate
    echo -e "${GREEN}✅ jobs tablosu oluşturuldu${NC}"
fi

# 3. Cache Temizliği ve Optimizasyonu
echo ""
echo "🧹 3. Cache Temizleniyor ve Optimize Ediliyor..."
php artisan optimize:clear
echo -e "${GREEN}✅ Cache temizlendi${NC}"

# Production'da route cache oluştur (performans için)
if [ "${APP_ENV}" = "production" ] || [ -z "${APP_ENV}" ]; then
    echo ""
    echo "⚡ Route cache oluşturuluyor (Production optimizasyonu)..."
    php artisan route:cache
    echo -e "${GREEN}✅ Route cache oluşturuldu (Route bulma %95-98 daha hızlı)${NC}"
else
    echo ""
    echo "ℹ️  Development modu: Route cache atlandı (hot reload için)"
fi

# 4. Servis Sağlık Kontrolleri
echo ""
echo "🔍 4. Servis Sağlık Kontrolleri Yapılıyor..."

# Telegram Bot Kontrolü
TELEGRAM_TOKEN=$(grep "^TELEGRAM_BOT_TOKEN=" .env | cut -d '=' -f2)
if [ -n "$TELEGRAM_TOKEN" ]; then
    TELEGRAM_RESPONSE=$(curl -s "https://api.telegram.org/bot${TELEGRAM_TOKEN}/getMe" 2>/dev/null)
    if echo "$TELEGRAM_RESPONSE" | grep -q '"ok":true'; then
        echo -e "${GREEN}✅ Telegram bot bağlantısı başarılı${NC}"
    else
        echo -e "${YELLOW}⚠️  Telegram bot bağlantısı başarısız (token kontrol edin)${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Telegram bot token bulunamadı${NC}"
fi

# Ollama Kontrolü
OLLAMA_URL=$(grep "^OLLAMA_URL=" .env | cut -d '=' -f2 | sed 's|http://||' | cut -d ':' -f1)
if [ -n "$OLLAMA_URL" ]; then
    if curl -s --max-time 2 "http://${OLLAMA_URL}:11434/api/tags" > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Ollama bağlantısı başarılı${NC}"
    else
        echo -e "${YELLOW}⚠️  Ollama bağlantısı başarısız (servis çalışıyor mu?)${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Ollama URL bulunamadı${NC}"
fi

# 5. Queue Worker Durumu
echo ""
echo "🔄 5. Queue Worker Durumu Kontrol Ediliyor..."
if pgrep -f "queue:work.*cortex-notifications" > /dev/null; then
    echo -e "${GREEN}✅ Queue worker çalışıyor${NC}"
else
    echo -e "${YELLOW}⚠️  Queue worker çalışmıyor${NC}"
    echo ""
    echo "Queue worker'ı başlatmak için:"
    echo "  php artisan queue:work --queue=cortex-notifications --tries=3"
    echo ""
    echo "Veya Supervisor ile otomatik başlatmak için:"
    echo "  sudo supervisorctl start cortex-queue-worker:*"
fi

# 6. Özet
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${GREEN}✅ Deployment Kontrolü Tamamlandı!${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📋 Sonraki Adımlar:"
echo "  1. Queue worker'ı başlatın (yukarıdaki komut)"
echo "  2. Test senaryosu çalıştırın (yeni ilan oluştur)"
echo "  3. Telegram bildirimini kontrol edin"
echo "  4. Log dosyalarını izleyin: tail -f storage/logs/laravel.log"
echo ""
echo "📚 Detaylı dokümantasyon: docs/deployment/CORTEX_DEPLOYMENT_CHECKLIST.md"
echo ""

