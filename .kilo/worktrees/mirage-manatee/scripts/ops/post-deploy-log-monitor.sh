#!/usr/bin/env bash

# SAB Post-Deploy Log Monitor
# Deploy sonrası log dosyasını tarar, hata patternlerini raporlar
# Usage: ./scripts/post-deploy-log-monitor.sh [--deep]

set -euo pipefail

MODE="${1:-quick}"
LOG_FILE="storage/logs/laravel.log"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
REPORT_FILE="storage/logs/log-monitor-${TIMESTAMP}.log"
mkdir -p "$(dirname "$REPORT_FILE")"

if [ ! -f "$LOG_FILE" ]; then
    echo "❌ Log dosyası bulunamadı: $LOG_FILE"
    exit 1
fi

log() { echo "$1" | tee -a "$REPORT_FILE"; }

# How far back to look
if [ "$MODE" = "--deep" ]; then
    SINCE="6 hours ago"
    LINES=5000
    log "🔍 Deep scan modu (son 6 saat)"
else
    SINCE="30 minutes ago"
    LINES=1000
    log "🔍 Quick scan modu (son 30 dakika)"
fi

# Get recent log lines (approximate by line count)
RECENT=$(tail -n "$LINES" "$LOG_FILE")

log ""
log "═══════════════════════════════════════════════════════"
log "  SAB Post-Deploy Log Monitor"
log "  Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
log "  Mode: $MODE"
log "  Scanning last $LINES lines of $LOG_FILE"
log "═══════════════════════════════════════════════════════"
log ""

ISSUES=0

# ── Critical Errors ─────────────────────────────────────
log "🚨 Kritik Hatalar (SQLSTATE / Exception / 500)"
log "────────────────────────────────────────────────"
SQLSTATE_COUNT=$(echo "$RECENT" | grep -c "SQLSTATE" || true)
EXCEPTION_COUNT=$(echo "$RECENT" | grep -c "Exception" || true)
HTTP500_COUNT=$(echo "$RECENT" | grep -c "HTTP 500\|500 Internal\|Server Error" || true)

if [ "$SQLSTATE_COUNT" -gt 0 ]; then
    log "  🚨 SQLSTATE hatası: $SQLSTATE_COUNT adet"
    echo "$RECENT" | grep "SQLSTATE" | head -5 | while IFS= read -r line; do
        log "     → ${line:0:200}"
    done
    ISSUES=$((ISSUES + 1))
else
    log "  ✅ SQLSTATE: 0"
fi

if [ "$EXCEPTION_COUNT" -gt 2 ]; then
    log "  ⚠️  Exception: $EXCEPTION_COUNT adet"
    echo "$RECENT" | grep "Exception" | sed 's/.*\] //' | sort | uniq -c | sort -rn | head -5 | while IFS= read -r line; do
        log "     → $line"
    done
    ISSUES=$((ISSUES + 1))
else
    log "  ✅ Exception: $EXCEPTION_COUNT (normal)"
fi

if [ "$HTTP500_COUNT" -gt 0 ]; then
    log "  🚨 HTTP 500: $HTTP500_COUNT adet"
    ISSUES=$((ISSUES + 1))
else
    log "  ✅ HTTP 500: 0"
fi
log ""

# ── Null / Undefined Errors ─────────────────────────────
log "⚠️  Null / Undefined Hatalar"
log "────────────────────────────"
NULL_COUNT=$(echo "$RECENT" | grep -ci "null\|undefined variable\|Attempt to read property" || true)
if [ "$NULL_COUNT" -gt 3 ]; then
    log "  ⚠️  Null/Undefined: $NULL_COUNT adet"
    echo "$RECENT" | grep -i "null\|undefined variable\|Attempt to read property" | sed 's/.*\] //' | sort | uniq -c | sort -rn | head -5 | while IFS= read -r line; do
        log "     → $line"
    done
    ISSUES=$((ISSUES + 1))
else
    log "  ✅ Null/Undefined: $NULL_COUNT (normal)"
fi
log ""

