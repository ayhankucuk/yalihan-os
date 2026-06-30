#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════════
# SAB Violation Baseline Updater
# SAB Core Constitution v2.3 — Baseline Management
#
# Kullanım:
#   bash scripts/update-violation-baseline.sh
#   bash scripts/update-violation-baseline.sh --dry-run
#
# Ne zaman çalıştırılır:
#   - Sprint sonu: violation sayısı düştükten sonra baseline'ı güncelle
#   - ci-guard-new-violation.sh "İYİLEŞME" raporu verdiğinde
#   - Yeni bir debt temizleme PR'ından sonra
#
# ⚠️  UYARI: Baseline artırmak (count yükseltmek) yasak.
#           Sadece düşen sayıları günceller.
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

BASELINE_FILE=".sab/violation-baseline.json"
DRY_RUN=false
[ "${1:-}" = "--dry-run" ] && DRY_RUN=true

echo ""
echo "📏 SAB Violation Baseline Updater"
echo "   Baseline: ${BASELINE_FILE}"
[ "$DRY_RUN" = "true" ] && echo -e "${YELLOW}   Mode: DRY-RUN — değişiklik yapılmaz${NC}"
echo ""

# Mevcut baseline'ı oku
OLD_TX=$(python3 -c "import json; d=json.load(open('$BASELINE_FILE')); print(d['baselines']['controller_tx']['count'])" 2>/dev/null || echo "9999")
OLD_CACHE=$(python3 -c "import json; d=json.load(open('$BASELINE_FILE')); print(d['baselines']['controller_cache']['count'])" 2>/dev/null || echo "9999")
OLD_SWALLOW=$(python3 -c "import json; d=json.load(open('$BASELINE_FILE')); print(d['baselines']['swallow_controller']['count'])" 2>/dev/null || echo "9999")

# Mevcut sayıları ölç
echo -e "${BLUE}🔍 Mevcut violation sayımları...${NC}"

CURRENT_TX=$(grep -rn \
    -E "DB::transaction|DB::beginTransaction" \
    app/Http/Controllers/ \
    --include="*.php" 2>/dev/null \
    | { grep -v "Traits/" || true; } \
    | { grep -Ev '^\s*(//|#|/\*|\*|\*/)' || true; } \
    | { grep -Ev ':[0-9]+:\s*(//|#|\*|/\*)' || true; } \
    | wc -l | tr -d ' ')
CURRENT_TX=${CURRENT_TX:-0}

CURRENT_CACHE=$(grep -rn \
    -E "Cache::(put|forget|flush|tags)" \
    app/Http/Controllers/ \
    --include="*.php" 2>/dev/null \
    | { grep -v "Traits/\|YalihanBekciController" || true; } \
    | { grep -Ev ':[0-9]+:\s*(//|#|\*|/\*)' || true; } \
    | wc -l | tr -d ' ')
CURRENT_CACHE=${CURRENT_CACHE:-0}

CURRENT_SWALLOW=$(php -r '
$dir = "app/Http/Controllers";
$count = 0;
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($files as $file) {
    if ($file->getExtension() !== "php") continue;
    $lines = explode("\n", file_get_contents($file->getRealPath()));
    $total = count($lines);
    for ($i = 0; $i < $total; $i++) {
        $line = $lines[$i];
        if (!preg_match("/^\s*}\s*catch\s*\(|^\s*catch\s*\(/", $line)) continue;
        $trimmed = ltrim($line);
        if (strpos($trimmed, "//") === 0 || strpos($trimmed, "*") === 0) continue;
        $depth = 0; $block = []; $started = false;
        for ($j = $i; $j < min($i+30, $total); $j++) {
            $line_scan = $lines[$j];
            if ($j === $i) { $line_scan = preg_replace("/^\s*}\s*/", "", $line_scan); }
            foreach (str_split($line_scan) as $ch) {
                if ($ch === "{") { $depth++; $started = true; }
                if ($ch === "}") { $depth--; }
            }
            $block[] = $lines[$j];
            if ($started && $depth <= 0) break;
        }
        $bc = implode("\n", $block);
        if (!preg_match("/throw\s|Log::|report\s*\(/", $bc) && !preg_match("/intentional|NOSONAR/i", $bc)) {
            $count++;
        }
    }
}
echo $count;
' 2>/dev/null || echo "0")
if [ -z "$CURRENT_SWALLOW" ]; then CURRENT_SWALLOW=0; fi

echo ""
echo "                   Eski    Mevcut   Delta"
echo "   ─────────────────────────────────────"

UPDATE_NEEDED=false

_print_row() {
    local label="$1" old="$2" current="$3"
    local delta=$((current - old))
    local durum
    if [ "$current" -lt "$old" ]; then
        durum="${GREEN}↓ İYİLEŞME${NC}"
        UPDATE_NEEDED=true
        echo -e "   ${label}   ${old}    ${current}   ${delta}  ${durum}"
    elif [ "$current" -gt "$old" ]; then
        durum="${RED}↑ ARTIŞ${NC}"
        echo -e "   ${label}   ${old}    ${current}   +${delta}  ${durum}"
    else
        durum="= STABLE"
        echo "   ${label}   ${old}    ${current}    0  ${durum}"
    fi
}

_print_row "controller_tx    " "$OLD_TX" "$CURRENT_TX"
_print_row "controller_cache " "$OLD_CACHE" "$CURRENT_CACHE"
_print_row "swallow_ctrl     " "$OLD_SWALLOW" "$CURRENT_SWALLOW"
echo ""

# Artış varsa baseline güncelleme yasak
if [ "$CURRENT_TX" -gt "$OLD_TX" ] || [ "$CURRENT_CACHE" -gt "$OLD_CACHE" ] || [ "$CURRENT_SWALLOW" -gt "$OLD_SWALLOW" ]; then
    echo -e "${RED}❌ Baseline güncellenemez: bazı kategorilerde sayı arttı.${NC}"
    echo "   Önce ci-guard-new-violation.sh'ı geçiren violation'ları geri alın."
    exit 1
fi

if [ "$UPDATE_NEEDED" = "false" ]; then
    echo "   Tüm kategoriler stable — güncelleme gerekmez."
    exit 0
fi

if [ "$DRY_RUN" = "true" ]; then
    echo -e "${YELLOW}   DRY-RUN: Güncelleme yapılırsa yeni baseline:${NC}"
    echo "   controller_tx: ${CURRENT_TX}"
    echo "   controller_cache: ${CURRENT_CACHE}"
    echo "   swallow_controller: ${CURRENT_SWALLOW}"
    exit 0
fi

# Baseline JSON güncelle
TODAY=$(date +%Y-%m-%d)
BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")

python3 - <<PYEOF
import json
with open("$BASELINE_FILE") as f:
    d = json.load(f)

d["_generated"] = "$TODAY"
d["_branch"] = "$BRANCH"
d["baselines"]["controller_tx"]["count"] = $CURRENT_TX
d["baselines"]["controller_cache"]["count"] = $CURRENT_CACHE
d["baselines"]["swallow_controller"]["count"] = $CURRENT_SWALLOW

with open("$BASELINE_FILE", "w") as f:
    json.dump(d, f, indent=2, ensure_ascii=False)

print("Baseline güncellendi.")
PYEOF

echo -e "${GREEN}✅ Baseline güncellendi: ${BASELINE_FILE}${NC}"
echo "   Sonraki adım: git add .sab/violation-baseline.json && git commit"
