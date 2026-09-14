#!/bin/bash

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# blade-fa-guardian.sh — Yalıhan AI OS
# SAB Context7 Zorunluluğu: FontAwesome İhlal Denetleyicisi
# Path: scripts/tools/blade-fa-guardian.sh
#
# Kullanım:
#   ./blade-fa-guardian.sh                    → tam tarama (audit)
#   ./blade-fa-guardian.sh --fix              → otomatik düzeltme
#   ./blade-fa-guardian.sh --fix --dry-run     → sadece göster, dokunma
#   ./blade-fa-guardian.sh --path resources/views/admin  → sadece belirli dizin
#   ./blade-fa-guardian.sh --ci                → CI modu (sadece exit code)
#   ./blade-fa-guardian.sh --summary           → özet rapor
#
# SAB Context7 Kuralı:
#   FontAwesome (fa-, fas, far, fab) class'ları YASAK.
#   Icon için → <x-icon name="..." class="..." /> kullan.
#   İstisna: Alpine dynamic class'ta @sab-fa-intentional yorumu gerekli.
#   Bypass: <!-- @sab-ignore-fa --> satırı ile ihlal beyanı.
#━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

set -euo pipefail

# ─── Renkler ─────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# ─── Sabitler ────────────────────────────────────────────────
TOOL_NAME="blade-fa-guardian"
VERSION="1.0.0"
VIEWS_DIR="resources/views"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
LOCK_FILE="$REPO_ROOT/.cache/.$TOOL_NAME.lock"
COUNTER_FILE="$REPO_ROOT/.cache/.$TOOL_NAME.count"

# ─── CLI Argümanları ─────────────────────────────────────────
MODE="audit"           # audit | fix
TARGET_PATH=""         # sadece belirli dizin tara
DRY_RUN=false
CI_MODE=false
VERBOSE=false
SINCE_COMMIT=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --fix)
            MODE="fix"
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --path)
            TARGET_PATH="$2"
            shift 2
            ;;
        --ci)
            CI_MODE=true
            MODE="audit"
            shift
            ;;
        --summary)
            MODE="summary"
            shift
            ;;
        --verbose|-v)
            VERBOSE=true
            shift
            ;;
        --since)
            SINCE_COMMIT="$2"
            shift 2
            ;;
        --help|-h)
            echo "Kullanım: $0 [seçenekler]"
            echo ""
            echo "Seçenekler:"
            echo "  --fix           FA ihlallerini otomatik düzelt (.blade.php)"
            echo "  --dry-run       Değişiklik yapma, sadece göster"
            echo "  --path <dizin>  Sadece belirli dizini tara"
            echo "  --ci            CI modu: sadece exit code döndürür"
            echo "  --summary       Özet rapor göster"
            echo "  --since <hash>   Son commit'ten bu yana değişen dosyaları tara"
            echo "  --verbose, -v   Ayrıntılı çıktı"
            echo "  --help, -h       Bu yardım"
            exit 0
            ;;
        *)
            echo -e "${RED}❌ Bilinmeyen argüman: $1${NC}"
            exit 1
            ;;
    esac
done

# ─── Yardımcı Fonksiyonlar ───────────────────────────────────

log_info()  { echo -e "${BLUE}ℹ️  ${NC}$1"; }
log_ok()    { echo -e "${GREEN}✅ ${NC}$1"; }
log_warn()  { echo -e "${YELLOW}⚠️  ${NC}$1"; }
log_fail()  { echo -e "${RED}❌ ${NC}$1"; }
log_bold()  { echo -e "${BOLD}${CYAN}$1${NC}"; }

# ─── İkon Eşleme Dosyası (bash 3.2 uyumlu) ───────────────────
# macOS'te bash 3.2 var — declare -A yok. Dosya tabanlı lookup kullanılır.
ICON_MAP_FILE=$(mktemp)
cleanup_icon_map() { rm -f "$ICON_MAP_FILE"; }
trap cleanup_icon_map EXIT

