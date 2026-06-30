#!/usr/bin/env bash

set -euo pipefail

TARGET_FILE="resources/views/admin/layouts/sidebar.blade.php"

echo "[verify-sidebar-dark] Target: ${TARGET_FILE}"

# Prefer SAB scan (current active scanner in this repo).
if php artisan list --raw | grep -qE '^sab:integrity-scan[[:space:]]'; then
  TMP_OUTPUT="$(mktemp)"

  set +e
  php artisan sab:integrity-scan --path="${TARGET_FILE}" >"${TMP_OUTPUT}" 2>&1
  EXIT_CODE=$?
  set -e

  # Show only dark-mode related findings if any.
  if grep -Eiq 'missing dark mode variant|context7 violation: missing dark mode variant|dark mode variant' "${TMP_OUTPUT}"; then
    echo "[verify-sidebar-dark] FAILED: dark-mode violations found"
    grep -Ein 'missing dark mode variant|context7 violation: missing dark mode variant|dark mode variant' "${TMP_OUTPUT}" | cat
    rm -f "${TMP_OUTPUT}"
    exit 1
  fi

  # If scan fails for unrelated checks, report clearly but keep this helper scoped.
  if [[ ${EXIT_CODE} -ne 0 ]]; then
    echo "[verify-sidebar-dark] PASSED for dark-mode checks (scanner reported other violations)."
    echo "[verify-sidebar-dark] Tip: run full report if needed: php artisan sab:integrity-scan --path=${TARGET_FILE}"
    rm -f "${TMP_OUTPUT}"
    exit 0
  fi

  echo "[verify-sidebar-dark] PASSED: no dark-mode violations detected"
  rm -f "${TMP_OUTPUT}"
  exit 0
fi

echo "[verify-sidebar-dark] ERROR: 'sab:integrity-scan' command not found"
exit 2
