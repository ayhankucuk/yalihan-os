#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════════
# CI-GATE-05: Cross Domain Drift Guard
# ═══════════════════════════════════════════════════════════════════════════
# Detects cross-domain imports in controllers.
# Maps controller namespace → domain, imported class → domain.
# Flags mismatches as drift.
# Initial mode: WARN ONLY (Phase 1)
# ═══════════════════════════════════════════════════════════════════════════
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
BASELINE_FILE="${PROJECT_ROOT}/scripts/guards/baselines/baseline-cross-domain.txt"
ALLOWLIST_FILE="${PROJECT_ROOT}/scripts/guards/baselines/allowlist-cross-domain.txt"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

cd "${PROJECT_ROOT}"

CONTROLLER_DIR="app/Http/Controllers"

# ─── Domain extraction from namespace path ───
# Admin/PropertyHub/... → PropertyHub
# Admin/AI/... → AI
# Api/V1/Wizard/... → Wizard
# Admin/CRM/... → CRM
extract_domain() {
    local path="$1"
    # Remove base controller dir prefix
    local rel="${path#app/Http/Controllers/}"
    # Extract domain segment
    # Pattern: Admin/{Domain}/Controller.php or Api/V1/{Domain}/Controller.php
    if [[ "${rel}" =~ ^Admin/([^/]+)/ ]]; then
        echo "${BASH_REMATCH[1]}"
    elif [[ "${rel}" =~ ^Api/V[0-9]+/([^/]+)/ ]]; then
        echo "${BASH_REMATCH[1]}"
    elif [[ "${rel}" =~ ^Admin/([^/]+)Controller\.php$ ]]; then
        echo "${BASH_REMATCH[1]}"
    elif [[ "${rel}" =~ ^Api/V[0-9]+/([^/]+)Controller\.php$ ]]; then
        echo "${BASH_REMATCH[1]}"
    else
        echo "Root"
    fi
}

# ─── Domain extraction from use statement ───
# use App\Models\CRM\... → CRM
# use App\Services\AI\... → AI
# use App\Models\... → Shared
# use App\Services\Wizard\... → Wizard
extract_import_domain() {
    local import="$1"
    if [[ "${import}" =~ App\\Models\\([A-Z][a-zA-Z]+)\\ ]]; then
        echo "${BASH_REMATCH[1]}"
    elif [[ "${import}" =~ App\\Services\\([A-Z][a-zA-Z]+)\\ ]]; then
        echo "${BASH_REMATCH[1]}"
    elif [[ "${import}" =~ App\\Modules\\([A-Z][a-zA-Z]+)\\ ]]; then
        echo "${BASH_REMATCH[1]}"
    elif [[ "${import}" =~ App\\Domain\\([A-Z][a-zA-Z]+)\\ ]]; then
        echo "${BASH_REMATCH[1]}"
    elif [[ "${import}" =~ App\\Domains\\([A-Z][a-zA-Z]+)\\ ]]; then
        echo "${BASH_REMATCH[1]}"
    else
        echo "Shared"
    fi
}

echo "═══════════════════════════════════════════════════════════════"
echo "🔍 CI-GATE-05: Cross Domain Drift Guard"
echo "═══════════════════════════════════════════════════════════════"
echo ""

CURRENT_FILE=$(mktemp)
trap "rm -f '${CURRENT_FILE}'" EXIT

DRIFT_COUNT=0

# ─── Scan controllers for cross-domain imports ───
find "${CONTROLLER_DIR}" -name "*.php" -type f | sort | while IFS= read -r controller; do
    controller_domain=$(extract_domain "${controller}")
    [[ "${controller_domain}" == "Root" ]] && continue

    # Extract use statements (|| true prevents pipefail abort when grep finds nothing)
    use_lines=$(grep -E '^use App\\(Models|Services|Modules|Domain|Domains)\\' "${controller}" 2>/dev/null) || true
    [[ -z "${use_lines}" ]] && continue

    echo "${use_lines}" | while IFS= read -r use_line; do
        import_class=$(echo "${use_line}" | sed 's/^use //;s/;$//')
        import_domain=$(extract_import_domain "${import_class}")

        [[ "${import_domain}" == "Shared" ]] && continue
        [[ "${import_domain}" == "${controller_domain}" ]] && continue

        # Cross-domain import detected
        echo "${controller}|${controller_domain}|${import_domain}|${import_class}" >> "${CURRENT_FILE}"
    done
done

