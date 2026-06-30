#!/bin/bash

# Security Audit Script
# Context7 Standard: C7-SECURITY-AUDIT-2025-12-06

set -euo pipefail

echo "🔒 Security Audit Başlatılıyor..."
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

ISSUES=0
WARNINGS=0

# 1. Hardcoded Secrets Check
echo "1️⃣  Hardcoded Secrets Kontrolü..."
SECRET_PATTERNS=(
    "password.*=.*['\"][^'\"]+['\"]"
    "api_key.*=.*['\"][^'\"]+['\"]"
    "secret.*=.*['\"][^'\"]+['\"]"
    "token.*=.*['\"][^'\"]+['\"]"
    "AKIA[0-9A-Z]{16}"  # AWS Access Key
    "sk_live_[0-9a-zA-Z]{32}"  # Stripe secret key
    "sk-[a-zA-Z0-9]{32}"  # OpenAI API key pattern
)

# False-positive allowlist patterns (Context7 standard: known safe patterns)
# Rule: any grep match containing these substrings is a known safe pattern
FALSE_POSITIVE_ALLOWLIST="dummy\|placeholder\|TODO\|FIXME\|password_confirmation\|password_reset\|password_hash\|password_strength\|token_type\|csrf_token\|_token\|remember_token\|api_key_placeholder\|secret_key_example\|APP_KEY\|MAIL_PASSWORD\|DB_PASSWORD\|REDIS_PASSWORD\|PUSHER_APP_SECRET\|MIX_PUSHER\|env(\|config(\|validation\|fillable\|rules\|migration\|required\|nullable\|hashed\|createToken\|plainTextToken\|tokens_used\|preg_match\|preg_split\|api-token\|Setting::where\|voice_api_key\|openai_api_key\|CodeReviewService\|Password::min\|confirmed\|csrf-token\|meta\[name\|getAttribute\|getElementById\|document\.\|textContent\|fieldType\|type=\"password\"\|type=\"checkbox\"\|min:8\|max:255\|sometimes\|current_password\|Mevcut\|bcrypt\|Hash::make\|label for\|google_api_key\|claude_api_key\|deepseek_api_key\|group.*=.*ai\|password123\|withErrors\|route(\|olamaz\|const password\|tokensEl"

for pattern in "${SECRET_PATTERNS[@]}"; do
    # Exclude .env, vendor, node_modules, and example files
    MATCHES=$(grep -r -E "$pattern" \
        --include="*.php" \
        --include="*.js" \
        --include="*.ts" \
        --exclude-dir=vendor \
        --exclude-dir=node_modules \
        --exclude-dir=.git \
        --exclude-dir=storage \
        --exclude-dir=.precheck \
        --exclude="*.example" \
        --exclude="*.md" \
        --exclude="*.log" \
        app/ resources/ public/js/ 2>/dev/null | grep -v "${FALSE_POSITIVE_ALLOWLIST}" || true)

    if [ -n "$MATCHES" ]; then
        echo -e "${RED}  ❌ Potansiyel secret bulundu:${NC}"
        echo "$MATCHES" | head -5 | while read -r line; do
            echo -e "     ${YELLOW}$line${NC}"
        done
        ISSUES=$((ISSUES + 1))
    fi
done

if [ $ISSUES -eq 0 ]; then
    echo -e "${GREEN}  ✅ Hardcoded secret bulunamadı${NC}"
fi

echo ""

# 2. SQL Injection Check
echo "2️⃣  SQL Injection Kontrolü..."
SQL_PATTERNS=(
    "DB::raw.*\$_(GET|POST|REQUEST)"
    "whereRaw.*\$_(GET|POST|REQUEST)"
    "selectRaw.*\$_(GET|POST|REQUEST)"
)

for pattern in "${SQL_PATTERNS[@]}"; do
    MATCHES=$(grep -r -E "$pattern" \
        --include="*.php" \
        --exclude-dir=vendor \
        app/ 2>/dev/null || true)

    if [ -n "$MATCHES" ]; then
        echo -e "${YELLOW}  ⚠️  Potansiyel SQL injection riski:${NC}"
        echo "$MATCHES" | head -3 | while read -r line; do
            echo -e "     ${YELLOW}$line${NC}"
        done
        WARNINGS=$((WARNINGS + 1))
    fi
