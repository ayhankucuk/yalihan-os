#!/usr/bin/env bash
# ==============================================================================
# 🛡️ Yalıhan Bekçi MCP Lifecycle — CI/CD Bridge
#
# Bu script CI/CD pipeline'larında MCP server lifecycle yönetimini sağlar.
# MCP server port 4001 üzerinde çalışır, authority.json'dan governance
# kurallarını okur ve health + learning entegrasyonu yapar.
#
# Kullanım:
#   scripts/services/bekci-mcp-lifecycle.sh start    — Başlat (PID kaydet)
#   scripts/services/bekci-mcp-lifecycle.sh stop     — Durdur (PID sil)
#   scripts/services/bekci-mcp-lifecycle.sh status   — Durumu kontrol et
#   scripts/services/bekci-mcp-lifecycle.sh health   — Health check (artisan)
#   scripts/services/bekci-mcp-lifecycle.sh restart — Restart (stop + start)
#
# CI/CD Notları:
#   - CI ortamında MCP server zorunlu DEĞİLDİR — bekci:health --no-mcp kullan
#   - Production deploy'da MCP server ayrı container'da çalışır (bu script değil)
#   - Agent oturumlarında MCP server gereklidir (bekci:health normal mod)
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
PID_FILE="$PROJECT_ROOT/logs/mcp/bekci-health-bridge.pid"
LOG_FILE="$PROJECT_ROOT/logs/mcp/bekci-health-bridge.log"
MCP_PORT=4001
MCP_PID=""

# ---- Helpers ---------------------------------------------------------------

log_info()  { echo "[INFO]  $(date '+%Y-%m-%d %H:%M:%S') — $*"; }
log_warn()  { echo "[WARN]  $(date '+%Y-%m-%d %H:%M:%S') — $*" >&2; }
log_error() { echo "[ERROR] $(date '+%Y-%m-%d %H:%M:%S') — $*" >&2; }

is_port_listening() {
    lsof -ti :"$1" >/dev/null 2>&1
}

is_running() {
    local pid
    pid=$(cat "$PID_FILE" 2>/dev/null || echo "")
    [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null
}

ensure_dirs() {
    mkdir -p "$(dirname "$PID_FILE")"
    mkdir -p "$(dirname "$LOG_FILE")"
    mkdir -p "$PROJECT_ROOT/yalihan-bekci/knowledge"
    mkdir -p "$PROJECT_ROOT/yalihan-bekci/learning"
}

# ---- Actions ---------------------------------------------------------------

do_start() {
    ensure_dirs

    if is_running; then
        log_info "MCP Health Bridge zaten çalışıyor (PID: $(cat "$PID_FILE"))."
        return 0
    fi

    if is_port_listening "$MCP_PORT"; then
        log_warn "Port $MCP_PORT üzerinde başka bir işlem var — PID dosyası güncelleniyor."
        local existing_pid
        existing_pid=$(lsof -ti :"$MCP_PORT")
        echo "$existing_pid" > "$PID_FILE"
        log_info "Mevcut PID $existing_pid bağlandı."
        return 0
    fi

    log_info "Yalıhan Bekçi MCP Health Bridge başlatılıyor (port $MCP_PORT)..."

    cd "$PROJECT_ROOT"
    nohup node mcp-servers/mcp-health-bridge.js \
        >> "$LOG_FILE" 2>&1 &
    local pid=$!
    echo "$pid" > "$PID_FILE"
    log_info "PID $pid başlatıldı."

    # Wait up to 5s for health endpoint
    local ready=0
    for i in $(seq 1 10); do
        if curl -s "http://localhost:$MCP_PORT/health" >/dev/null 2>&1; then
            ready=1
            break
        fi
        sleep 0.5
    done

    if [[ "$ready" -eq 1 ]]; then
        log_info "✅ MCP Health Bridge hazır (PID: $pid, port: $MCP_PORT)"
    else
        log_error "Servis başlatılamadı — log: $LOG_FILE"
        rm -f "$PID_FILE"
        exit 1
    fi
}

do_stop() {
    if ! is_running; then
        log_info "MCP Health Bridge zaten durmuş."
        rm -f "$PID_FILE"
        return 0
    fi

    local pid
    pid=$(cat "$PID_FILE")
    log_info "MCP Health Bridge durduruluyor (PID: $pid)..."
    kill "$pid" 2>/dev/null || true

    # Graceful shutdown wait
    local waited=0
    while kill -0 "$pid" 2>/dev/null && [[ "$waited" -lt 10 ]]; do
        sleep 0.5
        ((waited++))
    done

    if kill -0 "$pid" 2>/dev/null; then
        log_warn "PID $pid zorla sonlandırılıyor..."
        kill -9 "$pid" 2>/dev/null || true
    fi

    rm -f "$PID_FILE"
    log_info "✅ MCP Health Bridge durduruldu."
}

do_status() {
    if is_port_listening "$MCP_PORT"; then
        local pid
        pid=$(lsof -ti :"$MCP_PORT" 2>/dev/null || echo "unknown")
        echo "🟢 MCP Health Bridge AKTİF — PID: $pid — Port: $MCP_PORT"
        curl -s "http://localhost:$MCP_PORT/health" 2>/dev/null || true
    else
        echo "🔴 MCP Health Bridge AKTİF DEĞİL — Port: $MCP_PORT"
    fi
}

do_health() {
    # CI-friendly: skip MCP connectivity check, use --no-mcp
    if is_port_listening "$MCP_PORT"; then
        log_info "MCP server bulundu — full health check çalıştırılıyor..."
        php "$PROJECT_ROOT/artisan" bekci:health --detailed
    else
        log_warn "MCP server çevrimdışı — --no-mcp modunda çalışılıyor..."
        php "$PROJECT_ROOT/artisan" bekci:health --detailed --no-mcp
    fi
}

do_restart() {
    do_stop
    sleep 1
    do_start
}

# ---- CLI dispatch ----------------------------------------------------------

ACTION="${1:-status}"

case "$ACTION" in
    start)
        do_start
        ;;
    stop)
        do_stop
        ;;
    status)
        do_status
        ;;
    health)
        do_health
        ;;
    restart)
        do_restart
        ;;
    *)
        echo "Kullanım: $0 {start|stop|status|health|restart}"
        echo ""
        echo "  start   — MCP Health Bridge'i başlat"
        echo "  stop    — MCP Health Bridge'i durdur"
        echo "  status  — Çalışma durumunu göster"
        echo "  health  — Laravel health check (CI uyumlu, --no-mcp otomatik)"
        echo "  restart — Restart (stop + start)"
        exit 1
        ;;
esac
