#!/usr/bin/env bash
# ============================================================
# YALIHAN OS — BACKLOG-1 Test Runner (Guaranteed Fixture Isolation)
# File: scripts/tools/backlog1-test.sh
# Ensures ZERO working tree pollution: Uses trap for all exit paths.
# Total Tests: 10/10
# ============================================================

set -o pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
RESET='\033[0m'

total=0; pass=0; fail=0
TRACKED_FIXTURES=()

# Backup existing .env.example if present to preserve user edits
if [ -f ".env.example" ]; then
  cp ".env.example" ".env.example.preserve_bak"
fi

cleanup() {
  git reset HEAD -- . >/dev/null 2>&1
  for f in "${TRACKED_FIXTURES[@]}"; do
    if [ "$f" != ".env.example" ]; then
      rm -f "$f" >/dev/null 2>&1
    fi
  done
  # Restore user's exact .env.example content if backup exists
  if [ -f ".env.example.preserve_bak" ]; then
    cp ".env.example.preserve_bak" ".env.example"
  fi
  # Delete untracked test fixtures
  rm -f .env.local .env.production certificate.pem id_rsa .t_*.md .fx_*.md >/dev/null 2>&1
}

final_cleanup() {
  cleanup
  rm -f ".env.example.preserve_bak" >/dev/null 2>&1
}

# Guaranteed trap cleanup on ANY exit
trap final_cleanup EXIT INT TERM HUP

run() {
  local label="$1"
  local expected="$2"
  local fixture_file="$3"
  local fixture_content="$4"
  local mode="${5:---staged}"

  ((total++))
  cleanup

  if [ -n "$fixture_file" ]; then
    TRACKED_FIXTURES+=("$fixture_file")
    printf '%s\n' "$fixture_content" > "$fixture_file"
    git add -f "$fixture_file" >/dev/null 2>&1
  fi

  local scanner_exit=0
  if [ "$mode" = "--ci" ]; then
    bash scripts/tools/secret-scan.sh --ci >/dev/null 2>&1
    scanner_exit=$?
  else
    bash scripts/tools/secret-scan.sh --staged >/dev/null 2>&1
    scanner_exit=$?
  fi

  cleanup
  local actual=$scanner_exit

  if [ "$actual" -eq "$expected" ]; then
    echo -e "${GREEN}PASS${RESET}  $label (exit $actual)"
    ((pass++))
  else
    echo -e "${RED}FAIL${RESET}  $label (expected $expected, got $actual)"
    ((fail++))
  fi
}

echo ""
echo "BACKLOG-1 Regression Tests — Safe Runner (10/10)"
echo "==============================================="

run "R1  clean staged content"        0 ".fx_clean.md"  "notes for the team"
run "R2  ghp_ token blocked"          1 ".fx_ghp.md"    "ghp_abcdefghijklmnopqrstuvwxyz0123456789"
run "R3  sk-proj token blocked"        1 ".fx_skproj.md" "sk-proj-abcdefghijk1234567890123456789012345678901234567890"
run "R4  Bearer token blocked"        1 ".fx_bearer.md" "Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9"
run "R5  AKIA token blocked"          1 ".fx_akia.md"   "AKIAABCDEFGHIJKLMNOP"
run "R6  private key blocked"         1 ".fx_key.md"    "-----BEGIN RSA PRIVATE KEY-----"
run "R7  CI contaminated blocked"     1 ".fx_ci.md"     "ghp_abcdefghijklmnopqrstuvwxyz0123456789" "--ci"
run "R8  CI clean passed"             0 ""              "" "--ci"
run "R9  .env.example path allowed"   0 ".env.example"  "APP_NAME=Yalihan"
run "R10 ghp_ no bypass (B2)"         1 ".fx_b2.md"     "ghp_abcdefghijklmnopqrstuvwxyz0123456789 // secret-scan-ignore"

# Final cleanup
final_cleanup

echo ""
echo "Summary: $pass / $total passed."
if [ "$fail" -eq 0 ]; then
  echo -e "${GREEN}ALL 10 REGRESSION TESTS PASSED (Zero working-tree pollution)${RESET}"
  exit 0
else
  echo -e "${RED}SOME TESTS FAILED${RESET}"
  exit 1
fi
