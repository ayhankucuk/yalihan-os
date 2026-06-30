#!/usr/bin/env bash

# Yalıhan Bekçi: Hourly Light Health Check
# Usage: ./scripts/health-check.sh

BASE_URL=${1:-http://127.0.0.1:8002}
ALERT_FILE="storage/logs/health-alerts.log"

set -euo pipefail

mkdir -p "$(dirname "$ALERT_FILE")"

check_endpoint() {
    local name="$1"
    local url="$2"
    local method="${3:-GET}"
    local RESPONSE
    local HTTP_CODE
    local BODY

    RESPONSE=$(curl -s -w "\n%{http_code}" -X "$method" "$url" -H "Accept: application/json" 2>&1)
    HTTP_CODE=$(echo "$RESPONSE" | tail -1)
    BODY=$(echo "$RESPONSE" | sed '$d')

    if ! [[ "$HTTP_CODE" =~ ^[0-9]+$ ]]; then
        echo "🚨 CRITICAL: $name invalid HTTP code: $HTTP_CODE"
        echo "$(date): $name - invalid_http_code $HTTP_CODE" >> "$ALERT_FILE"
        return 1
    fi

    # Check for 500 errors
    if [ "$HTTP_CODE" -ge 500 ]; then
        echo "🚨 CRITICAL: $name returned $HTTP_CODE"
        echo "$(date): $name - HTTP $HTTP_CODE" >> "$ALERT_FILE"
        return 1
    fi

    # Check for HTML in JSON response
    if echo "$BODY" | grep -q "<!DOCTYPE\|<html\|<body\|Unexpected token"; then
        echo "🚨 CRITICAL: $name returned HTML instead of JSON!"
        echo "$(date): $name - HTML in JSON response" >> "$ALERT_FILE"
        echo "Response preview: $(echo "$BODY" | head -c 200)"
        return 1
    fi

    echo "✅ $name OK (HTTP $HTTP_CODE)"
    return 0
}

echo "🏥 Hourly Health Check - $(date)"
echo "================================"

FAILED=0

check_endpoint "Frontend Features" "$BASE_URL/api/v1/admin/category/daire/frontend-features?yayin_tipi_id=1" || FAILED=$((FAILED + 1))
check_endpoint "Validation Rules" "$BASE_URL/api/v1/wizard/validation-rules" || FAILED=$((FAILED + 1))

echo ""
if [ $FAILED -eq 0 ]; then
    echo "✅ All endpoints healthy"
    exit 0
else
    echo "❌ $FAILED endpoint(s) unhealthy - Check $ALERT_FILE"
    exit 1
fi
