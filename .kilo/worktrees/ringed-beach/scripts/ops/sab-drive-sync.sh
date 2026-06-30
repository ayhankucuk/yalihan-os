#!/usr/bin/env bash
set -e

DRIVE_REMOTE="gdrive-personal"
DRIVE_BASE_PATH="Yalihan-Governance"

LOCAL_SNAPSHOTS=".sab/snapshots"
LOCAL_PROPOSALS=".sab/proposals"
LOCAL_LATEST=".sab/latest"
LOCAL_HISTORY=".sab/history"
LOCAL_SPRINT_REPORTS=".sab/sprint-reports"

LOG_DIR="logs"
LOG_FILE="$LOG_DIR/drive-sync.log"

echo "[SYNC] Starting..."

if ! rclone listremotes | grep -q "^${DRIVE_REMOTE}:$"; then
  echo "[SYNC][ERROR] Remote not found: ${DRIVE_REMOTE}"
  exit 1
fi

mkdir -p "$LOCAL_SNAPSHOTS" "$LOCAL_PROPOSALS" "$LOCAL_LATEST" "$LOCAL_HISTORY" "$LOCAL_SPRINT_REPORTS" "$LOG_DIR"
touch "$LOG_FILE"

{
  echo "[SYNC][START] $(date '+%Y-%m-%d %H:%M:%S')"

  rclone mkdir "${DRIVE_REMOTE}:${DRIVE_BASE_PATH}/snapshots"
  rclone mkdir "${DRIVE_REMOTE}:${DRIVE_BASE_PATH}/proposals"
  rclone mkdir "${DRIVE_REMOTE}:${DRIVE_BASE_PATH}/latest"
  rclone mkdir "${DRIVE_REMOTE}:${DRIVE_BASE_PATH}/history"
  rclone mkdir "${DRIVE_REMOTE}:${DRIVE_BASE_PATH}/sprint-reports"

  # Lokal → Drive (backup)
  rclone copy "$LOCAL_SNAPSHOTS" "${DRIVE_REMOTE}:${DRIVE_BASE_PATH}/snapshots" --ignore-existing --checksum
  rclone copy "$LOCAL_LATEST" "${DRIVE_REMOTE}:${DRIVE_BASE_PATH}/latest" --checksum
  rclone copy "$LOCAL_HISTORY" "${DRIVE_REMOTE}:${DRIVE_BASE_PATH}/history" --checksum
  rclone copy "$LOCAL_SPRINT_REPORTS" "${DRIVE_REMOTE}:${DRIVE_BASE_PATH}/sprint-reports" --checksum

  # Drive → Lokal (proposal alma)
  rclone copy "${DRIVE_REMOTE}:${DRIVE_BASE_PATH}/proposals" "$LOCAL_PROPOSALS" --ignore-existing

  echo "[SYNC][DONE] $(date '+%Y-%m-%d %H:%M:%S')"
} >>"$LOG_FILE" 2>&1

echo "[SYNC] Done."
