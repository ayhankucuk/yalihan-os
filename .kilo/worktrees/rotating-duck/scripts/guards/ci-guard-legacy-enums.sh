#!/usr/bin/env bash
set -euo pipefail

# Blocks known legacy enum names that were removed during canonical migration.
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if command -v rg >/dev/null 2>&1; then
    MATCHES="$(rg -n --no-heading --glob '!storage/**' --glob '!vendor/**' --glob '!node_modules/**' "App\\\\Enums\\\\ListingStatus|App\\\\Enums\\\\ListingDurum|\\\\App\\\\Enums\\\\ListingStatus|\\\\App\\\\Enums\\\\ListingDurum" app resources routes tests config || true)"
else
    MATCHES="$(grep -RInE "App\\\\Enums\\\\ListingStatus|App\\\\Enums\\\\ListingDurum|\\\\App\\\\Enums\\\\ListingStatus|\\\\App\\\\Enums\\\\ListingDurum" app resources routes tests config 2>/dev/null || true)"
fi

if [[ -n "${MATCHES}" ]]; then
    echo "[LEGACY ENUM GUARD] Forbidden enum references detected:"
    echo "${MATCHES}"
    echo "[LEGACY ENUM GUARD] Use App\\Enums\\IlanDurumu instead."
    exit 1
fi

echo "[LEGACY ENUM GUARD] PASS: No forbidden legacy enum references found."
