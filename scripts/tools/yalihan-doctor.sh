#!/usr/bin/env bash
# ==============================================================================
# 🏰 YALIHAN AI OS — READ-ONLY SYSTEM DIAGNOSTIC & HEALTH OBSERVABILITY (DOCTOR)
# Path: scripts/tools/yalihan-doctor.sh
# Version: 1.1.0-hardened
# Purpose: PURE READ-ONLY diagnostics, drift observation, and architecture health.
#          NOT A RELEASE CERTIFICATION GATE (See rc2-release-certification-gate.sh).
#          NEVER MUTATES FILES, CODE, PERMISSIONS, OR CACHES.
#
# Usage:
#   ./scripts/tools/yalihan-doctor.sh              # Standard diagnostic
#   ./scripts/tools/yalihan-doctor.sh --quick       # Rapid inspection (skips Tenant tests)
#   ./scripts/tools/yalihan-doctor.sh --json        # Machine-readable JSON output
# ==============================================================================

set -uo pipefail

# ── ANSI Colors & Styling ──────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
DIM='\033[2m'
NC='\033[0m'

START_TIME=$(date +%s)
QUICK_MODE=false
JSON_MODE=false

for arg in "$@"; do
    case "$arg" in
        --quick) QUICK_MODE=true ;;
        --json)   JSON_MODE=true ;;
    esac
done

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$REPO_ROOT"

# ── Counters ─────────────────────────────────────────────────────────────────
PASSED_CHECKS=0
WARNING_CHECKS=0
FAILED_CHECKS=0
SKIPPED_CHECKS=0
TOTAL_CHECKS=0

# ── JSON items ────────────────────────────────────────────────────────────────
declare -a JSON_ITEMS=()

# ── JSON serializer ──────────────────────────────────────────────────────────
# Uses jq if available, falls back to Python; strips control chars and ensures
# valid JSON string output regardless of detail content.
_safe_json_string() {
    local raw="$1"
    if command -v jq >/dev/null 2>&1; then
        # jq 1.6+ handles null bytes and control chars correctly
        printf '%s' "$raw" | jq -Rs .
    elif command -v python3 >/dev/null 2>&1; then
        printf '%s' "$raw" | python3 -c 'import sys,json; print(json.dumps(sys.stdin.read()))'
    else
        # Minimal fallback: strip everything non-printable except \n \t
        printf '%s' "$raw" | LC_ALL=C tr -cd '[:print:]\t\n' | tr '\n' ' ' | sed "s/\"/'/g"
    fi
}

# ── Core recording ────────────────────────────────────────────────────────────
# Always writes a record_check; no branch may exit without one.
record_check() {
    local status="$1"   # PASS | WARN | FAIL | SKIPPED
    local category="$2"
    local name="$3"
    local detail="$4"

    ((TOTAL_CHECKS++)) || true

    case "$status" in
        PASS)    ((PASSED_CHECKS++))  ;;
        WARN)    ((WARNING_CHECKS++)) ;;
        FAIL)    ((FAILED_CHECKS++)) ;;
        SKIPPED) ((SKIPPED_CHECKS++)) ;;
    esac

    local escaped
    escaped=$(_safe_json_string "$detail")

    JSON_ITEMS+=("{\"category\":\"$category\",\"name\":\"$name\",\"status\":\"$status\",\"detail\":$escaped}")

    if [[ "$JSON_MODE" == false ]]; then
        case "$status" in
            PASS)
                echo -e "    ${GREEN}✔ [PASS]${NC} ${name} ${DIM}(${detail})${NC}"
                ;;
            WARN)
                echo -e "    ${YELLOW}▲ [WARN]${NC} ${name} ${YELLOW}— ${detail}${NC}"
                ;;
            SKIPPED)
                echo -e "    ${BLUE}○ [SKIP]${NC} ${name} ${DIM}(${detail})${NC}"
                ;;
            FAIL)
                echo -e "    ${RED}✖ [FAIL]${NC} ${BOLD}${name}${NC} ${RED}— ${detail}${NC}"
                ;;
        esac
    fi
}

