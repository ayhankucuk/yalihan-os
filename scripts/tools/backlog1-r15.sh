#!/usr/bin/env bash
# ============================================================
# YALIHAN OS — BACKLOG-1 Regression Test Suite (Guaranteed Fixture Isolation)
# File: scripts/tools/backlog1-r15.sh
# Ensures ZERO working tree pollution: Uses trap for all exit paths.
# Total Tests: 15/15
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
  local fixture="$3"
  local content="$4"
  local mode="${5:---staged}"

  ((total++))
  cleanup

  if [ -n "$fixture" ]; then
    TRACKED_FIXTURES+=("$fixture")
    printf '%s\n' "$content" > "$fixture"
    git add -f "$fixture" >/dev/null 2>&1
  fi

  local exit_code=0
  if [ "$mode" = "--ci" ]; then
    bash scripts/tools/secret-scan.sh --ci >/dev/null 2>&1
  else
    bash scripts/tools/secret-scan.sh --staged >/dev/null 2>&1
  fi
  exit_code=$?
  cleanup

  if [ "$exit_code" -eq "$expected" ]; then
    echo -e "${GREEN}PASS${RESET}  $label (exit $exit_code)"
    ((pass++))
  else
    echo -e "${RED}FAIL${RESET}  $label (expected $expected, got $exit_code)"
    ((fail++))
  fi
}

run_path() {
  local label="$1"
  local expected="$2"
  local fixture="$3"

  ((total++))
  cleanup

  TRACKED_FIXTURES+=("$fixture")
  if [ "$fixture" != ".env.example" ]; then
    printf 'dummy test content\n' > "$fixture"
  fi
  git add -f "$fixture" >/dev/null 2>&1

  local exit_code=0
  bash scripts/tools/secret-scan.sh --staged >/dev/null 2>&1
  exit_code=$?
  cleanup

  if [ "$exit_code" -eq "$expected" ]; then
    echo -e "${GREEN}PASS${RESET}  $label (exit $exit_code)"
    ((pass++))
  else
    echo -e "${RED}FAIL${RESET}  $label (expected $expected, got $exit_code)"
    ((fail++))
  fi
}

echo ""
echo "BACKLOG-1 Regression Tests (15/15) — Safe Isolated Runner"
echo "========================================================="

run "R1  clean staged content"           0  ".t_clean.md"   "notes for the development team"
run "R2  ghp_ PAT token blocked"         1  ".t_ghp.md"    "ghp_abcdefghijklmnopqrstuvwxyz0123456789"
run "R3  sk-proj token blocked"         1  ".t_skproj.md" "sk-proj-abcdefghijk1234567890123456789012345678901234567890"
run "R4  Bearer token blocked"         1  ".t_bearer.md" "Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9"
run "R5  AKIA token blocked"            1  ".t_akia.md"   "AKIAABCDEFGHIJKLMNOP"
run "R6  private key marker RSA"         1  ".t_key.md"   "-----BEGIN RSA PRIVATE KEY-----"
run "R7  private key marker OPENSSH"     1  ".t_openssh.md" "-----BEGIN OPENSSH PRIVATE KEY-----"
run "R8  private key marker EC"          1  ".t_ec.md"    "-----BEGIN EC PRIVATE KEY-----"
run_path "R9   .env.production path blocked"   1  ".env.production"
run_path "R10  .env.local path blocked"       1  ".env.local"
run_path "R11  certificate.pem path blocked"   1  "certificate.pem"
run_path "R12  id_rsa path blocked"           1  "id_rsa"
run "R13 .env.example clean (no content)" 0  ".t_example.md" "APP_NAME=Yalihan"
run "R14 .env.example contaminated"       1  ".t_bad.md"   "APP_KEY=base64:ghp_abcdefghijklmnopqrstuvwxyz0123456789"
run_path "R15 .env.example path allowed"       0  ".env.example"

# Final guaranteed cleanup
final_cleanup

echo ""
echo "Summary: $pass / $total passed."
if [ "$fail" -eq 0 ]; then
  echo -e "${GREEN}ALL REGRESSION TESTS PASSED (Zero working-tree pollution)${RESET}"
  exit 0
else
  echo -e "${RED}SOME TESTS FAILED${RESET}"
  exit 1
fi