# ── Auth / Permission Errors ────────────────────────────
log "🔐 Auth / Permission Hatalar"
log "────────────────────────────"
AUTH_COUNT=$(echo "$RECENT" | grep -ci "403\|Unauthorized\|Access Denied\|AuthorizationException\|policy\|gate" || true)
if [ "$AUTH_COUNT" -gt 2 ]; then
    log "  ⚠️  Auth/Permission: $AUTH_COUNT adet"
    echo "$RECENT" | grep -i "403\|Unauthorized\|Access Denied" | head -5 | while IFS= read -r line; do
        log "     → ${line:0:200}"
    done
    ISSUES=$((ISSUES + 1))
else
    log "  ✅ Auth/Permission: $AUTH_COUNT (normal)"
fi
log ""

# ── Queue / Cache / Env ─────────────────────────────────
log "⚙️  Queue / Cache / Env"
log "────────────────────────────"
QUEUE_ERR=$(echo "$RECENT" | grep -ci "queue\|job.*fail\|MaxAttemptsExceededException" || true)
CACHE_ERR=$(echo "$RECENT" | grep -ci "cache.*fail\|Redis.*Connection\|predis" || true)
ENV_ERR=$(echo "$RECENT" | grep -ci "env.*missing\|config.*not found\|APP_KEY" || true)

[ "$QUEUE_ERR" -gt 0 ] && log "  ⚠️  Queue issues: $QUEUE_ERR" && ISSUES=$((ISSUES + 1)) || log "  ✅ Queue: clean"
[ "$CACHE_ERR" -gt 0 ] && log "  ⚠️  Cache issues: $CACHE_ERR" && ISSUES=$((ISSUES + 1)) || log "  ✅ Cache: clean"
[ "$ENV_ERR" -gt 0 ] && log "  ⚠️  Env issues: $ENV_ERR" && ISSUES=$((ISSUES + 1)) || log "  ✅ Env: clean"
log ""

# ── Warning Spam Detection ──────────────────────────────
if [ "$MODE" = "--deep" ]; then
    log "📊 Warning Spam Analizi"
    log "────────────────────────────"
    WARNING_COUNT=$(echo "$RECENT" | grep -c "\.WARNING:" || true)
    if [ "$WARNING_COUNT" -gt 20 ]; then
        log "  ⚠️  Warning spam: $WARNING_COUNT adet"
        echo "$RECENT" | grep "\.WARNING:" | sed 's/\[.*\] //' | sed 's/{.*}//' | sort | uniq -c | sort -rn | head -10 | while IFS= read -r line; do
            log "     → $line"
        done
        ISSUES=$((ISSUES + 1))
    else
        log "  ✅ Warning count: $WARNING_COUNT (normal)"
    fi
    log ""
fi

# ── Repeated Error Pattern (Incident Rule) ──────────────
log "🔁 Tekrar Eden Hata Kontrolü (2+ = incident)"
log "────────────────────────────────────────────────"
REPEATED=$(echo "$RECENT" | grep "\.ERROR:" | sed 's/\[.*\] //' | sed 's/{.*}//' | sort | uniq -c | sort -rn | head -10)
INCIDENT=0
if [ -n "$REPEATED" ]; then
    echo "$REPEATED" | while IFS= read -r line; do
        count=$(echo "$line" | awk '{print $1}')
        if [ "$count" -gt 2 ]; then
            log "  🚨 INCIDENT: $line"
            INCIDENT=1
        else
            log "  ✅ $line"
        fi
    done
else
    log "  ✅ Tekrar eden hata yok"
fi
log ""

# ── Summary ──────────────────────────────────────────────
log "═══════════════════════════════════════════════════════"
if [ $ISSUES -eq 0 ]; then
    log "  ✅ LOG TEMİZ — Sistem sakin görünüyor"
else
    log "  ⚠️  $ISSUES ISSUE CATEGORY DETECTED"
    log "  Detaylı review önerilir."
    log "  Bkz: docs/runbooks/deploy-24h-survival-plan.md"
fi
log "  Report: $REPORT_FILE"
log "═══════════════════════════════════════════════════════"
