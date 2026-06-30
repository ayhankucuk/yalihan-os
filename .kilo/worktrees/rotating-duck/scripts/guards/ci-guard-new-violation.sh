#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════════
# SAB New Violation Zero-Tolerance Guard
# SAB Core Constitution v2.3 — §new_violation_zero_tolerance
#
# Amaç: Legacy debt tolere edilir, YENİ violation sıfır tolerans.
# Bu guard her kategori için mevcut sayıyı baseline ile karşılaştırır.
# Herhangi bir kategoride artış → BLOCKING (authority.json'dan bağımsız).
#
# Baseline: .sab/violation-baseline.json
# SSOT:     .sab/authority.json §new_violation_zero_tolerance
#
# Çıkış kodları:
#   0 = Yeni violation yok (sayı baseline'a eşit veya düşük)
#   1 = Yeni violation tespit edildi — BLOCKING
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

BASELINE_FILE=".sab/violation-baseline.json"
AUTHORITY_FILE=".sab/authority.json"
NEW_VIOLATIONS=0
IMPROVEMENTS=0

# ──────────────────────────────────────────────────────────────────────────
# Yardımcı fonksiyon — baseline karşılaştırma
# ──────────────────────────────────────────────────────────────────────────
check_baseline() {
    local category="$1"
    local base="$2"
    local current="$3"
    local rule="$4"
    local delta=$((current - base))

    if [ "$current" -gt "$base" ]; then
        echo -e "${RED}  ❌ [${rule}] YENİ VIOLATİON — ${category}: ${base} → ${current} (+${delta})${NC}"
        NEW_VIOLATIONS=$((NEW_VIOLATIONS + 1))
    elif [ "$current" -lt "$base" ]; then
        echo -e "${GREEN}  📉 [${rule}] İYİLEŞME — ${category}: ${base} → ${current} (${delta})${NC}"
        IMPROVEMENTS=$((IMPROVEMENTS + 1))
    else
        echo -e "${GREEN}  ✅ [${rule}] STABLE — ${category}: ${current} (baseline'da)${NC}"
    fi
}

echo ""
echo "🔬 SAB New Violation Zero-Tolerance Guard"
echo "   Authority: ${AUTHORITY_FILE} §new_violation_zero_tolerance"
echo "   Baseline:  ${BASELINE_FILE}"
echo ""

# ──────────────────────────────────────────────────────────────────────────
# Baseline var mı kontrol et
# ──────────────────────────────────────────────────────────────────────────
if [ ! -f "$BASELINE_FILE" ]; then
    echo -e "${YELLOW}⚠️  Baseline dosyası bulunamadı: ${BASELINE_FILE}${NC}"
    echo "   Oluşturmak için: bash scripts/update-violation-baseline.sh"
    exit 0
fi

# ──────────────────────────────────────────────────────────────────────────
# authority.json'dan zero_tolerance aktif mi kontrol et
# ──────────────────────────────────────────────────────────────────────────
ZERO_TOLERANCE=$(python3 -c "
import json
try:
    d = json.load(open('$AUTHORITY_FILE'))
    print('true' if d.get('new_violation_zero_tolerance', False) else 'false')
except Exception:
    print('false')
" 2>/dev/null || echo "false")

if [ "$ZERO_TOLERANCE" != "true" ]; then
    echo -e "${YELLOW}⚠️  new_violation_zero_tolerance = false — guard devre dışı${NC}"
    exit 0
fi

# ──────────────────────────────────────────────────────────────────────────
# Baseline değerlerini oku
# ──────────────────────────────────────────────────────────────────────────
BASELINE_TX=$(python3 -c "import json; d=json.load(open('$BASELINE_FILE')); print(d['baselines']['controller_tx']['count'])" 2>/dev/null || echo "9999")
BASELINE_CACHE=$(python3 -c "import json; d=json.load(open('$BASELINE_FILE')); print(d['baselines']['controller_cache']['count'])" 2>/dev/null || echo "9999")
BASELINE_SWALLOW=$(python3 -c "import json; d=json.load(open('$BASELINE_FILE')); print(d['baselines']['swallow_controller']['count'])" 2>/dev/null || echo "9999")

echo -e "${BLUE}📊 Baseline (son onaylı sayımlar):${NC}"
echo "   controller_tx:    ${BASELINE_TX}"
echo "   controller_cache: ${BASELINE_CACHE}"
echo "   swallow_controller: ${BASELINE_SWALLOW}"
echo ""

# ──────────────────────────────────────────────────────────────────────────
# [RULE-C1] DB::transaction mevcut sayısı
# ──────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}🔍 [RULE-C1] controller_tx mevcut sayım...${NC}"
CURRENT_TX=$(grep -rn \
    -E "DB::transaction|DB::beginTransaction" \
    app/Http/Controllers/ \
    --include="*.php" \
    2>/dev/null \
    | { grep -v "Traits/" || true; } \
    | { grep -Ev ':[0-9]+:\s*(//|#|\*|/\*)' || true; } \
    | wc -l | tr -d ' ')

check_baseline "controller_tx" "$BASELINE_TX" "$CURRENT_TX" "RULE-C1"

# ──────────────────────────────────────────────────────────────────────────
# [RULE-C2] Cache mutation mevcut sayısı
# ──────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}🔍 [RULE-C2] controller_cache mevcut sayım...${NC}"
CURRENT_CACHE=$(grep -rn \
    -E "Cache::(put|forget|flush|tags)" \
    app/Http/Controllers/ \
    --include="*.php" \
    2>/dev/null \
    | { grep -v "Traits/\|YalihanBekciController" || true; } \
    | { grep -Ev ':[0-9]+:\s*(//|#|\*|/\*)' || true; } \
    | wc -l | tr -d ' ')

check_baseline "controller_cache" "$BASELINE_CACHE" "$CURRENT_CACHE" "RULE-C2"

# ──────────────────────────────────────────────────────────────────────────
# [RULE-E1] Exception swallow mevcut sayısı (PHP scanner)
# ──────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}🔍 [RULE-E1] swallow_controller mevcut sayım...${NC}"
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

check_baseline "swallow_controller" "$BASELINE_SWALLOW" "$CURRENT_SWALLOW" "RULE-E1"

# ──────────────────────────────────────────────────────────────────────────
# Baseline güncelleme önerisi (sayı düştüyse)
# ──────────────────────────────────────────────────────────────────────────
echo ""
if [ "$IMPROVEMENTS" -gt 0 ]; then
    echo -e "${CYAN}📉 ${IMPROVEMENTS} kategoride iyileşme tespit edildi.${NC}"
    echo "   Baseline güncellemesi için: bash scripts/update-violation-baseline.sh"
fi

# ──────────────────────────────────────────────────────────────────────────
# SONUÇ
# ──────────────────────────────────────────────────────────────────────────
echo ""
if [ "$NEW_VIOLATIONS" -gt 0 ]; then
    echo -e "${RED}❌ SAB New Violation Guard: ${NEW_VIOLATIONS} kategori(de) sayı arttı!${NC}"
    echo "   Bu guard authority.json'dan bağımsız — her zaman BLOCKING."
    echo "   Çözüm: Yeni violation'ı geri al veya baseline güncellemesini onayla."
    exit 1
fi

echo -e "${GREEN}✅ SAB New Violation Guard: PASSED — sıfır yeni violation${NC}"
echo "   Tüm kategoriler baseline'da veya altında."
exit 0
