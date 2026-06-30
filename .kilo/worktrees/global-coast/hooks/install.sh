#!/usr/bin/env bash

# ═══════════════════════════════════════════════════════════════════════════
# 🛡️ Git Hooks Installer
# ═══════════════════════════════════════════════════════════════════════════
#
# Installs repo-based hooks to .git/hooks/
# Run once after clone: bash hooks/install.sh
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HOOKS_DIR="${REPO_ROOT}/hooks"
GIT_HOOKS_DIR="${REPO_ROOT}/.git/hooks"

echo "🛡️ Installing Yalıhan Bekçi Git Hooks..."
echo ""

# Check if .git exists
if [ ! -d "${REPO_ROOT}/.git" ]; then
    echo "❌ Error: .git directory not found"
    echo "   Run this script from repository root"
    exit 1
fi

# Install pre-commit hook
if [ -f "${HOOKS_DIR}/pre-commit" ]; then
    cp "${HOOKS_DIR}/pre-commit" "${GIT_HOOKS_DIR}/pre-commit"
    chmod +x "${GIT_HOOKS_DIR}/pre-commit"
    echo "✅ pre-commit hook installed"
else
    echo "⚠️  Warning: hooks/pre-commit not found"
fi

# Install pre-push hook
if [ -f "${HOOKS_DIR}/pre-push" ]; then
    cp "${HOOKS_DIR}/pre-push" "${GIT_HOOKS_DIR}/pre-push"
    chmod +x "${GIT_HOOKS_DIR}/pre-push"
    echo "✅ pre-push hook installed"
else
    echo "⚠️  Warning: hooks/pre-push not found"
fi

echo ""
echo "════════════════════════════════════════"
echo "✅ Git Hooks Installation Complete"
echo "════════════════════════════════════════"
echo ""
echo "🛡️ Pre-commit hook will run SAB integrity check before each commit"
echo "🛡️ Pre-push hook will run full quality gate before each push"
echo ""
echo "To bypass (emergency only):"
echo "  git commit --no-verify"
echo "  git push --no-verify"
echo ""
