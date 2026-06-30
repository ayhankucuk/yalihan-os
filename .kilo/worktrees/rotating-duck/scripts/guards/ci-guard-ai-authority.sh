#!/usr/bin/env bash
# ============================================================
# CI Guard — AI Authority Drift (Patch B)
#
# Ensures controllers do NOT inject OllamaService directly
# as a method parameter. All AI calls must route through
# YalihanCortex which handles provider selection, fallback,
# cost guard, and telemetry.
#
# Rule: OllamaService may ONLY be referenced inside:
#   - app/Services/AI/YalihanCortex.php        (owns $ollamaService property)
#   - app/Services/AI/OllamaService.php        (self-definition)
#   - tests/ and seeders/                      (test doubles)
#
# Violation pattern: any function signature in app/Http/Controllers/
# that contains OllamaService as a type-hint parameter.
# ============================================================

set -euo pipefail

BASE_DIR="$(cd "$(dirname "$0")/.." && pwd)"
CONTROLLERS_DIR="${BASE_DIR}/app/Http/Controllers"

PASS=0
FAIL=0

echo ""
echo "🛡️  AI Authority Drift Guard"
echo "   YalihanCortex = sole AI entry point"
echo ""

# ── RULE-AI-1: No direct OllamaService injection in controller method signatures ──
echo "🔍 [RULE-AI-1] Direct OllamaService injection in controllers..."

# Find controllers with OllamaService in a function signature
VIOLATIONS=$(grep -rn "function .*OllamaService" "${CONTROLLERS_DIR}" 2>/dev/null || true)

if [[ -n "${VIOLATIONS}" ]]; then
    echo "  ❌ [RULE-AI-1] FAILED — OllamaService injected in controller method(s):"
    echo "${VIOLATIONS}" | while IFS= read -r line; do
        echo "      ⚠️  ${line}"
    done
    FAIL=$((FAIL + 1))
else
    echo "  ✅ [RULE-AI-1] PASSED — No direct OllamaService injection in controllers."
    PASS=$((PASS + 1))
fi

# ── RULE-AI-2: YalihanCortex is declared in PropertyHubController constructor ──
echo "🔍 [RULE-AI-2] YalihanCortex authority wired in PropertyHubController..."

PHUB="${BASE_DIR}/app/Http/Controllers/Admin/PropertyHubController.php"
if grep -q "YalihanCortex" "${PHUB}" 2>/dev/null && grep -q "cortex" "${PHUB}" 2>/dev/null; then
    echo "  ✅ [RULE-AI-2] PASSED — YalihanCortex injected in PropertyHubController."
    PASS=$((PASS + 1))
else
    echo "  ❌ [RULE-AI-2] FAILED — YalihanCortex not found in PropertyHubController."
    FAIL=$((FAIL + 1))
fi

# ── RULE-AI-3: The 3 bridged AI methods use cortex, not ollamaService ──
echo "🔍 [RULE-AI-3] AI helper methods call cortex, not ollamaService directly..."

CORTEX_CALLS=$(grep -c "this->cortex->analyzePropertyGaps\|this->cortex->extractFeaturesFromText\|this->cortex->generateTemplateSuggestions" "${PHUB}" 2>/dev/null || true)

if [[ "${CORTEX_CALLS}" -ge 3 ]]; then
    echo "  ✅ [RULE-AI-3] PASSED — All 3 AI helper methods route through YalihanCortex."
    PASS=$((PASS + 1))
else
    echo "  ❌ [RULE-AI-3] FAILED — Expected 3 cortex AI calls, found ${CORTEX_CALLS}."
    FAIL=$((FAIL + 1))
fi

# ── Summary ──
echo ""
echo "────────────────────────────────────────────────────────────────"

if [[ "${FAIL}" -gt 0 ]]; then
    echo "💥 AI Authority Drift Guard: FAILED (${FAIL} rule(s) violated, ${PASS} passed)"
    echo "   OllamaService bypass detected — all AI calls must route through YalihanCortex."
    exit 1
else
    echo "✨ AI Authority Drift Guard: PASSED — ${PASS}/${PASS} rules clean."
    echo "   YalihanCortex authority intact."
    exit 0
fi
