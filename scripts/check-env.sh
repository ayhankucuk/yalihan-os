#!/usr/bin/env bash
# =============================================================================
# Yalıhan Emlak — Production ENV Validator
# SAB Kural: Deploy öncesi kritik değişkenler doğrulanmalı
# Çalıştırma: ./scripts/check-env.sh
# =============================================================================

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ERRORS=0
WARNINGS=0

check_required() {
    local key="$1"
    local val="${!key:-}"
    if [ -z "$val" ]; then
        echo -e "${RED}❌ ZORUNLU EKSİK: ${key}${NC}"
        ((ERRORS++))
    else
        echo -e "${GREEN}✅ ${key}${NC}"
    fi
}

check_optional() {
    local key="$1"
    local val="${!key:-}"
    if [ -z "$val" ]; then
        echo -e "${YELLOW}⚠️  OPSİYONEL BOŞ: ${key}${NC}"
        ((WARNINGS++))
    else
        echo -e "${GREEN}✅ ${key}${NC}"
    fi
}

check_not_default() {
    local key="$1"
    local forbidden="$2"
    local val="${!key:-}"
    if [ "$val" = "$forbidden" ] || [ -z "$val" ]; then
        echo -e "${RED}❌ GEÇERSİZ DEĞER (varsayılan/boş): ${key}=${val}${NC}"
        ((ERRORS++))
    else
        echo -e "${GREEN}✅ ${key}${NC}"
    fi
}

# .env dosyasını yükle (eğer varsa)
ENV_FILE="${ENV_FILE:-.env}"
if [ -f "$ENV_FILE" ]; then
    set -a
    # shellcheck disable=SC1090
    source "$ENV_FILE"
    set +a
else
    echo -e "${RED}❌ .env dosyası bulunamadı: $ENV_FILE${NC}"
    exit 1
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🛡️  Yalıhan Production ENV Validator"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# --------------------------------------------------------------------------
echo ""
echo "📦 1. TEMEL UYGULAMA"
# --------------------------------------------------------------------------
check_not_default "APP_KEY"   ""
check_not_default "APP_ENV"   "local"
check_not_default "APP_URL"   "http://localhost"

# APP_DEBUG production'da false olmalı
APP_DEBUG_VAL="${APP_DEBUG:-true}"
if [ "$APP_DEBUG_VAL" = "true" ]; then
    echo -e "${RED}❌ GÜVENLİK: APP_DEBUG=true production'da yasak!${NC}"
    ((ERRORS++))
else
    echo -e "${GREEN}✅ APP_DEBUG=false${NC}"
fi

# --------------------------------------------------------------------------
echo ""
echo "💾 2. VERİTABANI"
# --------------------------------------------------------------------------
check_required "DB_HOST"
check_required "DB_DATABASE"
check_required "DB_USERNAME"
check_required "DB_PASSWORD"
check_required "REDIS_HOST"

# --------------------------------------------------------------------------
echo ""
echo "🤖 3. AI SERVİSLERİ"
# --------------------------------------------------------------------------
check_required "DEEPSEEK_API_KEY"
check_optional "OPENAI_API_KEY"
check_optional "ANTHROPIC_API_KEY"

# --------------------------------------------------------------------------
echo ""
echo "📱 4. TELEGRAM"
# --------------------------------------------------------------------------
check_required "TELEGRAM_BOT_TOKEN"
check_required "TELEGRAM_ADMIN_CHAT_ID"
check_required "TELEGRAM_WEBHOOK_SECRET"

# --------------------------------------------------------------------------
echo ""
echo "🔄 5. N8N"
# --------------------------------------------------------------------------
check_required "N8N_WEBHOOK_URL"
check_required "N8N_WEBHOOK_SECRET"

# --------------------------------------------------------------------------
echo ""
echo "🔐 6. GÜVENLİK"
# --------------------------------------------------------------------------
check_required "PRODUCTION_LOCK"
check_optional "FRONTEND_API_KEY"

# CHAOS_MODE production'da false olmalı
CHAOS_VAL="${PROPERTYHUB_CHAOS_ENABLED:-false}"
if [ "$CHAOS_VAL" = "true" ]; then
    echo -e "${RED}❌ GÜVENLİK: PROPERTYHUB_CHAOS_ENABLED=true production'da yasak!${NC}"
    ((ERRORS++))
else
    echo -e "${GREEN}✅ PROPERTYHUB_CHAOS_ENABLED=false${NC}"
fi

# --------------------------------------------------------------------------
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ "$ERRORS" -gt 0 ]; then
    echo -e "${RED}❌ ENV DOĞRULAMA BAŞARISIZ — $ERRORS hata, $WARNINGS uyarı${NC}"
    echo -e "${RED}   Deploy iptal edildi. Eksik değişkenleri .env'e ekleyin.${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    exit 1
else
    echo -e "${GREEN}✅ ENV DOĞRULAMA BAŞARILI — $WARNINGS uyarı${NC}"
    echo -e "${GREEN}   Tüm kritik değişkenler mevcut.${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    exit 0
fi
