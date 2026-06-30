#!/usr/bin/env bash
set -euo pipefail

cmd="$1"
shift || true

mkdir -p storage/logs
ts="$(date +"%Y%m%d-%H%M%S")"
log="storage/logs/vscode-${cmd}-${ts}.log"

echo "▶️  Running: ${cmd} $*" | tee -a "$log"

case "$cmd" in
  quality-gate)
    bash scripts/quality-gate.sh "$@" 2>&1 | tee -a "$log"
    ;;
  nightly)
    bash scripts/nightly.sh "$@" 2>&1 | tee -a "$log"
    ;;
  smoke)
    bash scripts/smoke.sh "$@" 2>&1 | tee -a "$log"
    ;;
  health-check)
    bash scripts/health-check.sh "$@" 2>&1 | tee -a "$log"
    ;;
  bekci)
    php artisan bekci:wizard-contract "$@" 2>&1 | tee -a "$log"
    ;;
  context7)
    php artisan sab:integrity-scan "$@" 2>&1 | tee -a "$log"
    ;;
  *)
    echo "Unknown cmd: $cmd" | tee -a "$log"
    exit 2
    ;;
esac

echo "✅ DONE. Log: $log" | tee -a "$log"