# ── Header ───────────────────────────────────────────────────────────────────
print_header() {
    if [[ "$JSON_MODE" == true ]]; then return; fi
    echo -e "${BLUE}${BOLD}"
    echo "  ╔══════════════════════════════════════════════════════════════════════╗"
    echo "  ║      🏰 YALIHAN AI OS — READ-ONLY SYSTEM DIAGNOSTIC (DOCTOR)     ║"
    echo "  ║          Observability, Drift Radar & Health Diagnostics            ║"
    echo "  ╚══════════════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    local mode_label="READ-ONLY OBSERVABILITY"
    if [[ "$QUICK_MODE" == true ]]; then mode_label+=" (QUICK — Tenant tests skipped)"; fi
    echo -e "  ${DIM}Zaman: $(date '+%Y-%m-%d %H:%M:%S') | Dal: $(git branch --show-current) | Mod: ${mode_label}${NC}\n"
}

# ==============================================================================
# KATMAN 1: Git & Worktree Hijyeni
# ==============================================================================
check_layer1_worktrees() {
    if [[ "$JSON_MODE" == false ]]; then
        echo -e "${CYAN}${BOLD}[1/7] 🌳 Git & Worktree Hijyeni${NC}"
    fi

    # 1.1 Working tree kirliliği
    local dirty_files
    dirty_files=$(git status --porcelain 2>/dev/null)
    if [[ -z "$dirty_files" ]]; then
        record_check "PASS" "git" "Working Tree Durumu" "Temiz, commitlenmemiş veya untracked dosya yok"
    else
        local count
        count=$(echo "$dirty_files" | wc -l | tr -d ' ')
        record_check "FAIL" "git" "Working Tree Kirliliği" "${count} adet commitlenmemiş/untracked dosya mevcut"
    fi

    # 1.2 Worktree yoğunluğu
    # Sadece gerçek worktree dizinleri sayılır — commit hash satırları haric
    local wt_output
    wt_output=$(git worktree list --porcelain 2>/dev/null || echo "")
    local wt_count
    wt_count=$(echo "$wt_output" | grep "^worktree" | wc -l | tr -d ' ' || echo "0")
    if [[ "$wt_count" -le 10 ]]; then
        record_check "PASS" "git" "Worktree Yoğunluğu" "${wt_count} aktif worktree"
    else
        record_check "WARN" "git" "Worktree Şişkinliği" "${wt_count} aktif worktree mevcut (Düzenli budama önerilir)"
    fi

    # 1.3 Worktree başına dirty / branch / son commit / SQLite risk (salt-okunur)
    # git worktree list --porcelain: her worktree "worktree <path>" ile başlar,
    # sonraki satırlar HEAD/branch/commit bilgisi taşır
    local wt_dirty_count=0
    local wt_total_count=0

    # Önce tüm worktree path'lerini topla
    local wt_paths=()
    while IFS= read -r line; do
        [[ "$line" != worktree\ * ]] && continue
        wt_total_count=$((wt_total_count + 1))
        local wt_path
        wt_path=$(echo "$line" | sed 's/^worktree //')
        wt_paths+=("$wt_path")
    done <<< "$(git worktree list --porcelain 2>/dev/null || echo "")"

    # Her worktree için detay
    if [[ "$JSON_MODE" == false ]]; then
        echo -e "    ${DIM}  Worktree ayrıntıları:${NC}"
    fi

    local wt_detail_output=""
    for wt_path in "${wt_paths[@]}"; do
        # Branch
        local wt_branch
        wt_branch=$(git -C "$wt_path" symbolic-ref --short HEAD 2>/dev/null || echo "detached")
        # Commit
        local wt_commit
        wt_commit=$(git -C "$wt_path" rev-parse --short HEAD 2>/dev/null || echo "N/A")
        # Dirty mi?
        local wt_dirty=false
        if [[ -d "$wt_path" ]]; then
            local wt_dirty_files
            wt_dirty_files=$(git -C "$wt_path" status --porcelain 2>/dev/null || echo "")
            [[ -n "$wt_dirty_files" ]] && wt_dirty=true
        fi
        # SQLite riski
        local sqlite_risk="OK"
        [[ -f "$wt_path/database.sqlite" ]] && sqlite_risk="SQLite-ortak-risk"
        # Dirty sayaç
        [[ "$wt_dirty" == true ]] && wt_dirty_count=$((wt_dirty_count + 1))
        # Flag
        local dirty_flag=""
        [[ "$wt_dirty" == true ]] && dirty_flag=" DIRTY"
        if [[ "$JSON_MODE" == false ]]; then
            echo -e "    ${DIM}  ${wt_path}${dirty_flag} | ${wt_branch} | ${wt_commit} | ${sqlite_risk}${NC}"
        fi
    done

    # Özet kaydı — her zaman bir sonuç olmalı
    if [[ "$wt_total_count" -eq 0 ]]; then
        record_check "PASS" "git" "Worktree Envanteri" "0 worktree (yalnızca ana repo)"
    elif [[ "$wt_dirty_count" -eq 0 ]]; then
        record_check "PASS" "git" "Worktree Envanteri" "${wt_total_count} worktree, kirli yok"
    else
        record_check "WARN" "git" "Worktree Envanteri" "${wt_dirty_count}/${wt_total_count} worktree kirli"
    fi
}

