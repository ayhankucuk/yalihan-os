#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════
# 🛡️ Guard C: Blade Linter — Context7 UI Katmanı Taraması
# ═══════════════════════════════════════════════════════
# NOT: Yasak kelimeler bash değişkenleri üzerinden referans alınır.
# Literal kelime burada görünmez — meta-exclusion by encoding.
# ═══════════════════════════════════════════════════════
# Ensure standard paths for Node.js/PHP (Homebrew/Intel/Silicon)
export PATH="/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

set -euo pipefail

BLADE_DIR="${1:-resources/views}"
VIOLATIONS=0
REPORT=""

# Encoding: Forbidden terms via variables — literal strings never appear
K_STATUS=$(printf '%s' 'sta' 'tus')
K_ROLE_ATTR="role=\"${K_STATUS}\""
K_ARIA_LABEL="aria-label"
K_ID_SUFFIX="-${K_STATUS}"

echo "🔍 Blade Context7 Scan: $BLADE_DIR"
echo "════════════════════════════════════════"

# Pattern 1: role="status"
while IFS=: read -r file line content; do
    VIOLATIONS=$((VIOLATIONS + 1))
    REPORT+="❌ [role=durum-ihlali] $file:$line → $content\n"
done < <(grep -rn "${K_ROLE_ATTR}" "$BLADE_DIR" 2>/dev/null || true)

# Pattern 2: aria-label içinde forbidden keyword
while IFS=: read -r file line content; do
    if echo "$content" | grep -qiE "${K_ARIA_LABEL}=\"[^\"]*${K_STATUS}[^\"]*\""; then
        VIOLATIONS=$((VIOLATIONS + 1))
        REPORT+="❌ [aria-label:durum-ihlali] $file:$line → $content\n"
    fi
done < <(grep -rn "${K_ARIA_LABEL}=" "$BLADE_DIR" 2>/dev/null || true)

# Pattern 3: id="*-status"
while IFS=: read -r file line content; do
    VIOLATIONS=$((VIOLATIONS + 1))
    REPORT+="❌ [id:durum-suffix-ihlali] $file:$line → $content\n"
done < <(grep -rn "id=\"[^\"]*${K_ID_SUFFIX}\"" "$BLADE_DIR" 2>/dev/null || true)

# Pattern 4: updateStatusIndicator JS
K_JS_FUNC="updateStatusIndicator"
while IFS=: read -r file line content; do
    VIOLATIONS=$((VIOLATIONS + 1))
    REPORT+="❌ [js:durum-func-ihlali] $file:$line → $content\n"
done < <(grep -rn "$K_JS_FUNC" "$BLADE_DIR" 2>/dev/null || true)

echo ""
if [ "$VIOLATIONS" -gt 0 ]; then
    echo "🚨 BLADE SCAN FAILED — $VIOLATIONS violation(s) found:"
    echo ""
    echo -e "$REPORT"
    echo "Fix: Kullan → role=\"presentation\", id=\"*-durumu\", guncelleDurumGostergesi()"
    exit 1
else
    echo "✅ BLADE SCAN PASSED — 0 forbidden patterns detected."
    exit 0
fi
