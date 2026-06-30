#!/bin/bash
# Smoke Test Suite - Yalıhan Emlak V2
# Context7 Compliant Testing Script

echo "🧪 Starting Smoke Test Suite..."
echo "================================"
echo ""

# Colors
GREEN='\033[0.32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

PASS=0
FAIL=0

function test_case() {
    local name=$1
    local command=$2
    
    echo -n "Testing: $name... "
    
    if eval "$command" > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC}"
        ((PASS++))
        return 0
    else
        echo -e "${RED}✗${NC}"
        ((FAIL++))
        return 1
    fi
}

# 1. Server Health
echo "📡 Server Health Checks"
echo "----------------------"
test_case "Server Running (port 8002)" "lsof -i:8002 | grep -q LISTEN"
test_case "Homepage Returns 200" "curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8002/ | grep -q 200"
test_case "Frontend Assets Load" "curl -s http://127.0.0.1:8002/ | grep -q 'Yalıhan Emlak'"
echo ""

# 2. Routes
echo "🛣️  Route Checks"
echo "---------------"
test_case "Preferences Locale Route" "php artisan route:list | grep -q 'preferences.locale'"
test_case "Preferences Currency Route" "php artisan route:list | grep -q 'preferences.currency'"
test_case "Admin Dashboard Route" "php artisan route:list | grep -q 'admin.dashboard.index'"
echo ""

# 3. Files & Classes
echo "📁 File & Class Checks"
echo "---------------------"
test_case "PreferenceController Exists" "test -f app/Http/Controllers/Frontend/PreferenceController.php"
test_case "SetLocaleFromSession Middleware Exists" "test -f app/Http/Middleware/SetLocaleFromSession.php"
test_case "Config Files Present" "test -f config/localization.php && test -f config/currency.php"
echo ""

# 4. PHP Syntax
echo "🔍 PHP Syntax Checks"
echo "-------------------"
test_case "PreferenceController Syntax" "php -l app/Http/Controllers/Frontend/PreferenceController.php"
test_case "SetLocaleFromSession Syntax" "php -l app/Http/Middleware/SetLocaleFromSession.php"
echo ""

# 5. Context7 Compliance
echo "🛡️  Context7 Compliance"
echo "---------------------"
test_case "No 'status' in PreferenceController" "! grep -q 'status' app/Http/Controllers/Frontend/PreferenceController.php"
test_case "Routes Use Context7 Names" "php artisan route:list | grep preferences | grep -qv 'status\|order\|active'"
echo ""

# 6. Summary
echo ""
echo "================================"
echo "📊 Test Summary"
echo "================================"
echo -e "Passed: ${GREEN}$PASS${NC}"
echo -e "Failed: ${RED}$FAIL${NC}"
echo "Total:  $((PASS + FAIL))"
echo ""

if [ $FAIL -eq 0 ]; then
    echo -e "${GREEN}✅ All tests passed! System is healthy.${NC}"
    exit 0
else
    echo -e "${YELLOW}⚠️  Some tests failed. Review above output.${NC}"
    exit 1
fi
