#!/usr/bin/env bash
set -e

echo "[PIPELINE] Sync starting..."
bash scripts/sab-drive-sync.sh

echo "[PIPELINE] Apply starting..."
bash scripts/sab-apply.sh

echo "[PIPELINE] Done."