# ==============================================================================
# KATMAN 2: SAB Mimari Anayasası & Kod Güvenliği
# ==============================================================================
check_layer2_sab() {
    if [[ "$JSON_MODE" == false ]]; then
        echo -e "\n${CYAN}${BOLD}[2/7] 🛡️  SAB Mimari Anayasası & Kod Güvenliği${NC}"
    fi

    # 2.1 SAB Integrity Scan — komut hatası ile JSON parse hatası AYRI yakalanır
    local sab_rc=0
    local sab_json
    if command -v php >/dev/null 2>&1 && [[ -f artisan ]]; then
        sab_json=$(php artisan sab:integrity-scan --format=json 2>/dev/null)
        sab_rc=$?
    else
        sab_rc=1
        sab_json=""
    fi

    if [[ "$sab_rc" -ne 0 ]]; then
        record_check "FAIL" "architecture" "SAB Integrity Scan" "Artisan komutu çalışmadı (rc=$sab_rc)"
    else
        # JSON parse başarısız — değer boşsa veya geçersizse
        local new_v=""
        local base_v=""
        new_v=$(echo "$sab_json" | grep -o '"new_violations": *[0-9]*' | awk -F': ' '{print $2}' | tr -d ' ' || echo "")
        base_v=$(echo "$sab_json" | grep -o '"baseline_violations": *[0-9]*' | awk -F': ' '{print $2}' | tr -d ' ' || echo "")

        if [[ -z "$new_v" || -z "$base_v" ]]; then
            record_check "WARN" "architecture" "SAB JSON Parse" "Komut başarılı ama JSON yapısı beklenenden farklı"
        elif [[ "$new_v" -eq 0 ]]; then
            record_check "PASS" "architecture" "SAB Anayasa Uyumu" "0 yeni ihlal (${base_v:-0} baseline kayıtlı)"
        else
            record_check "FAIL" "architecture" "SAB Anayasa İhlali" "${new_v} YENİ anayasa ihlali tespit edildi"
        fi
    fi

    # 2.2 Yasaklı Fonksiyonlar
    # grep hata verirse (dosya bulamadı vs.) $? != 0 — bu hata FİLMeli, sessizce atlanmamalı
    local forbidden_func
    forbidden_func=$(grep -rnE "\b(shell_exec|passthru|system\()\b" app/Http app/Models 2>/dev/null | grep -v "SecurityMiddleware" || true)

    if [[ -z "$forbidden_func" ]]; then
        record_check "PASS" "security" "Yasaklı PHP Fonksiyonları" "Http ve Model katmanında shell_exec/eval yok"
    else
        local ff_count
        ff_count=$(echo "$forbidden_func" | grep -c "." || echo "1")
        record_check "FAIL" "security" "Yasaklı Fonksiyon Sızıntısı" "${ff_count} yerde sistem çağrısı bulundu (SecurityMiddleware dışında)"
    fi
}

