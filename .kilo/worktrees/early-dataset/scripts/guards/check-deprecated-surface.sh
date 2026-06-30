#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════════
# CI-GATE-06: Deprecated Surface Guard
# ═══════════════════════════════════════════════════════════════════════════
# Detects:
# 1. New imports referencing @deprecated / QUARANTINE classes
# 2. New methods added to deprecated files
# FAIL if new references to deprecated classes detected.
# ═══════════════════════════════════════════════════════════════════════════
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
BASELINE_FILE="${PROJECT_ROOT}/scripts/guards/baselines/baseline-deprecated-surface.txt"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

cd "${PROJECT_ROOT}"

echo "═══════════════════════════════════════════════════════════════"
echo "🔍 CI-GATE-06: Deprecated Surface Guard"
echo "═══════════════════════════════════════════════════════════════"
echo ""

CURRENT_FILE=$(mktemp)
DEPRECATED_CLASSES=$(mktemp)
trap "rm -f '${CURRENT_FILE}' '${DEPRECATED_CLASSES}'" EXIT

# ─── Step 1: Find all deprecated/quarantined files ───
echo "Scanning for @deprecated and QUARANTINE markers..."

find app/ -name "*.php" -type f | while IFS= read -r file; do
    if grep -qE '@deprecated|QUARANTINE' "${file}" 2>/dev/null; then
        # Extract the fully qualified class name
        namespace=$(grep -E '^namespace ' "${file}" 2>/dev/null | head -1 | sed 's/namespace //;s/;//' || true)
        classname=$(grep -E '^class ' "${file}" 2>/dev/null | head -1 | awk '{print $2}' || true)
        if [[ -n "${namespace}" ]] && [[ -n "${classname}" ]]; then
            fqcn="${namespace}\\${classname}"
            method_count=$(grep -cE '^\s+public\s+function\s+' "${file}" 2>/dev/null || echo 0)
            echo "${file}|${fqcn}|${method_count}"
        fi
    fi
done > "${DEPRECATED_CLASSES}"

DEPRECATED_COUNT=0
[[ -s "${DEPRECATED_CLASSES}" ]] && DEPRECATED_COUNT=$(wc -l < "${DEPRECATED_CLASSES}" | tr -d ' ')

echo "📊 Deprecated/quarantined classes: ${DEPRECATED_COUNT}"

if [[ ${DEPRECATED_COUNT} -eq 0 ]]; then
    echo -e "${GREEN}✅ PASS — No deprecated classes found${NC}"
    exit 0
fi

# List them
while IFS='|' read -r path fqcn methods; do
    echo "  📋 ${fqcn} (${path}) — ${methods} public methods"
done < "${DEPRECATED_CLASSES}"
echo ""

# ─── Step 2: Find imports of deprecated classes outside their own files ───
echo "Checking for external references to deprecated classes..."

VIOLATION_COUNT=0

while IFS='|' read -r dep_path dep_fqcn dep_methods; do
    # Search for `use {FQCN}` — use grep -rl for O(n) instead of O(n*m)
    escaped_fqcn=$(echo "${dep_fqcn}" | sed 's/\\/\\\\/g')

    grep -rl --include="*.php" "use ${escaped_fqcn}" app/ 2>/dev/null | while IFS= read -r file; do
        [[ "${file}" == "${dep_path}" ]] && continue  # Skip the deprecated file itself
        echo "${file}|imports|${dep_fqcn}"
    done
done < "${DEPRECATED_CLASSES}" >> "${CURRENT_FILE}"

[[ -s "${CURRENT_FILE}" ]] && VIOLATION_COUNT=$(wc -l < "${CURRENT_FILE}" | tr -d ' ')

echo "📊 External references to deprecated classes: ${VIOLATION_COUNT}"