# ─── Apply allowlist ───
if [[ -f "${ALLOWLIST_FILE}" ]] && [[ -s "${CURRENT_FILE}" ]]; then
    FILTERED_FILE=$(mktemp)
    trap "rm -f '${CURRENT_FILE}' '${FILTERED_FILE}'" EXIT
    while IFS= read -r line; do
        allowed=false
        while IFS= read -r allow_pattern; do
            [[ -z "${allow_pattern}" ]] && continue
            [[ "${allow_pattern}" == \#* ]] && continue
            if [[ "${line}" == *"${allow_pattern}"* ]]; then
                allowed=true
                break
            fi
        done < "${ALLOWLIST_FILE}"
        if [[ "${allowed}" == "false" ]]; then
            echo "${line}"
        fi
    done < "${CURRENT_FILE}" > "${FILTERED_FILE}"
    mv "${FILTERED_FILE}" "${CURRENT_FILE}"
fi

CURRENT_COUNT=0
[[ -s "${CURRENT_FILE}" ]] && CURRENT_COUNT=$(wc -l < "${CURRENT_FILE}" | tr -d ' ')

echo "📊 Current cross-domain imports: ${CURRENT_COUNT}"

# ─── Report current violations ───
if [[ ${CURRENT_COUNT} -gt 0 ]]; then
    echo ""
    echo -e "${YELLOW}Cross-domain import map:${NC}"
    head -30 "${CURRENT_FILE}" | while IFS='|' read -r path ctrl_domain imp_domain imp_class; do
        echo "  ⚠️  ${path}"
        echo "      Controller domain: ${ctrl_domain} → imports: ${imp_domain} (${imp_class})"
    done
    if [[ ${CURRENT_COUNT} -gt 30 ]]; then
        echo "  ... and $((CURRENT_COUNT - 30)) more"
    fi
fi

# ─── Generate baseline if requested ───
if [[ "${1:-}" == "--generate-baseline" ]]; then
    mkdir -p "$(dirname "${BASELINE_FILE}")"
    cp "${CURRENT_FILE}" "${BASELINE_FILE}" 2>/dev/null || touch "${BASELINE_FILE}"
    echo -e "${GREEN}✅ Baseline generated: ${CURRENT_COUNT} cross-domain imports${NC}"
    exit 0
fi

# ─── Baseline comparison ───
if [[ ! -f "${BASELINE_FILE}" ]]; then
    echo -e "${YELLOW}⚠️  No baseline found at ${BASELINE_FILE}${NC}"
    echo "   Run with --generate-baseline to create initial baseline"
    echo "   ℹ️  WARN-only mode: no FAIL in Phase 1"
    exit 0
fi

BASELINE_COUNT=$(wc -l < "${BASELINE_FILE}" | tr -d ' ')
echo "📊 Baseline cross-domain imports: ${BASELINE_COUNT}"

# ─── Find NEW drift ───
NEW_VIOLATIONS=$(mktemp)
trap "rm -f '${CURRENT_FILE}' '${NEW_VIOLATIONS}'" EXIT

comm -23 <(sort "${CURRENT_FILE}") <(sort "${BASELINE_FILE}") > "${NEW_VIOLATIONS}" 2>/dev/null || true
NEW_COUNT=0
[[ -s "${NEW_VIOLATIONS}" ]] && NEW_COUNT=$(wc -l < "${NEW_VIOLATIONS}" | tr -d ' ')

if [[ ${NEW_COUNT} -gt 0 ]]; then
    echo ""
    echo -e "${YELLOW}⚠️  WARN: ${NEW_COUNT} NEW cross-domain import(s) detected${NC}"
    echo ""
    echo "New drift:"
    head -10 "${NEW_VIOLATIONS}" | while IFS='|' read -r path ctrl_domain imp_domain imp_class; do
        echo "  ⚠️  ${path}: ${ctrl_domain} → ${imp_domain}"
    done
    echo ""
    echo "Note: Cross-domain guard is WARN-only in Phase 1"
    echo "      Will become FAIL in Phase 2"
    # WARN only — exit 0
    exit 0
fi

if [[ ${CURRENT_COUNT} -lt ${BASELINE_COUNT} ]]; then
    echo -e "${GREEN}✅ PASS — Cross-domain imports decreased: ${BASELINE_COUNT} → ${CURRENT_COUNT} (improvement!)${NC}"
else
    echo -e "${GREEN}✅ PASS — No new cross-domain drift${NC}"
fi

exit 0