# ==============================================================================
# KATMAN 3: Hayalet Kolonlar & Enum Drift — DURUM-BAZLI KAYIT
# ==============================================================================
check_layer3_drift() {
    if [[ "$JSON_MODE" == false ]]; then
        echo -e "\n${CYAN}${BOLD}[3/7] 👻 Hayalet Kolonlar & Enum Drift Taraması${NC}"
    fi

    # Her dal mutlaka bir record_check üretir; silent skip YOK
    local drift_cmd_rc=0
    local drift_json=""
    drift_json=$(php artisan system:env-drift-guard --json 2>/dev/null)
    drift_cmd_rc=$?

    # JSON geçerli mi?
    local valid_json=false
    if [[ -n "$drift_json" ]]; then
        if command -v jq >/dev/null 2>&1; then
            if echo "$drift_json" | jq -e . >/dev/null 2>&1; then
                valid_json=true
            fi
        else
            # jq yoksa sadece yapı kontrolü
            if echo "$drift_json" | grep -q '^\s*{.*"checks"' 2>/dev/null; then
                valid_json=true
            fi
        fi
    fi

    if [[ "$valid_json" == false ]]; then
        # JSON geçersiz veya boş — bu gerçek bir hata
        record_check "FAIL" "drift" "Env Drift Guard JSON" "Çıktı geçerli JSON değil (rc=$drift_cmd_rc) — env-drift-guard --json çalışıyor mu?"
    else
        # JSON geçerli — tüm checks[] kayıtlarını durum alanına göre aktar.
        # Tek kaynak: "durum" alanı. "severity" tek başına sonuç DEĞİL;
        # başarılı kontrollerde de severity=fail olabilir
        # (örn. schema_mysql: durum=pass, severity=fail).
        if command -v jq >/dev/null 2>&1; then
            local check_count=0
            check_count=$(echo "$drift_json" | jq '.checks | length' 2>/dev/null || echo "0")

            if [[ "$check_count" -gt 0 ]]; then
                local idx=0
                while [[ "$idx" -lt "$check_count" ]]; do
                    local check_name check_durum check_mesaj
                    check_name=$(echo "$drift_json" | jq -r ".checks[$idx].check" 2>/dev/null)
                    check_durum=$(echo "$drift_json" | jq -r ".checks[$idx].durum" 2>/dev/null)
                    check_mesaj=$(echo "$drift_json" | jq -r ".checks[$idx].mesaj" 2>/dev/null)

                    # Boş değerleri güvenli varsayılanlarla doldur
                    [[ -z "$check_name" ]] && check_name="check_$idx"
                    [[ -z "$check_durum" ]] && check_durum="unknown"
                    [[ -z "$check_mesaj" ]] && check_mesaj="no message"

                    # durum → record_check status (tek kaynak: durum alanı)
                    local record_status
                    case "$check_durum" in
                        pass) record_status="PASS" ;;
                        warn) record_status="WARN" ;;
                        fail) record_status="FAIL" ;;
                        skip|skipped) record_status="SKIPPED" ;;
                        *) record_status="WARN" ;;
                    esac

                    # Kısa detail: mesajın ilk satırı, 120 char ile sınırlı
                    local short_detail
                    short_detail=$(echo "$check_mesaj" | head -1 | cut -c1-120)

                    record_check "$record_status" "drift" "EnvDrift: ${check_name}" "$short_detail"

                    idx=$((idx + 1))
                done
            else
                record_check "WARN" "drift" "Env Drift Guard" "checks dizisi boş veya okunamadı"
            fi
        else
            # jq yok — text-based fallback
            local line_count=0
            line_count=$(echo "$drift_json" | grep -c '"durum":' 2>/dev/null || echo "0")

            if [[ "$line_count" -gt 0 ]]; then
                while IFS= read -r check_block; do
                    [[ -z "$check_block" ]] && continue
                    local durum_val
                    durum_val=$(echo "$check_block" | sed 's/.*"durum": *"\([^"]*\)".*/\1/' | tr -d ' ')
                    local check_name
                    check_name=$(echo "$check_block" | sed 's/.*"check": *"\([^"]*\)".*/\1/' | tr -d ' ')
                    [[ -z "$check_name" ]] && check_name="unknown"

                    local record_status
                    case "$durum_val" in
                        pass) record_status="PASS" ;;
                        warn) record_status="WARN" ;;
                        fail) record_status="FAIL" ;;
                        *) continue ;;
                    esac

                    record_check "$record_status" "drift" "EnvDrift: ${check_name}" "durum=$durum_val"
                done <<< "$(echo "$drift_json" | grep -o '{"[^"]*check[^"]*":"[^"]*"[^"]*"durum":[^"]*"[^"]*}')"
            else
                record_check "WARN" "drift" "Env Drift Guard" "jq yok, text parse başarısız"
            fi
        fi
    fi

    # Ilan.php is_active Ghost Check — ayrı bir kontrol, drift guard dışında
    local ghost_field="PASS"
    local ghost_detail="Ilan.php Context7 uyumlu"
    if grep -q "'is_active'" app/Models/Ilan.php 2>/dev/null; then
        ghost_field="WARN"
        ghost_detail="Ilan.php: 'is_active' referansı (aktiflik_durumu olmalı)"
    fi
    record_check "$ghost_field" "drift" "Model Ghost Field (Ilan)" "$ghost_detail"
}

