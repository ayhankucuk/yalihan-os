#!/usr/bin/env bash
# CI Guard: Governance Dashboard Safety
# Ensures dashboard is read-only and contains no control actions

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

echo "🛡️  CI Guard: Governance Dashboard Safety"
echo "================================================"
echo ""

VIOLATIONS=0

# Check 1: No write operations in dashboard controller
echo "✓ Checking for write operations in dashboard controller..."
DASHBOARD_CONTROLLER="$PROJECT_ROOT/app/Http/Controllers/Admin/GovernanceDashboardController.php"
if [ -f "$DASHBOARD_CONTROLLER" ]; then
    if grep -q "->save()\|->update()\|->delete()\|->create()\|->insert()" "$DASHBOARD_CONTROLLER"; then
        echo "❌ VIOLATION: Write operations found in dashboard controller"
        echo "   Dashboard must be read-only"
        VIOLATIONS=$((VIOLATIONS + 1))
    else
        echo "   ✅ No write operations detected"
    fi
else
    echo "   ℹ️  Dashboard controller not implemented yet"
fi
echo ""

# Check 2: No enforcement actions in dashboard
echo "✓ Checking for enforcement actions..."
if [ -f "$DASHBOARD_CONTROLLER" ]; then
    if grep -q "enforce\|remediate\|fix\|resolve\|block\|deny" "$DASHBOARD_CONTROLLER"; then
        echo "❌ VIOLATION: Enforcement actions found in dashboard"
        echo "   Dashboard must be observation-only"
        VIOLATIONS=$((VIOLATIONS + 1))
    else
        echo "   ✅ No enforcement actions detected"
    fi
fi
echo ""

# Check 3: No control buttons in dashboard views
echo "✓ Checking for control buttons in dashboard views..."
DASHBOARD_VIEWS="$PROJECT_ROOT/resources/views/admin/governance"
if [ -d "$DASHBOARD_VIEWS" ]; then
    if grep -r "button.*enforce\|button.*fix\|button.*resolve\|button.*remediate" \
        "$DASHBOARD_VIEWS" 2>/dev/null | grep -v "^Binary"; then
        echo "❌ VIOLATION: Control buttons found in dashboard views"
        echo "   Dashboard UI must be read-only"
        VIOLATIONS=$((VIOLATIONS + 1))
    else
        echo "   ✅ No control buttons detected"
    fi
else
    echo "   ℹ️  Dashboard views not implemented yet"
fi
echo ""

# Check 4: No POST/PUT/DELETE routes for dashboard
echo "✓ Checking dashboard routes..."
if grep -A 10 "governance.*dashboard" "$PROJECT_ROOT/routes/web.php" 2>/dev/null | \
    grep -E "Route::(post|put|delete|patch)"; then
    echo "❌ VIOLATION: Write routes found for dashboard"
    echo "   Dashboard should only have GET routes"
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo "   ✅ Only read routes detected (or not implemented yet)"
fi
echo ""

# Check 5: Verify dashboard JavaScript is read-only
echo "✓ Checking dashboard JavaScript..."
DASHBOARD_JS="$PROJECT_ROOT/public/js/admin/governance-dashboard.js"
if [ -f "$DASHBOARD_JS" ]; then
    if grep -q "method.*POST\|method.*PUT\|method.*DELETE" "$DASHBOARD_JS"; then
        echo "⚠️  WARNING: Write methods found in dashboard JavaScript"
        echo "   Verify these are not for control actions"
    else
        echo "   ✅ No write methods detected"
    fi
else
    echo "   ℹ️  Dashboard JavaScript not implemented yet"
fi
echo ""

# Check 6: No authority model access in dashboard
echo "✓ Checking for authority model access..."
if [ -f "$DASHBOARD_CONTROLLER" ]; then
    if grep -q "Authority::\|use.*Authority;" "$DASHBOARD_CONTROLLER"; then
        echo "❌ VIOLATION: Authority model access found in dashboard"
        echo "   Dashboard must not access authority models"
        VIOLATIONS=$((VIOLATIONS + 1))
    else
        echo "   ✅ No authority model access detected"
    fi
fi
echo ""

# Summary
echo "================================================"
if [ $VIOLATIONS -eq 0 ]; then
    echo "✅ PASSED: All governance dashboard safety checks passed"
    echo ""
    echo "Governance dashboard is:"
    echo "  ✓ Read-only"
    echo "  ✓ No control actions"
    echo "  ✓ No enforcement buttons"
    echo "  ✓ Authority-safe"
    echo "  ✓ Visualization only"
    exit 0
else
    echo "❌ FAILED: $VIOLATIONS violation(s) detected"
    echo ""
    echo "Governance dashboard must:"
    echo "  • Be read-only (no write operations)"
    echo "  • Have no control actions"
    echo "  • Have no enforcement buttons"
    echo "  • Not access authority models"
    echo "  • Be visualization only"
    exit 1
fi
