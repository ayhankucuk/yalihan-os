#!/usr/bin/env bash
# ==============================================================================
# 🛡️ Yalıhan Bekçi MCP Health Bridge — Başlatıcı Servis
# Port: 4001
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
PID_FILE="$PROJECT_ROOT/logs/mcp/bekci-health-bridge.pid"
LOG_FILE="$PROJECT_ROOT/logs/mcp/bekci-health-bridge.log"

mkdir -p "$(dirname "$PID_FILE")"

# Check if port 4001 is already listening
if lsof -ti :4001 >/dev/null 2>&1; then
    echo "ℹ️  Yalıhan Bekçi MCP Health Bridge zaten port 4001 üzerinde çalışıyor."
    curl -s http://localhost:4001/health || true
    echo ""
    exit 0
fi

echo "🚀 Yalıhan Bekçi MCP Health Bridge başlatılıyor..."

cd "$PROJECT_ROOT"
nohup node mcp-servers/mcp-health-bridge.js >> "$LOG_FILE" 2>&1 &
PID=$!
echo "$PID" > "$PID_FILE"

# Wait up to 5 seconds for health endpoint
READY=0
for i in {1..10}; do
    if curl -s http://localhost:4001/health >/dev/null 2>&1; then
        READY=1
        break
    fi
    sleep 0.5
done

if [ "$READY" -eq 1 ]; then
    echo "✅ Yalıhan Bekçi MCP Health Bridge başarıyla başlatıldı (PID: $PID, Port: 4001)."
    curl -s http://localhost:4001/health
    echo ""
else
    echo "❌ Servis başlatılamadı. Log dosyası: $LOG_FILE"
    exit 1
fi
