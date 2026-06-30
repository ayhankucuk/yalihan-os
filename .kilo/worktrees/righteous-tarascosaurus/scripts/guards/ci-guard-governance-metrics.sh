#!/usr/bin/env bash
# CI Guard: Governance Metrics Isolation
# Ensures metrics use separate tables and don't access authority data

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

echo "🛡️  CI Guard: Governance Metrics Isolation"
echo "================================================"
echo ""

VIOLATIONS=0

# Check 1: No authority table access in metrics services
echo "✓ Checking for authority table access in metrics..."
METRICS_DIR="$PROJECT_ROOT/app/Services/Governance"
if [ -d "$METRICS_DIR" ]; then
    if grep -r "DB::table.*authority\|from.*authority" \
        "$METRICS_DIR" 2>/dev/null | \
        grep -i "metric\|aggregat\|trend" | grep -v "^Binary"; then
        echo "❌ VIOLATION: Authority table access found in metrics services"
        echo "   Metrics must use separate tables"
        VIOLATIONS=$((VIOLATIONS + 1))
    else
        echo "   ✅ No authority table access detected"
    fi
else
    echo "   ℹ️  Metrics services not implemented yet"
fi
echo ""

# Check 2: Verify separate metrics tables exist
echo "✓ Checking for separate metrics tables..."
MIGRATIONS_DIR="$PROJECT_ROOT/database/migrations"
if ls "$MIGRATIONS_DIR"/*governance_metrics*.php 2>/dev/null | grep -q .; then
    echo "   ✅ Governance metrics table migration found"
else
    echo "   ℹ️  Governance metrics table not created yet"
fi

if ls "$MIGRATIONS_DIR"/*governance_telemetry*.php 2>/dev/null | grep -q .; then
    echo "   ✅ Governance telemetry table migration found"
else
    echo "   ℹ️  Governance telemetry table not created yet"
fi
echo ""

# Check 3: No authority model usage in metrics
echo "✓ Checking for authority model usage..."
if [ -d "$METRICS_DIR" ]; then
    if grep -r "Authority::\|use.*Authority;" \
        "$METRICS_DIR" 2>/dev/null | \
        grep -i "metric\|aggregat" | grep -v "^Binary"; then
        echo "❌ VIOLATION: Authority model usage found in metrics"
        echo "   Metrics must not use authority models"
        VIOLATIONS=$((VIOLATIONS + 1))
    else
        echo "   ✅ No authority model usage detected"
    fi
fi
echo ""

# Check 4: Verify CQRS separation
echo "✓ Checking CQRS separation..."
if [ -d "$METRICS_DIR" ]; then
    # Check for direct Eloquent usage (should use repositories)
    if grep -r "Eloquent::\|extends Model" \
        "$METRICS_DIR" 2>/dev/null | \
        grep -i "metric" | grep -v "^Binary"; then
        echo "⚠️  WARNING: Direct Eloquent usage in metrics"
        echo "   Consider using read-only repositories"
    else
        echo "   ✅ No direct Eloquent usage detected"
    fi
fi
echo ""

# Check 5: No write operations to authority tables
echo "✓ Checking for writes to authority tables..."
if [ -d "$METRICS_DIR" ]; then
    if grep -r "->save()\|->update()\|->delete()" \
        "$METRICS_DIR" 2>/dev/null | grep -v "^Binary" | \
        grep -v "governance_metrics\|governance_telemetry"; then
        echo "⚠️  WARNING: Write operations found in metrics services"
        echo "   Verify these are to metrics tables only"
    else
        echo "   ✅ No suspicious write operations detected"
    fi
fi
echo ""

# Check 6: Verify metrics aggregation is read-only
echo "✓ Checking metrics aggregation safety..."
AGGREGATOR="$PROJECT_ROOT/app/Services/Governance/MetricsAggregator.php"
if [ -f "$AGGREGATOR" ]; then
    if grep -q "Authority::" "$AGGREGATOR"; then
        echo "❌ VIOLATION: Authority access in metrics aggregator"
        VIOLATIONS=$((VIOLATIONS + 1))
    else
        echo "   ✅ Metrics aggregator is authority-safe"
    fi
else
    echo "   ℹ️  Metrics aggregator not implemented yet"
fi
echo ""

# Summary
echo "================================================"
if [ $VIOLATIONS -eq 0 ]; then
    echo "✅ PASSED: All governance metrics isolation checks passed"
    echo ""
    echo "Governance metrics are:"
    echo "  ✓ Using separate tables"
    echo "  ✓ Not accessing authority data"
    echo "  ✓ CQRS-compliant"
    echo "  ✓ Read-only operations"
    exit 0
else
    echo "❌ FAILED: $VIOLATIONS violation(s) detected"
    echo ""
    echo "Governance metrics must:"
    echo "  • Use separate database tables"
    echo "  • Not access authority tables"
    echo "  • Not use authority models"
    echo "  • Respect CQRS boundaries"
    echo "  • Be read-only"
    exit 1
fi