cat > "$ICON_MAP_FILE" << 'ICON_EOF'
fa-search=goster
fa-eye=goster
fa-eye-slash=gizle
fa-edit=duzenle
fa-pen=duzenle
fa-trash=sil
fa-trash-alt=sil
fa-save=kaydet
fa-download=indir
fa-upload=yukle
fa-copy=kopyala
fa-clone=kopyala
fa-plus=ekle
fa-plus-circle=ekle
fa-minus=eksi
fa-times=kapat
fa-times-circle=kapat
fa-check=onay
fa-check-circle=onay-daire
fa-exclamation-circle=uyari
fa-exclamation-triangle=uyari
fa-info-circle=bilgi
fa-external-link-alt=dis-baglanti
fa-external-link=dis-baglanti
fa-link=dis-baglanti
fa-unlink=dis-baglanti
fa-arrow-right=sag-ok
fa-arrow-left=sol-ok
fa-arrow-up=yukari-ok
fa-arrow-down=asagi-ok
fa-chevron-right=sag-chevron
fa-chevron-left=sol-chevron
fa-chevron-down=asagi-chevron
fa-chevron-up=yukari-chevron
fa-bars=menu
fa-filter=filtrele
fa-redo=yenile
fa-sync=yenile
fa-share=paylash
fa-send=gonder
fa-envelope=eposta
fa-phone=telefon
fa-home=ev
fa-map-marker-alt=konum
fa-user=kullanici
fa-users=kullanicilar
fa-star=yildiz
fa-star-half-alt=yildiz
fa-tag=etiket
fa-tags=etiket
fa-building=bina
fa-calendar=takvim
fa-chart-bar=grafik
fa-chart-pie=grafik
fa-chart-line=grafik
fa-bolt=flas
fa-layer-group=katman
fa-cube=kutu
fa-robot=robot
fa-microphone=cog
fa-bell=zil
fa-spinner=yukleniyor
fa-dollar-sign=para
fa-lira-sign=para
fa-map=harita
fa-clock=saat
fa-shield-alt=kalkan
fa-list=liste
fa-lightbulb=ampul
fa-lock=kilit
fa-lock-open=kilit
fa-image=resim
fa-images=resim
fa-file=yazi
fa-file-alt=yazi
fa-folder=kutu
fa-folder-open=kutu
fa-server=sunucu
fa-thumbtack=parmak
fa-network-wired=ag
fa-sun=gunes
fa-moon=ay
fa-expand=dis-baglanti
fa-compress=dis-baglanti
ICON_EOF

# Dosyadan ikon adı bulan fonksiyon (bash 3.2 uyumlu)
icon_lookup() {
    grep "^${1}=" "$ICON_MAP_FILE" 2>/dev/null | cut -d= -f2
}

# Yapılandırma dosyası (opsiyonel — proje bazlı override)
CONFIG_FILE="$REPO_ROOT/.config/blade-fa-guardian.json"
if [[ -f "$CONFIG_FILE" ]]; then
    FA_SCAN_ROOT="$(jq -r '.scan_root // empty' "$CONFIG_FILE" 2>/dev/null || echo "$VIEWS_DIR")"
    FA_IGNORE_PATHS="$(jq -r '.ignore_paths // [] | join("|")' "$CONFIG_FILE" 2>/dev/null || echo "")"
else
    FA_SCAN_ROOT="$VIEWS_DIR"
    FA_IGNORE_PATHS="vendor/node_modules"
fi

# ─── Kilitleme (concurrent koruması) ─────────────────────────
acquire_lock() {
    mkdir -p "$(dirname "$LOCK_FILE")"
    if command -v flock &>/dev/null; then
        exec 200>"$LOCK_FILE"
        flock -n 200 || { echo -e "${RED}❌ $TOOL_NAME zaten çalışıyor (kilit dosyası mevcut)${NC}"; exit 5; }
    fi
}

release_lock() {
    rm -f "$LOCK_FILE"
    exec 200>&-
}

# ─── Tarama Motoru ──────────────────────────────────────────