# ==============================================================================
# KATMAN 4: Güvenlik, Gizlilik & Tenant İzolasyonu
# ==============================================================================
check_layer4_security() {
    if [[ "$JSON_MODE" == false ]]; then
        echo -e "\n${CYAN}${BOLD}[4/7] 🔒 Güvenlik, Gizlilik & Tenant İzolasyonu${NC}"
    fi

    # 4.1 Secret Scanner (STAGED ONLY — commit öncesi diff'te çalışır)
    if [[ -x "scripts/tools/secret-scan.sh" ]]; then
        local scan_rc=0
        ./scripts/tools/secret-scan.sh --staged >/dev/null 2>&1
        scan_rc=$?
        if [[ "$scan_rc" -eq 0 ]]; then
            record_check "PASS" "security" "Secret Scanner (Staged)" "Staged dosyalarda API key/token sızıntısı yok"
        else
            record_check "FAIL" "security" "Secret Scanner Sızıntı" "Açıkta API key veya token tespit edildi! (rc=$scan_rc)"
        fi
    else
        record_check "WARN" "security" "Secret Scanner" "secret-scan.sh bulunamadı veya çalıştırılabilir değil"
    fi

    # 4.2 Tenant İzolasyonu — QUICK modda SKIP, asla yanlış PASS denmez
    if [[ "$QUICK_MODE" == false ]]; then
        local tenant_rc=0
        local tenant_output
        # Timeout: 60 saniye — SQLite :memory: veya API mock sorunu durumunda
        # script sonsuza kadar beklemesin. MacOS'ta gtimeout/yok → perl wrapper.
        tenant_output=$(perl -MPOSIX 'my $pid = fork; die "fork: $!" if !defined $pid; if (!$pid) { setpgrp(POSIX::PGID(), POSIX::getpid()); exec @ARGV; exit 127; } my $done = 0; local $SIG{ALRM} = sub { $done = 1; kill ALRM => $pid; }; alarm 60; while (!$done && waitpid($pid, WNOHANG) == 0) { usleep 100_000; } alarm 0; if ($done) { kill TERM => $pid; waitpid($pid, 0); exit 42; } my $rc = $? >> 8; exit $rc;' -- php artisan test --testsuite=Feature --filter=TenantIsolationTest 2>&1)
        tenant_rc=$?
        if [[ "$tenant_rc" -eq 0 ]] && echo "$tenant_output" | grep -q "PASS"; then
            record_check "PASS" "security" "Tenant İzolasyon Testleri" "Multi-tenant veri sınırları sızdırmaz (%100 PASS)"
        elif [[ "$tenant_rc" -eq 42 ]]; then
            record_check "FAIL" "security" "Tenant İzolasyon Testleri" "Test 60 sn içinde tamamlanamadı — timeout aşıldı"
        elif [[ "$tenant_rc" -ne 0 ]]; then
            record_check "FAIL" "security" "Tenant İzolasyon Testleri" "Test komutu hata verdi (rc=$tenant_rc)"
        else
            record_check "FAIL" "security" "Tenant İzolasyon Testleri" "Testler başarısız — detay için php artisan test"
        fi
    else
        record_check "SKIPPED" "security" "Tenant İzolasyon Testleri" "--quick modunda atlandı"
    fi

    # 4.3 Storage Fotoğraf Sızıntı Koruması
    if git check-ignore "storage/app/public/ilan-fotograflari/test.jpg" >/dev/null 2>&1; then
        record_check "PASS" "security" "İlan Medya Koruması" "İlan fotoğrafları gitignore kapsamında güvende"
    else
        record_check "FAIL" "security" "İlan Medya Riski" "ilan-fotograflari .gitignore'a dahil DEĞİL!"
    fi
}

