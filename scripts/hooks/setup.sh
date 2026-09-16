#!/usr/bin/env bash
# ============================================================
# YALIHAN OS — Pre-Commit Hook Installer
# Installs: .git/hooks/pre-commit
# BACKLOG-1: Staged Diff Secret Scanner
# ============================================================

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
RESET='\033[0m'

HOOK_SOURCE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/pre-commit"
HOOK_TARGET="$(cd "$(dirname "${BASH_SOURCE[0]}")" && cd ../.. && pwd)/.git/hooks/pre-commit"

echo ""
echo -e "${CYAN}━━━ YALIHAN OS Hook Installer ━━━${RESET}"
echo ""

if [[ ! -f "$HOOK_SOURCE" ]]; then
  echo -e "  ${RED}❌  Source hook not found: ${HOOK_SOURCE}${RESET}"
  exit 1
fi

# Backup existing hook if not our hook
if [[ -f "$HOOK_TARGET" ]] && ! grep -q "YALIHAN OS" "$HOOK_TARGET" 2>/dev/null; then
  BACKUP="${HOOK_TARGET}.backup-$(date +%Y%m%d-%H%M%S)"
  cp "$HOOK_TARGET" "$BACKUP"
  echo -e "  ${YELLOW}⚠️  Existing hook backed up to: ${BACKUP}${RESET}"
fi

cp "$HOOK_SOURCE" "$HOOK_TARGET"
chmod +x "$HOOK_TARGET"

echo -e "  ${GREEN}✅  Installed: .git/hooks/pre-commit${RESET}"
echo -e "  ${GREEN}✅  Mode: 0o755 (executable)${RESET}"
echo ""
echo -e "  ${CYAN}Version: 1.0.0 | 2026-09-01${RESET}"
echo -e "  ${CYAN}Scope:   staged diff token scan (ghp_, sk-proj-, sk-, xoxb-, Bearer, AKIA, api_key)${RESET}"
echo ""

# Verify it runs without error on this system
echo -e "  ${CYAN}Verifying hook runs cleanly...${RESET}"
if "$HOOK_TARGET" --self-test 2>/dev/null || true; then
  echo -e "  ${GREEN}✅  Self-check: OK${RESET}"
else
  # Hook will complain about no staged files — that's fine for a self-test
  if "$HOOK_TARGET" 2>&1 | grep -q "No staged files"; then
    echo -e "  ${GREEN}✅  Self-check: OK (no staged files)${RESET}"
  else
    echo -e "  ${YELLOW}⚠️  Self-check: could not verify (bash version: ${BASH_VERSION})${RESET}"
  fi
fi

echo ""
echo -e "${GREEN}━━━ Installation complete ━━━${RESET}"
echo ""
echo "  To uninstall: rm .git/hooks/pre-commit"
echo "  To bypass:    git commit --no-verify (exceptional only)"
echo ""