# Bir satırdaki FA ihlallerini bulur
# Dönüş: ihlal satırları (satır no | sınıf | bulunduğu dosya)
find_fa_violations_in_file() {
    local file="$1"
    local violations=()
    local basename
    basename=$(basename "$file")

    # grep -n boş dosyada 1 çıktı üretir, o yüzden erken kontrol
    local line_count
    line_count=$(grep -c '' "$file" 2>/dev/null || echo 0)
    [[ "$line_count" -eq 0 ]] && return

    # Her satırı işle
    while IFS= read -r line || [[ -n "$line" ]]; do
        local lineno="${line%%|*}"
        local content="${line#*|}"

        # Bypass kontrolü
        if [[ "$content" =~ @sab-ignore-fa ]]; then
            continue
        fi
        if [[ "$content" =~ @sab-fa-intentional ]]; then
            continue
        fi

        # FA class yakalama
        # Not: Regex'te \- kullanılır — bash değişken genişletme hatasını önler
        if [[ "$content" =~ class= ]]; then
            local fa_class
            fa_class=$(echo "$content" | grep -oE 'fas[[:space:]]+fa\x2d[a-z0-9\x2d]+|far[[:space:]]+fa\x2d[a-z0-9\x2d]+|fab[[:space:]]+fa\x2d[a-z0-9\x2d]+|fa\x2d[a-z][a-z0-9\x2d]*' | sort -u)
            if [[ -n "$fa_class" ]]; then
                while IFS= read -r cls || [[ -n "$cls" ]]; do
                    [[ -z "$cls" ]] && continue
                    violations+=("$lineno|$cls|$basename")
                done <<< "$fa_class"
            fi
        fi
    done < <(grep -n '' "$file" 2>/dev/null || true)

    printf '%s\n' "${violations[@]}"
}

# Düzeltme önerisi üretir
generate_fix() {
    local fa_class="$3"

    # İkon eşlemesini dosyadan bul
    local icon_name
    icon_name=$(icon_lookup "$fa_class")

    # Fallback: bilinmeyen class'lar için
    if [[ -z "$icon_name" ]]; then
        local short="${fa_class#fa-}"
        short="${short#fas }"
        short="${short#far }"
        short="${short#fab }"
        icon_name=$(icon_lookup "fa-${short}")
    fi

    if [[ -n "$icon_name" ]]; then
        echo "→ <x-icon name=\"${icon_name}\" class=\"...\">"
    else
        echo "→ <!-- @sab-fa-intentional: ${fa_class} -->"
    fi
}

# Otomatik düzeltme yapar
apply_fix() {
    local file="$1"
    local lineno="$2"
    local fa_class="$3"

    # FA class'ı satırdan kaldırılır
    # class="...fas fa-eye text-xxx..." → class="text-xxx..."
    # class="...fa-eye..." → (class attr'dan kaldır)

    local line
    line=$(sed -n "${lineno}p" "$file")

    # FA class'larını sırayla kaldır (fas, far, fab prefix + base name)
    local clean_line="$line"

    # "fa-xxx" veya "fas fa-xxx" vs. kaldır
    clean_line=$(echo "$clean_line" | sed -E 's/[[:space:]]*fa-[a-z0-9-]+//g')
    clean_line=$(echo "$clean_line" | sed -E 's/fa-[a-z0-9-]+[[:space:]]*//g')
    clean_line=$(echo "$clean_line" | sed -E 's/\s+/ /g')
    clean_line=$(echo "$clean_line" | sed -E 's/class=""//g')
    clean_line=$(echo "$clean_line" | sed -E 's/class=" /class="/g')
    clean_line=$(echo "$clean_line" | sed -E 's/ class=" "/ class="/g')

    echo "$clean_line"
}

# ─── Ana Tarama ──────────────────────────────────────────────