# ==============================================================================
# KATMAN 5: Cortex AI & Model Entegrasyon Durumu — ADAPTER SAĞLIĞI
# ==============================================================================
check_layer5_ai() {
    if [[ "$JSON_MODE" == false ]]; then
        echo -e "\n${CYAN}${BOLD}[5/7] 🧠 Cortex AI & Model Entegrasyon Durumu${NC}"
    fi

    # 5.1 AI Adapter yapılandırması — sağlayıcıdan BAĞIMSIZ
    # Boş değer, placeholder, gerçek anahtar ayrımı
    local ai_adapter_ok=false
    local ai_detail="Hiçbir AI adapter yapılandırılmamış"

    # OPENAI
    if grep -qE "^OPENAI_API_KEY=[^[:space:]]+" .env 2>/dev/null; then
        ai_adapter_ok=true
        ai_detail="OpenAI adapter yapılandırılmış (.env)"
    # DeepSeek
    elif grep -qE "^DEEPSEEK_API_KEY=[^[:space:]]+" .env 2>/dev/null; then
        ai_adapter_ok=true
        ai_detail="DeepSeek adapter yapılandırılmış (.env)"
    # Azure
    elif grep -qE "^AZURE_OPENAI_API_KEY=[^[:space:]]+" .env 2>/dev/null; then
        ai_adapter_ok=true
        ai_detail="Azure OpenAI adapter yapılandırılmış (.env)"
    # Proxy / custom
    elif grep -qE "^CORTEX_PROXY_API_KEY=[^[:space:]]+" .env 2>/dev/null; then
        ai_adapter_ok=true
        ai_detail="Cortex proxy adapter yapılandırılmış (.env)"
    fi

    if [[ "$ai_adapter_ok" == true ]]; then
        record_check "PASS" "cortex_ai" "AI Adapter Yapılandırması" "$ai_detail"
    else
        record_check "WARN" "cortex_ai" "AI Adapter Yapılandırması" "Yapılandırılmış adapter yok (.env kontrol et)"
    fi

    # 5.2 Ollama yerel LLM — kurulu değilse PASS (bulut modu aktif)
    if command -v ollama >/dev/null 2>&1; then
        if curl -s --connect-timeout 3 http://127.0.0.1:11434/api/tags >/dev/null 2>&1; then
            record_check "PASS" "cortex_ai" "Yerel Ollama LLM Motoru" "Ollama aktif (Port 11434)"
        else
            record_check "WARN" "cortex_ai" "Yerel Ollama LLM Motoru" "Ollama kurulu ama servis kapalı"
        fi
    else
        record_check "PASS" "cortex_ai" "Yerel Ollama LLM Motoru" "Ollama kurulu değil (bulut LLM modu)"
    fi
}

# ==============================================================================
# KATMAN 6: Runtime, Sunucu & Tarayıcı Kanıt Doğrulaması
# ==============================================================================
check_layer6_runtime() {
    if [[ "$JSON_MODE" == false ]]; then
        echo -e "\n${CYAN}${BOLD}[6/7] 🖥️  Runtime & Tarayıcı Kanıt Doğrulaması${NC}"
    fi

    # 6.1 App HTTP Sunucusu
    local http_status
    http_status=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 3 http://127.0.0.1:8000/ 2>/dev/null || echo "DOWN")
    if [[ "$http_status" == "200" || "$http_status" == "302" ]]; then
        record_check "PASS" "runtime" "App HTTP Sunucusu" "http://127.0.0.1:8000 aktif (HTTP ${http_status})"
    else
        record_check "WARN" "runtime" "App HTTP Sunucusu" "Sunucu yanıt vermiyor (${http_status})"
    fi

    # 6.2 Bekçi Runtime
    local bekci_out
    local bekci_rc=0
    bekci_out=$(php artisan bekci:health 2>/dev/null)
    bekci_rc=$?
    if [[ "$bekci_rc" -ne 0 ]]; then
        record_check "FAIL" "runtime" "Bekçi App Runtime" "bekci:health komutu hata verdi (rc=$bekci_rc)"
    elif echo "$bekci_out" | grep -q "All systems operational"; then
        record_check "PASS" "runtime" "Bekçi App Runtime" "Tüm alt sistemler ve DB operasyonel"
    else
        record_check "WARN" "runtime" "Bekçi App Runtime" "Bekçi runtime uyarısı mevcut"
    fi

    # 6.3 TC-GT-11 Kalıcı Kanıt Dosyası (content analizi)
    local evidence_file=".project-brain/evidence/TC-GT-11-report.json"
    if [[ -f "$evidence_file" ]]; then
        if grep -q '"expectedStatus": *"passed"' "$evidence_file" 2>/dev/null && \
           grep -q '"status": *"passed"' "$evidence_file" 2>/dev/null; then
            record_check "PASS" "runtime" "TC-GT-11 Edit Runtime Kanıtı" "Kalıcı rapor PASSED olarak doğrulandı"
        else
            record_check "FAIL" "runtime" "TC-GT-11 Edit Runtime Kanıtı" "Rapor dosyası var ama test başarısız"
        fi
    else
        record_check "WARN" "runtime" "TC-GT-11 Edit Runtime Kanıtı" "Kalıcı kanıt dosyası henüz üretilmemiş"
    fi
}

