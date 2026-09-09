#!/usr/bin/env bash
# ==============================================================================
# 🛡️ Yalıhan Bekçi MCP Health Bridge — Durdurucu Servis
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
PID_FILE="$PROJECT_ROOT/logs/mcp/bekci-health-bridge.pid"

STOPPED=0

if [ -f "$PID_FILE" ]; then
    PID=$(cat "$PID_FILE")
    if kill -0 "$PID" 2>/dev/null; then
        kill "$PID" || true
        echo "🛑 PID $PID sonlandırıldı."
        STOPPED=1
    fi
    rm -f "$PID_FILE"
fi

# Fallback: check port 4001 directly
PORT_PID=$(lsof -ti :4001 2>/dev/null || true)
if [ -n "$PORT_PID" ]; then
    kill -9 $PORT_PID || true
    echo "🛑 Port 4001 dinleyen süreç (PID: $PORT_PID) durduruldu."
    STOPPED=1
fi

if [ "$STOPPED" -eq 1 ]; then
    echo "✅ Yalıhan Bekçi MCP Health Bridge durduruldu."
else
    echo "ℹ️  Çalışan Yalıhan Bekçi servisi bulunamadı."
fi