scan_blade_files() {
    local scan_root="${1:-$REPO_ROOT/$FA_SCAN_ROOT}"
    local temp_results
    temp_results=$(mktemp)
    local total_files=0
    local total_violations=0
    local clean_files=0
    local violated_files=0
    # file_violations ve file_totals dosya tabanlı (bash 3.2 uyumlu)
    local violations_index violations_count
    violations_index=$(mktemp)
    violations_count=$(mktemp)
    > "$violations_index"
    > "$violations_count"

    log_bold "🔍 $TOOL_NAME v$VERSION — FontAwesome İhlal Tarayıcı"
    echo ""

    if [[ -n "$TARGET_PATH" ]]; then
        scan_root="$REPO_ROOT/$TARGET_PATH"
    fi

    if [[ ! -d "$scan_root" ]]; then
        log_fail "Dizin bulunamadı: $scan_root"
        exit 1
    fi

    log_info "Tarama dizini: $scan_root"
    [[ "$MODE" == "fix" ]] && ! $DRY_RUN && log_warn "Düzeltme modu AKTİF — dosyalar değiştirilecek"
    $DRY_RUN && log_warn "--dry-run: Değişiklik yapılmayacak"
    echo ""

    # Dizin listesi
    local dirs_to_scan="$scan_root"
    [[ -d "$scan_root" ]] || dirs_to_scan=$(dirname "$scan_root")

    # Blade dosyalarını bul
    local blade_files
    blade_files=$(find "$dirs_to_scan" -type f -name "*.blade.php" 2>/dev/null || true)

    if [[ -z "$blade_files" ]]; then
        log_fail "Blade dosyası bulunamadı: $dirs_to_scan"
        exit 1
    fi

    # Ignore patterns
    local ignored_patterns="vendor|node_modules|\.git|bootstrap"
    local filtered_files=""
    while IFS= read -r f; do
        local rel="${f#$REPO_ROOT/}"
        local skip=false
        for pat in $ignored_patterns; do
            if [[ "$rel" == *"$pat"* ]]; then
                skip=true
                break
            fi
        done
        $skip || filtered_files+="$f"$'\n'
    done <<< "$blade_files"
    filtered_files="${filtered_files%$'\n'}"

    local count
    count=$(echo "$filtered_files" | grep -c . || echo 0)
    log_info "Taranan dosya: $count Blade dosyası"
    echo ""

    # Dosya başına tarama
    local violations_file
    violations_file=$(mktemp)

    local file
    while IFS= read -r file; do
        [[ -z "$file" ]] && continue
        local result
        result=$(find_fa_violations_in_file "$file" 2>/dev/null || true)
        if [[ -n "$result" ]]; then
            local line
            while IFS='|' read -r lineno cls basename; do
                [[ -z "$lineno" ]] && continue
                echo "$file|$lineno|$cls"
            done <<< "$result"
        fi
    done <<< "$filtered_files" >> "$violations_file" 2>/dev/null || true

    # Sonuçları dosyaya indexle (bash 3.2 uyumlu — assoc array yok)
    # violations_index: "rel_path|lineno|cls" formatında
    # violations_count: "rel_path|count" formatında
    if [[ -s "$violations_file" ]]; then
        sort -t'|' -k1 "$violations_file" | while IFS='|' read -r file lineno cls; do
            [[ -z "$file" ]] && continue
            ((total_violations++)) || true
            local rel="${file#$REPO_ROOT/}"
            echo "$rel|$lineno|$cls" >> "$violations_index"
        done
        # Dosya başına ihlal sayısı
        sort "$violations_index" | uniq -c | while read -r cnt rel; do
            echo "$rel|$cnt" >> "$violations_count"
        done
        violated_files=$(sort -u "$violations_index" | cut -d'|' -f1 | wc -l | tr -d ' ')
    fi

    # Raporlama
    if [[ $total_violations -eq 0 ]] || [[ -z "$violated_files" ]] || [[ "$violated_files" -eq 0 ]]; then
        log_ok "FontAwesome ihlali BULUNMADI ($count dosya temiz)"
        echo ""
        echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        echo -e "${GREEN}  🎉 Tüm Blade dosyaları temiz!${NC}"
        echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        rm -f "$violations_file" "$violations_index" "$violations_count"
        return 0
    fi

    clean_files=$((count - violated_files))

    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${RED}  ⚠️  $total_violations FA İHLALİ BULUNDU ($violated_files dosya)${NC}"
    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""

    if $VERBOSE; then
        while IFS='|' read -r rel cnt; do
            echo -e "${RED}❌ $rel${NC} (${cnt} ihlal)"
            grep "^${rel}|" "$violations_index" | sed 's/^[^|]*|/  /' | sed 's/|/ — /'
        done < "$violations_count"
    else
        echo -e "${BOLD}İhlaller (ilk 20 dosya):${NC}"
        local i=0
        while IFS='|' read -r rel cnt; do
            ((i++)) || true
            [[ $i -gt 20 ]] && { echo "  ... ve $((violated_files - 20)) dosya daha"; break; }
            echo -e "  ${RED}❌${NC} $rel (${cnt}×)"
        done < "$violations_count"
    fi

    echo ""

    # ─── Düzeltme Teklifleri ─────────────────────────────────
    if [[ "$MODE" == "fix" ]]; then
        log_bold "🔧 Düzeltme modu — değişiklikler uygulanıyor..."
        echo ""

        local fixes_applied=0
        local errors=0

        while IFS='|' read -r file lineno cls; do
            local rel="${file#$REPO_ROOT/}"

            if $DRY_RUN; then
                local suggested
                suggested=$(generate_fix "$file" "$lineno" "$cls")
                echo -e "  ${YELLOW}DRY:${NC} $rel:$lineno — $cls"
                echo -e "       ${GREEN}$suggested${NC}"
            else
                local clean_line
                clean_line=$(apply_fix "$file" "$lineno" "$cls")

                if sed -i "${lineno}s/.*/${clean_line//\//\\/}/" "$file" 2>/dev/null; then
                    ((fixes_applied++)) || true
                    $VERBOSE && echo -e "  ${GREEN}✅${NC} $rel:$lineno"
                else
                    ((errors++)) || true
                    echo -e "  ${RED}❌${NC} $rel:$lineno — sed hatası"
                fi
            fi
        done < "$violations_file"

        echo ""
        if $DRY_RUN; then
            log_info "Dry-run tamamlandı ($fixes_applied ihlal tespit edildi)"
        else
            log_ok "$fixes_applied düzeltme uygulandı"
            [[ $errors -gt 0 ]] && log_warn "$errors düzeltme başarısız"
        fi
    else
        log_bold "💡 Düzeltme için:"
        echo "  1) Otomatik düzeltme:  ./blade-fa-guardian.sh --fix"
        echo "  2) Önizleme:            ./blade-fa-guardian.sh --fix --dry-run"
        echo "  3) Belirli dosya:       ./blade-fa-guardian.sh --fix --path resources/views/admin"
    fi

    rm -f "$violations_file" "$violations_index" "$violations_count"

    # Cache güncelle
    mkdir -p "$(dirname "$COUNTER_FILE")"
    echo "{\"date\":\"$(date -Iseconds)\",\"total_violations\":$total_violations,\"violated_files\":$violated_files,\"clean_files\":$clean_files}" > "$COUNTER_FILE"

    if $CI_MODE; then
        exit 1  # CI: ihlal varsa non-zero exit
    fi

    return 1
}

