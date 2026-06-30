#!/usr/bin/env bash
# ==============================================================================
# Yalıhan SAB Watcher — Auto-Run Mode
# Purpose: Periodically execute scripts/sab-run.sh with locking and logging.
# Version: 3.0.0
# ==============================================================================

set -euo pipefail

# --- Config ---
INTERVAL="${SAB_WATCH_INTERVAL:-60}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
cd "$PROJECT_ROOT"

PIPELINE_SCRIPT="scripts/sab-run.sh"
LOG_DIR="logs"
LOG_FILE="$LOG_DIR/sab-watch.log"
LOCK_FILE=".sab/watch.lock"
PID_FILE=".sab/watch.pid"
PIPELINE_LOCK_FILE=".sab/pipeline.lock"
PROPOSALS_DIR=".sab/proposals"

# --- Helpers ---
log() {
    local level="$1"
    local msg="$2"
    local ts
    ts="$(date '+%Y-%m-%d %H:%M:%S')"
    echo "[$ts][WATCH][$level][cycle=$cycle] $msg" | tee -a "$LOG_FILE"
}

cleanup() {
    log "INFO" "Watcher stopping (PID $$)."
    rm -f "$PIPELINE_LOCK_FILE/pid" 2>/dev/null
    rmdir "$PIPELINE_LOCK_FILE" 2>/dev/null || true
    rm -f "$PID_FILE"
    exit 0
}

recover_stale_lock() {
    if [[ -d "$PIPELINE_LOCK_FILE" ]]; then
        local lock_pid
        lock_pid="$(cat "$PIPELINE_LOCK_FILE/pid" 2>/dev/null || echo "")"
        if [[ -n "$lock_pid" ]] && kill -0 "$lock_pid" 2>/dev/null; then
            return 1  # lock is held by a live process
        fi
        # Stale lock — remove
        rm -f "$PIPELINE_LOCK_FILE/pid"
        rmdir "$PIPELINE_LOCK_FILE" 2>/dev/null || true
        log "WARN" "Stale pipeline lock removed (was PID ${lock_pid:-unknown})."
    fi
    return 0
}

# --- Pre-flight checks ---
if [[ ! -f "$PIPELINE_SCRIPT" ]]; then
    echo "[WATCH][FATAL] Pipeline script not found: $PIPELINE_SCRIPT"
    exit 1
fi

if [[ ! -d ".sab" ]]; then
    echo "[WATCH][FATAL] .sab directory not found. SAB not initialized."
    exit 1
fi

mkdir -p "$LOG_DIR"

# --- Lock: prevent double watcher ---
if [[ -f "$PID_FILE" ]]; then
    existing_pid="$(cat "$PID_FILE" 2>/dev/null || true)"
    if [[ -n "$existing_pid" ]] && kill -0 "$existing_pid" 2>/dev/null; then
        echo "[WATCH][ABORT] Another watcher is already running (PID $existing_pid)."
        exit 1
    fi
    # Stale pid file — remove it
    rm -f "$PID_FILE"
fi

echo $$ > "$PID_FILE"
trap cleanup INT TERM EXIT

# --- Start ---
cycle=0
echo "[WATCH] Starting..."
log "INFO" "Watcher started (PID $$, interval ${INTERVAL}s)."

while true; do
    cycle=$((cycle + 1))

    # Check: stale lock recovery + active lock
    if ! recover_stale_lock; then
        echo "[WATCH] Pipeline already running → skip"
        log "SKIP" "Pipeline already running."
        sleep "$INTERVAL"
        continue
    fi

    # Check: proposals exist (*.json only)
    if ! compgen -G "$PROPOSALS_DIR/*.json" > /dev/null 2>&1; then
        echo "[WATCH] No proposals → skip"
        log "SKIP" "No proposals found."
        sleep "$INTERVAL"
        continue
    fi

    # Atomic lock acquire
    if ! mkdir "$PIPELINE_LOCK_FILE" 2>/dev/null; then
        echo "[WATCH] Pipeline lock contention → skip"
        log "SKIP" "Lock contention (mkdir failed)."
        sleep "$INTERVAL"
        continue
    fi
    echo $$ > "$PIPELINE_LOCK_FILE/pid"

    # Run pipeline with duration
    echo "[WATCH] Pipeline started (cycle #$cycle)"
    log "INFO" "Pipeline started."

    start_ts=$(date +%s)
    exit_code=0
    bash "$PIPELINE_SCRIPT" >> "$LOG_FILE" 2>&1 || exit_code=$?
    end_ts=$(date +%s)
    duration=$((end_ts - start_ts))

    # Release lock
    rm -f "$PIPELINE_LOCK_FILE/pid"
    rmdir "$PIPELINE_LOCK_FILE" 2>/dev/null || true

    if [[ "$exit_code" -eq 0 ]]; then
        echo "[WATCH] Pipeline success (${duration}s)"
        log "INFO" "Pipeline success (exit 0, ${duration}s)."
    else
        echo "[WATCH] Pipeline failed (exit $exit_code, ${duration}s)"
        log "ERROR" "Pipeline failed (exit $exit_code, ${duration}s)."
    fi

    echo "[WATCH] Sleeping ${INTERVAL}s"
    sleep "$INTERVAL"
done