# ─── Generate baseline if requested ───
if [[ "${1:-}" == "--generate-baseline" ]]; then
    mkdir -p "$(dirname "${BASELINE_FILE}")"
    # Save both: deprecated class inventory + external references
    {
        echo "# Deprecated Classes"
        cat "${DEPRECATED_CLASSES}"
        echo "# External References"
        cat "${CURRENT_FILE}" 2>/dev/null || true
    } > "${BASELINE_FILE}"
    echo -e "${GREEN}✅ Baseline generated: ${DEPRECATED_COUNT} deprecated classes, ${VIOLATION_COUNT} external refs${NC}"
    exit 0
fi

# ─── Step 3: Baseline comparison ───
if [[ ! -f "${BASELINE_FILE}" ]]; then
    echo -e "${YELLOW}⚠️  No baseline found at ${BASELINE_FILE}${NC}"
        exit 0
    fi
    echo "   Run with --generate-baseline to create initial baseline"
    echo "   Treating current state as baseline (PASS)"
    exit 0
fi

# ─── Compare: new references to deprecated classes ───
BASELINE_REFS=$(grep -v '^#' "${BASELINE_FILE}" | grep '|imports|' | sort 2>/dev/null || true)
CURRENT_REFS=$(sort "${CURRENT_FILE}" 2>/dev/null || true)

NEW_REFS=$(mktemp)
trap "rm -f '${CURRENT_FILE}' '${DEPRECATED_CLASSES}' '${NEW_REFS}'" EXIT

comm -23 <(echo "${CURRENT_REFS}") <(echo "${BASELINE_REFS}") > "${NEW_REFS}" 2>/dev/null || true

NEW_REF_COUNT=0
[[ -s "${NEW_REFS}" ]] && NEW_REF_COUNT=$(wc -l < "${NEW_REFS}" | tr -d ' ')

if [[ ${NEW_REF_COUNT} -gt 0 ]]; then
    echo ""
    echo -e "${RED}❌ FAIL: ${NEW_REF_COUNT} NEW reference(s) to deprecated classes!${NC}"
    echo ""
    while IFS='|' read -r file action dep_class; do
        echo "  ⛔ ${file} → imports ${dep_class}"
    done < "${NEW_REFS}"
    echo ""
    echo "Fix: Do not import @deprecated or QUARANTINE classes. Use their replacement."
    exit 1
fi

# ─── Check for method growth in deprecated files ───
BASELINE_CLASSES=$(grep -v '^#' "${BASELINE_FILE}" | grep -v '|imports|' 2>/dev/null || true)
GROWTH_VIOLATIONS=0

while IFS='|' read -r dep_path dep_fqcn dep_methods; do
    baseline_entry=$(echo "${BASELINE_CLASSES}" | grep "|${dep_fqcn}|" 2>/dev/null || true)
    if [[ -n "${baseline_entry}" ]]; then
        baseline_methods=$(echo "${baseline_entry}" | cut -d'|' -f3)
        if [[ ${dep_methods} -gt ${baseline_methods} ]]; then
            echo -e "${RED}  ⛔ DEPRECATED GROWTH: ${dep_fqcn} grew from ${baseline_methods} → ${dep_methods} methods${NC}"
            GROWTH_VIOLATIONS=$((GROWTH_VIOLATIONS + 1))
        fi
    fi
done < "${DEPRECATED_CLASSES}"

if [[ ${GROWTH_VIOLATIONS} -gt 0 ]]; then
    echo ""
    echo -e "${RED}❌ FAIL: ${GROWTH_VIOLATIONS} deprecated class(es) have new methods!${NC}"
    echo "Fix: Do not add new methods to @deprecated or QUARANTINE classes."
    exit 1
fi

# ─── Summary ───
if [[ ${VIOLATION_COUNT} -lt $(grep -c '|imports|' "${BASELINE_FILE}" 2>/dev/null || echo 0) ]]; then
    echo -e "${GREEN}✅ PASS — Deprecated references decreased (improvement!)${NC}"
else
    echo -e "${GREEN}✅ PASS — No new deprecated surface growth${NC}"
fi

exit 0
