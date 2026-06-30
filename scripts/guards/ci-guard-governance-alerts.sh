#!/usr/bin/env bash
# CI Guard: Governance Alert Safety
# Ensures alerts are informational only and don't trigger auto-remediation

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

echo "🛡️  CI Guard: Governance Alert Safety"
echo "================================================"
echo ""

VIOLATIONS=0

# Check 1: No auto-remediation in alert handlers
echo "✓ Checking for auto-remediation in alert handlers..."
if grep -r "remediate\|auto.*fix\|self.*heal\|auto.*resolve" \
    "$PROJECT_ROOT/app/Services/Governance/" 2>/dev/null | \
    grep -i "alert" | grep -v "^Binary" | grep -v "comment" | grep -v "//"; then
    echo "❌ VIOLATION: Auto-remediation found in alert handlers"
    echo "   Alerts must be informational only"
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo "   ✅ No auto-remediation detected"
fi
echo ""

# Check 2: No enforcement triggers
echo "✓ Checking for enforcement triggers..."
if grep -r "enforce\|block\|deny\|reject" \
    "$PROJECT_ROOT/app/Services/Governance/" 2>/dev/null | \
    grep -i "alert" | grep -v "^Binary" | grep -v "comment" | grep -v "//"; then
    echo "❌ VIOLATION: Enforcement triggers found in alert handlers"
    echo "   Alerts must not trigger enforcement"
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo "   ✅ No enforcement triggers detected"
fi
echo ""

# Check 3: No authority mutations
echo "✓ Checking for authority mutations in alerts..."
if grep -r "Authority::\|->save()\|->update()" \
    "$PROJECT_ROOT/app/Services/Governance/" 2>/dev/null | \
    grep -i "alert" | grep -v "^Binary"; then
    echo "❌ VIOLATION: Authority mutations found in alert handlers"
    echo "   Alerts must not mutate authority"
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo "   ✅ No authority mutations detected"
fi
echo ""

# Check 4: Verify alert configuration exists
echo "✓ Checking alert configuration..."
if [ -f "$PROJECT_ROOT/config/governance-alerts.php" ]; then
    echo "   ✅ Alert configuration found"

    # Check for human review requirement
    if grep -q "human.*review\|manual.*review\|requires.*approval" \
        "$PROJECT_ROOT/config/governance-alerts.php"; then
        echo "   ✅ Human review requirement documented"
    else
        echo "   ⚠️  Consider documenting human review requirement"
    fi
else
    echo "   ℹ️  Alert configuration not found yet"
fi
echo ""

# Check 5: Verify alert channels are safe
echo "✓ Checking alert channels..."
ALERT_CHANNELS_DIR="$PROJECT_ROOT/app/Services/Governance/Channels"
if [ -d "$ALERT_CHANNELS_DIR" ]; then
    for channel in "$ALERT_CHANNELS_DIR"/*.php; do
        if [ -f "$channel" ]; then
            channel_name=$(basename "$channel")
            if grep -q "->save()\|->update()\|->delete()" "$channel"; then
                echo "   ❌ VIOLATION: Database writes in $channel_name"
                VIOLATIONS=$((VIOLATIONS + 1))
            else
                echo "   ✅ $channel_name is safe"
            fi
        fi
    done
else
    echo "   ℹ️  Alert channels not implemented yet"
fi
echo ""

# Summary
echo "================================================"
if [ $VIOLATIONS -eq 0 ]; then
    echo "✅ PASSED: All governance alert safety checks passed"
    echo ""
    echo "Governance alerts are:"
    echo "  ✓ Informational only"
    echo "  ✓ No auto-remediation"
    echo "  ✓ No enforcement triggers"
    echo "  ✓ Authority-safe"
    exit 0
else
    echo "❌ FAILED: $VIOLATIONS violation(s) detected"
    echo ""
    echo "Governance alerts must:"
    echo "  • Be informational only"
    echo "  • Not trigger auto-remediation"
    echo "  • Not trigger enforcement"
    echo "  • Not mutate authority"
    echo "  • Require human review"
    exit 1
fi