# ─── Özet Raporu ─────────────────────────────────────────────

show_summary() {
    if [[ ! -f "$COUNTER_FILE" ]]; then
        log_warn "Önceki tarama bulunamadı. Lütfen önce tarama çalıştırın."
        exit 1
    fi

    local data
    data=$(cat "$COUNTER_FILE")
    local date=$(echo "$data" | jq -r '.date // empty')
    local total=$(echo "$data" | jq -r '.total_violations // 0')
    local violated=$(echo "$data" | jq -r '.violated_files // 0')
    local clean=$(echo "$data" | jq -r '.clean_files // 0')

    echo -e "${BOLD}📊 $TOOL_NAME — Özet Rapor${NC}"
    echo "────────────────────────────────"
    echo -e "Son tarama:    ${date:-bilinmiyor}"
    echo -e "Toplam ihlal:  ${total}"
    echo -e "İhlal dosya:   ${violated}"
    echo -e "Temiz dosya:   ${clean}"
    echo ""
}

# ─── Ana Giriş ────────────────────────────────────────────────

main() {
    acquire_lock
    trap release_lock EXIT

    cd "$REPO_ROOT"

    case "$MODE" in
        audit)
            scan_blade_files "${TARGET_PATH:-$VIEWS_DIR}"
            ;;
        summary)
            show_summary
            ;;
        fix)
            scan_blade_files "${TARGET_PATH:-$VIEWS_DIR}"
            ;;
    esac
}

main