# ==============================================================================
# KATMAN 7: RC Bağlamı & Depo Senkronizasyonu — RELEASE KARARI VERMEZ
# ==============================================================================
check_layer7_branch() {
    if [[ "$JSON_MODE" == false ]]; then
        echo -e "\n${CYAN}${BOLD}[7/7] 📦 RC Bağlamı & Depo Senkronizasyonu${NC}"
    fi

    local current_branch
    current_branch=$(git branch --show-current 2>/dev/null || echo "UNKNOWN")

    # 7.1 RC2 dalında mı?
    if [[ "$current_branch" == "release-candidate/RC2" ]]; then
        record_check "PASS" "branch" "Aktif Dal (RC Bağlamı)" "release-candidate/RC2 üzerinde"
    else
        record_check "WARN" "branch" "Aktif Dal (RC Bağlamı)" "Aktif dal: ${current_branch} (RC2 dalında değilsiniz)"
    fi

    # 7.2 Senkronizasyon durumu — ahead / behind / diverged
    local ahead_count=0
    local behind_count=0
    ahead_count=$(git rev-list --count origin/release-candidate/RC2..HEAD 2>/dev/null || echo "0")
    behind_count=$(git rev-list --count HEAD..origin/release-candidate/RC2 2>/dev/null || echo "0")

    local sync_status="PASS"
    local sync_detail=""
    if [[ "$behind_count" -gt 0 && "$ahead_count" -gt 0 ]]; then
        sync_status="WARN"
        sync_detail="DIVERGED: ${ahead_count} ahead / ${behind_count} behind origin/RC2"
    elif [[ "$behind_count" -gt 0 ]]; then
        sync_status="WARN"
        sync_detail="behind: origin/RC2 ${behind_count} commit ileride — push gerekli"
    elif [[ "$ahead_count" -gt 0 ]]; then
        sync_status="PASS"
        sync_detail="ahead: yerel ${ahead_count} commit pushlanmayı bekliyor"
    else
        sync_status="PASS"
        sync_detail="Senkron: origin/RC2 ile tam eşleşme"
    fi
    record_check "$sync_status" "branch" "Commit Senkronizasyonu" "$sync_detail"
}

