#!/usr/bin/env bash

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

BASE_DIR="${CI_GUARD_BASE_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
REGISTRY="${BASE_DIR}/docs/governance/final_registry.md"
README="${BASE_DIR}/README.md"
CHANGELOG="${BASE_DIR}/CHANGELOG.md"

violations=0

fail() {
  echo -e "${RED}❌ $1${NC}"
  violations=$((violations + 1))
}

pass() {
  echo -e "${GREEN}✅ $1${NC}"
}

extract_state() {
  local key="$1"
  sed -n "s/^${key}: *//p" "${REGISTRY}" \
    | head -n1 \
    | tr -d '\r' \
    | sed 's/[[:space:]]*$//'
}

echo "🔍 Governance Registry Drift Guard"
echo "   SSOT: docs/governance/final_registry.md"

if [ ! -f "${REGISTRY}" ]; then
  fail "Registry file missing: ${REGISTRY}"
fi
if [ ! -f "${README}" ]; then
  fail "README missing: ${README}"
fi
if [ ! -f "${CHANGELOG}" ]; then
  fail "CHANGELOG missing: ${CHANGELOG}"
fi

if [ "${violations}" -gt 0 ]; then
  echo -e "${RED}❌ Governance Registry Guard FAILED (${violations} violations)${NC}"
  exit 1
fi

# SSOT header check
if grep -q "Single Source of Truth (SSOT) for governance state" "${REGISTRY}" \
  && grep -q "README and CHANGELOG are derived summaries" "${REGISTRY}"; then
  pass "Registry SSOT header present"
else
  fail "Registry SSOT header missing/incomplete"
fi

# Pull canonical states from registry
BOOTSTRAP_STATE="$(extract_state "Bootstrap")"
PROPERTY_STATE="$(extract_state "Property Engine")"
LIFECYCLE_STATE="$(extract_state "Listing Lifecycle")"
FINANCE_STATE="$(extract_state "Finance")"

for v in BOOTSTRAP_STATE PROPERTY_STATE LIFECYCLE_STATE FINANCE_STATE; do
  if [ -z "${!v}" ]; then
    fail "Missing state in registry: ${v}"
  fi
done

# README must clearly reference SSOT and include matching state snapshot lines
if grep -q "Governance SSOT: \`docs/governance/final_registry.md\`" "${README}"; then
  pass "README references governance SSOT"
else
  fail "README missing SSOT reference to docs/governance/final_registry.md"
fi

for pair in \
  "Bootstrap:${BOOTSTRAP_STATE}" \
  "Property Engine:${PROPERTY_STATE}" \
  "Listing Lifecycle:${LIFECYCLE_STATE}" \
  "Finance:${FINANCE_STATE}"; do
  key="${pair%%:*}"
  value="${pair#*:}"
  if grep -Eq "^- ${key}: ${value}$" "${README}"; then
    pass "README state matches: ${key}=${value}"
  else
    fail "README drift: expected '- ${key}: ${value}'"
  fi
done

# CHANGELOG must carry a derived decision entry aligned with registry
if grep -q "Governance Seal — Full Platform Stabilization" "${CHANGELOG}"; then
  pass "CHANGELOG governance decision section present"
else
  fail "CHANGELOG missing governance decision section"
fi

if grep -q "System is now governance-accepted." "${CHANGELOG}"; then
  pass "CHANGELOG governance acceptance line present"
else
  fail "CHANGELOG missing governance acceptance line"
fi

for pair in \
  "Bootstrap:${BOOTSTRAP_STATE}" \
  "Property Engine:${PROPERTY_STATE}" \
  "Listing Lifecycle:${LIFECYCLE_STATE}" \
  "Finance:${FINANCE_STATE}"; do
  key="${pair%%:*}"
  value="${pair#*:}"
  if grep -Eq "^- ${key} -> ${value}$" "${CHANGELOG}"; then
    pass "CHANGELOG decision matches: ${key}=${value}"
  else
    fail "CHANGELOG drift: expected '- ${key} -> ${value}'"
  fi
done

if [ "${violations}" -eq 0 ]; then
  echo -e "${GREEN}✅ Governance Registry Guard: PASSED${NC}"
  exit 0
fi

echo -e "${RED}❌ Governance Registry Guard: FAILED (${violations} violations)${NC}"
exit 1
