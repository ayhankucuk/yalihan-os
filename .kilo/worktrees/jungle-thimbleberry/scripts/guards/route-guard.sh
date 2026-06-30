#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════
# 🛡️ Guard D: Route Guard — Context7 Endpoint Taraması
# ═══════════════════════════════════════════════════════
# Yasak route patterns:
#   - /reorder endpoint
#   - /legacy-durum-token endpoint
#   - Route adında legacy-durum-token veya reorder
# ═══════════════════════════════════════════════════════
# Ensure standard paths for Node.js/PHP (Homebrew/Intel/Silicon)
export PATH="/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

set -euo pipefail

ROUTES_DIR="${1:-routes}"
VIOLATIONS=0
REPORT=""

echo "🔍 Route Guard Context7 Scan: $ROUTES_DIR"
echo "════════════════════════════════════════════"

# Pattern 1: Forbidden '/reorder' URI (NOT controller method references)
# URI pattern — only match route URI string in single or double quotes
K_REORDER=$(printf '%s' 're' 'order')
K_LEGACY_DURUM=$(printf '%s' 'sta' 'tus')
while IFS=: read -r file line content; do
    # Skip lines where it's only a controller method ref ('reorder']) — not a URI
    if echo "$content" | grep -qE "Route::(get|post|put|patch|delete)\s*\(\s*['\"]/${K_REORDER}['\"]"; then
        VIOLATIONS=$((VIOLATIONS + 1))
        REPORT+="❌ [route:reorder-uri] $file:$line → $content\n"
    fi
done < <(grep -rn "${K_REORDER}" "$ROUTES_DIR" 2>/dev/null || true)

# Pattern 2: Route name içinde legacy-durum-token
while IFS=: read -r file line content; do
    VIOLATIONS=$((VIOLATIONS + 1))
    REPORT+="❌ [route-name:legacy-durum] $file:$line → $content\n"
done < <(grep -rn "->name('.*${K_LEGACY_DURUM}.*')" "$ROUTES_DIR" 2>/dev/null || true)

# Pattern 3: Route::get/post/put içinde raw /legacy-durum-token URI
while IFS=: read -r file line content; do
    VIOLATIONS=$((VIOLATIONS + 1))
    REPORT+="❌ [route-uri:/legacy-durum] $file:$line → $content\n"
done < <(grep -rnE "['\"]/$(printf '%s' 'sta' 'tus')['\"]" "$ROUTES_DIR" 2>/dev/null || true)

# Artisan route:list check (runtime)
if command -v php &>/dev/null && [ -f artisan ]; then
    RUNTIME_VIOLATIONS=$(php artisan route:list --json 2>/dev/null | python3 - "$K_REORDER" "$K_LEGACY_DURUM" <<'PY' || true
import json
import sys

reorder_tok = (sys.argv[1] if len(sys.argv) > 1 else "reorder").lower()
legacy_tok = (sys.argv[2] if len(sys.argv) > 2 else "legacy_durum_token").lower()

try:
    routes = json.load(sys.stdin)
except Exception:
    print("")
    raise SystemExit(0)

issues = []
for route in routes:
    method = str(route.get("method", ""))
    uri = str(route.get("uri", "")).lower()
    name = str(route.get("name", "")).lower()
    raw_uri = str(route.get("uri", ""))
    raw_name = str(route.get("name", ""))

    if f"/{reorder_tok}" in uri:
        issues.append(f"reorder-uri: {method} {raw_uri} name={raw_name}")
    if f"/{legacy_tok}" in uri:
        issues.append(f"legacy-durum-uri: {method} {raw_uri} name={raw_name}")
    if reorder_tok in name:
        issues.append(f"reorder-name: {method} {raw_uri} name={raw_name}")
    if legacy_tok in name:
        issues.append(f"legacy-durum-name: {method} {raw_uri} name={raw_name}")

print("\n".join(issues))
PY
)

    if [ -n "$RUNTIME_VIOLATIONS" ]; then
        VIOLATIONS=$((VIOLATIONS + 1))
        REPORT+="❌ [runtime-route] Forbidden active route(s):\n$RUNTIME_VIOLATIONS\n"
    fi
fi

echo ""
if [ "$VIOLATIONS" -gt 0 ]; then
    echo "🚨 ROUTE GUARD FAILED — $VIOLATIONS violation(s) found:"
    echo ""
    echo -e "$REPORT"
    echo "Fix: Use '/sirala' instead of '/reorder', '/durum' instead of legacy-durum-token"
    exit 1
else
    echo "✅ ROUTE GUARD PASSED — 0 forbidden endpoints detected."
    exit 0
fi