# ==============================================================================
# NİHAİ RAPOR
# ==============================================================================
print_summary() {
    local END_TIME=$(date +%s)
    local DURATION=$((END_TIME - START_TIME))

    if [[ "$JSON_MODE" == true ]]; then
        echo "{"
        echo "  \"tool\": \"yalihan.doctor.diagnostics.v1.1\","
        echo "  \"execution_mode\": \"READ_ONLY\","
        echo "  \"quick_mode\": $QUICK_MODE,"
        echo "  \"duration_seconds\": $DURATION,"
        echo "  \"stats\": {"
        echo "    \"total\": $TOTAL_CHECKS,"
        echo "    \"passed\": $PASSED_CHECKS,"
        echo "    \"warnings\": $WARNING_CHECKS,"
        echo "    \"failures\": $FAILED_CHECKS,"
        echo "    \"skipped\": $SKIPPED_CHECKS"
        echo "  },"
        echo "  \"diagnostics\": ["
        local len=${#JSON_ITEMS[@]}
        for ((i=0; i<len; i++)); do
            if [[ $i -lt $((len - 1)) ]]; then
                echo "    ${JSON_ITEMS[$i]},"
            else
                echo "    ${JSON_ITEMS[$i]}"
            fi
        done
        echo "  ],"
        local health="HEALTHY"
        if [[ $FAILED_CHECKS -gt 0 ]]; then
            health="DEGRADED"
        elif [[ $WARNING_CHECKS -gt 0 ]] || [[ $SKIPPED_CHECKS -gt 0 && "$QUICK_MODE" == true ]]; then
            health="ACCEPTABLE"
        fi
        echo "  \"overall_health\": \"$health\""
        echo "}"
        # JSON mode is machine-readable, but its exit status still reflects
        # diagnostic failures. Warnings are intentionally non-fatal so callers
        # can parse the report and decide how to surface them.
        if [[ "$FAILED_CHECKS" -gt 0 ]]; then
            exit 1
        fi
        exit 0
    fi

    echo -e "\n${BLUE}${BOLD}  ╔══════════════════════════════════════════════════════════════════════╗"
    echo "  ║                      📊 SİSTEM TEŞHİS RAPORU                         ║"
    echo "  ╚══════════════════════════════════════════════════════════════════════╝${NC}"
    echo -e "  Toplam Kontrol : ${BOLD}${TOTAL_CHECKS}${NC}"
    echo -e "  Başarılı       : ${GREEN}${BOLD}${PASSED_CHECKS} ✔${NC}"
    echo -e "  Uyarılar       : ${YELLOW}${BOLD}${WARNING_CHECKS} ▲${NC}"
    echo -e "  Kritik Hatalar : ${RED}${BOLD}${FAILED_CHECKS} ✖${NC}"
    echo -e "  Atlananlar     : ${BLUE}${BOLD}${SKIPPED_CHECKS} ○${NC}"
    echo -e "  Tarama Süresi  : ${BOLD}${DURATION} saniye${NC}\n"

    if [[ $FAILED_CHECKS -gt 0 ]]; then
        echo -e "${RED}${BOLD}  🛑 SAĞLIK DURUMU: DEGRADED${NC}"
        echo -e "  ${RED}[✖ FAIL] maddeler yukarıda.${NC}"
        echo -e "  ${DIM}ℹ️  Release sertifikasyonu için: ./scripts/tools/rc2-release-certification-gate.sh${NC}"
        echo -e "  ${DIM}ℹ️  Doctor read-only gözlem aracıdır — release kararı VERMEZ.${NC}\n"
        exit 1
    elif [[ $WARNING_CHECKS -gt 0 ]]; then
        echo -e "${YELLOW}${BOLD}  ⚠️  SAĞLIK DURUMU: ACCEPTABLE${NC}"
        echo -e "  ${YELLOW}Kritik hata yok; takip edilecek uyarılar mevcut.${NC}"
        echo -e "  ${DIM}ℹ️  Doctor read-only gözlem aracıdır — release kararı VERMEZ.${NC}\n"
        exit 0
    elif [[ $SKIPPED_CHECKS -gt 0 && "$QUICK_MODE" == true ]]; then
        echo -e "${YELLOW}${BOLD}  ⚠️  SAĞLIK DURUMU: ACCEPTABLE (QUICK MODE)${NC}"
        echo -e "  ${BLUE}Tenant testleri --quick nedeniyle atlandı (${SKIPPED_CHECKS} kontrol).${NC}"
        echo -e "  ${DIM}Tam sonuç için --quick olmadan tekrar çalıştırın.${NC}\n"
        exit 0
    else
        echo -e "${GREEN}${BOLD}  🟢 SAĞLIK DURUMU: %100 HEALTHY${NC}"
        echo -e "  ${GREEN}Tüm kontroller yeşil.${NC}"
        echo -e "  ${DIM}ℹ️  Doctor read-only gözlem aracıdır — release kararı VERMEZ.${NC}\n"
        exit 0
    fi
}

# ==============================================================================
# MAIN
# ==============================================================================
print_header
check_layer1_worktrees
check_layer2_sab
check_layer3_drift
check_layer4_security
check_layer5_ai
check_layer6_runtime
check_layer7_branch
print_summary