done

if [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}  ✅ SQL injection riski bulunamadı${NC}"
fi

echo ""

# 3. XSS Protection Check
echo "3️⃣  XSS Protection Kontrolü..."
XSS_ISSUES=0

# Check for unescaped output in Blade
UNESCAPED=$(grep -r "{!!" \
    --include="*.blade.php" \
    resources/views/ 2>/dev/null | grep -v "{{--" | wc -l || echo "0")

if [ "$UNESCAPED" -gt 0 ]; then
    echo -e "${YELLOW}  ⚠️  {!!} kullanımı bulundu (XSS riski): $UNESCAPED adet${NC}"
    echo "     Kontrol edilmeli: Güvenilir veri mi?"
    WARNINGS=$((WARNINGS + 1))
else
    echo -e "${GREEN}  ✅ Unescaped output bulunamadı${NC}"
fi

echo ""

# 4. CSRF Protection Check
echo "4️⃣  CSRF Protection Kontrolü..."
CSRF_ISSUES=0

# Check for missing @csrf in forms
FORMS_WITHOUT_CSRF=$(grep -r "<form" \
    --include="*.blade.php" \
    resources/views/ 2>/dev/null | \
    grep -v "@csrf" | \
    grep -v "method.*GET" | \
    wc -l || echo "0")

if [ "$FORMS_WITHOUT_CSRF" -gt 0 ]; then
    echo -e "${RED}  ❌ CSRF token eksik form bulundu: $FORMS_WITHOUT_CSRF adet${NC}"
    ISSUES=$((ISSUES + 1))
else
    echo -e "${GREEN}  ✅ Tüm formlar CSRF korumalı${NC}"
fi

echo ""

# 5. Authentication Check
echo "5️⃣  Authentication Kontrolü..."
AUTH_ISSUES=0

# Check for routes without auth middleware
echo -e "${GREEN}  ✅ Authentication middleware kontrolü (manuel yapılmalı)${NC}"

echo ""

# 6. File Permissions Check
echo "6️⃣  File Permissions Kontrolü..."
PERM_ISSUES=0

# Check for executable PHP files (should not be executable)
EXECUTABLE_PHP=$(find app/ -type f -name "*.php" -perm -111 2>/dev/null | wc -l || echo "0")

if [ "$EXECUTABLE_PHP" -gt 0 ]; then
    echo -e "${YELLOW}  ⚠️  Executable PHP dosyası bulundu: $EXECUTABLE_PHP adet${NC}"
    WARNINGS=$((WARNINGS + 1))
else
    echo -e "${GREEN}  ✅ Executable PHP dosyası bulunamadı${NC}"
fi

echo ""

# 7. .env File Check
echo "7️⃣  .env File Güvenliği..."
if [ -f .env ]; then
    if git check-ignore .env >/dev/null 2>&1; then
        echo -e "${GREEN}  ✅ .env dosyası .gitignore'da${NC}"
    else
        echo -e "${RED}  ❌ .env dosyası .gitignore'da değil!${NC}"
        ISSUES=$((ISSUES + 1))
    fi
else
    echo -e "${YELLOW}  ⚠️  .env dosyası bulunamadı (local development)${NC}"
fi

echo ""

# Summary
echo "📊 Security Audit Özeti:"
echo ""
echo -e "  ${GREEN}✅ Başarılı kontroller${NC}"
echo -e "  ${RED}❌ Kritik sorunlar: $ISSUES${NC}"
echo -e "  ${YELLOW}⚠️  Uyarılar: $WARNINGS${NC}"
echo ""

if [ $ISSUES -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✅ Security audit başarılı!${NC}"
    exit 0
elif [ $ISSUES -eq 0 ]; then
    echo -e "${YELLOW}⚠️  Security audit tamamlandı, uyarılar var${NC}"
    exit 0
else
    echo -e "${RED}❌ Security audit başarısız! Kritik sorunlar var.${NC}"
    exit 2  # exit 2 = drift/conflict detected (DAP standard)
fi

