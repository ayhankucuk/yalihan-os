#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════════
# SAB Exception Swallow Guard
# SAB Core Constitution v2.2 — §6 Exception Swallow Yasağı
#
# Kural:
#   RULE-E1: catch bloğu Log:: veya throw içermiyorsa FAIL (P1)
#
# Kapsam: app/Http/Controllers/ (en riskli katman)
# Yöntem: PHP ile AST analizi yerine pragmatik blok tarama
#
# Çıkış kodları:
#   0 = İhlal yok
#   1 = İhlal tespit edildi
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

CONTROLLER_DIR="app/Http/Controllers"
VIOLATIONS=0

# ──────────────────────────────────────────────────────────────────────────
# authority.json'dan per-file blocking threshold oku (SSOT)
# ──────────────────────────────────────────────────────────────────────────
AUTHORITY_FILE=".sab/authority.json"
PER_FILE_THRESHOLD=99  # default: non-blocking large number
BLOCKING="true"
if [ -f "$AUTHORITY_FILE" ]; then
    PER_FILE_THRESHOLD=$(python3 -c "
import json
try:
    d = json.load(open('$AUTHORITY_FILE'))
    print(d.get('swallow_blocking_threshold', 99))
except Exception:
    print(99)
" 2>/dev/null || echo "99")

    BLOCKING=$(python3 -c "
import json
try:
    d = json.load(open('$AUTHORITY_FILE'))
    print('true' if d.get('ci_guards', {}).get('ci-guard-exception-swallow.sh', {}).get('blocking', True) else 'false')
except Exception:
    print('true')
" 2>/dev/null || echo "true")
fi

echo ""
echo "🛡️  SAB Exception Swallow Guard"
echo "   Authority: ${AUTHORITY_FILE} §swallow_blocking_threshold"
echo "   Scope: ${CONTROLLER_DIR}/"
echo "   Per-file blocking threshold: ${PER_FILE_THRESHOLD}"
echo ""

echo -e "${BLUE}🔍 [RULE-E1] Scanning catch blocks without throw/Log (per-file)...${NC}"

# PHP tabanlı blok analizi — catch satırından sonraki bloğu kontrol et, per-file sayar
SWALLOW_REPORT=$(php -r '
$dir = "'"${CONTROLLER_DIR}"'";
$violations = [];
$perFile = [];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($files as $file) {
    if ($file->getExtension() !== "php") continue;

    $content = file_get_contents($file->getRealPath());
    $lines   = explode("\n", $content);
    $total   = count($lines);

    for ($i = 0; $i < $total; $i++) {
        $line = $lines[$i];

        // catch satırı tespit et (comment değil)
        if (!preg_match("/^\s*}\s*catch\s*\(/", $line) &&
            !preg_match("/^\s*catch\s*\(/", $line)) {
            continue;
        }

        // Yorum satırı ise atla
        $trimmed = ltrim($line);
        if (strpos($trimmed, "//") === 0 || strpos($trimmed, "*") === 0) {
            continue;
        }

        // Blok açılışını bul ve içeriği topla
        $braceDepth  = 0;
        $blockLines  = [];
        $blockStarted = false;

        for ($j = $i; $j < min($i + 30, $total); $j++) {
            $l = $lines[$j];

            foreach (str_split($l) as $ch) {
                if ($ch === "{") { $braceDepth++; $blockStarted = true; }
                if ($ch === "}") { $braceDepth--; }
            }

            $blockLines[] = $l;

            // Blok kapandı
            if ($blockStarted && $braceDepth <= 0) {
                break;
            }
        }

        $blockContent = implode("\n", $blockLines);

        // throw veya Log:: / LogService:: var mı? (LP-014: LogService:: Log:: ile eşdeğer)
        $hasThrow = preg_match("/throw\s/", $blockContent);
        $hasLog   = preg_match("/Log::|LogService::/", $blockContent);
        $hasReport = preg_match("/report\s*\(|\\\\report\s*\(/", $blockContent);
        // empty catch (bilinçli) — phpstan notasyonu: // intentional
        $hasIntentional = preg_match("/intentional|NOSONAR|@ignore-exception/i", $blockContent);

        if (!$hasThrow && !$hasLog && !$hasReport && !$hasIntentional) {
            $relPath = str_replace(getcwd() . "/", "", $file->getRealPath());
            $violations[] = $relPath . ":" . ($i + 1);
            $perFile[$relPath] = ($perFile[$relPath] ?? 0) + 1;
        }
    }
}

foreach ($violations as $v) {
    echo $v . "\n";
}
echo "COUNT:" . count($violations) . "\n";
// per-file summary
arsort($perFile);
foreach ($perFile as $f => $c) {
    echo "FILE:" . $c . ":" . $f . "\n";
}
' 2>/dev/null)

VIOLATION_COUNT=$(echo "$SWALLOW_REPORT" | grep "^COUNT:" | sed 's/COUNT://' || echo "0")
VIOLATION_FILES=$(echo "$SWALLOW_REPORT" | grep -v "^COUNT:" | grep -v "^FILE:" || true)
FILE_COUNTS=$(echo "$SWALLOW_REPORT" | grep "^FILE:" || true)

# Per-file threshold check — authority.json'dan okunan eşiği aşan dosyalar blocking
THRESHOLD_BREACHES=0
if [ -n "$FILE_COUNTS" ] && [ "$PER_FILE_THRESHOLD" -lt 99 ]; then
    while IFS= read -r line; do
        FILE_COUNT=$(echo "$line" | cut -d: -f2)
        FILE_NAME=$(echo "$line" | cut -d: -f3-)
        if [ "$FILE_COUNT" -gt "$PER_FILE_THRESHOLD" ]; then
            echo -e "${RED}  ❌ THRESHOLD BREACH (${FILE_COUNT} > ${PER_FILE_THRESHOLD}): ${FILE_NAME}${NC}"
            THRESHOLD_BREACHES=$((THRESHOLD_BREACHES + 1))
        fi
    done <<< "$FILE_COUNTS"
fi

if [ -z "$VIOLATION_COUNT" ] || [ "$VIOLATION_COUNT" = "0" ]; then
    echo -e "${GREEN}  ✅ [RULE-E1] PASSED — No exception swallow in controllers.${NC}"
else
    echo -e "${YELLOW}⚠️  [RULE-E1] P1 — ${VIOLATION_COUNT} silent catch block(s) in controllers:${NC}"
    echo "$VIOLATION_FILES" | head -20
    if [ "$VIOLATION_COUNT" -gt 20 ]; then
        echo "   ... (${VIOLATION_COUNT} total — showing first 20)"
    fi
    echo ""
    echo -e "${BLUE}   Per-file summary (top violations):${NC}"
    echo "$FILE_COUNTS" | head -10 | sed 's/FILE:\([0-9]*\):\(.*\)/   \1  \2/'
    echo ""
    echo -e "${YELLOW}   Fix: Each catch block must contain Log::error/warning() + throw \$e${NC}"
    VIOLATIONS=$((VIOLATIONS + 1))
fi

echo ""

if [ "$THRESHOLD_BREACHES" -gt 0 ]; then
    echo -e "${RED}❌ SAB Exception Swallow Guard: ${THRESHOLD_BREACHES} file(s) exceed per-file threshold (${PER_FILE_THRESHOLD})${NC}"
    echo "   Per-file threshold defined in: ${AUTHORITY_FILE} §swallow_blocking_threshold"
    if [ "$BLOCKING" = "true" ]; then
        exit 1
    fi
    echo -e "${YELLOW}⚠️  Exception Swallow Guard: threshold breach detected (non-blocking — authority.json: blocking=false)${NC}"
    ESTIMATED_BLOCKING_DATE=$(python3 -c "
import json
try:
    d = json.load(open('$AUTHORITY_FILE'))
    print(d.get('blocking_transition', {}).get('ci-guard-exception-swallow.sh', {}).get('estimated_blocking_date', 'N/A'))
except Exception:
    print('N/A')
" 2>/dev/null || echo "N/A")
    echo -e "${YELLOW}⚠️     Blocking geçiş tarihi: ${ESTIMATED_BLOCKING_DATE}${NC}"
    exit 0
fi

if [ "$VIOLATIONS" -gt 0 ]; then
    echo -e "${YELLOW}⚠️  SAB Exception Swallow Guard: ${VIOLATION_COUNT} violation(s) detected (total)${NC}"
    echo "   Per-file threshold: ${PER_FILE_THRESHOLD} — all files below threshold"
    if [ "$BLOCKING" = "true" ]; then
        exit 1
    fi
    echo -e "${YELLOW}⚠️  Exception Swallow Guard: violations detected (non-blocking — authority.json: blocking=false)${NC}"
    ESTIMATED_BLOCKING_DATE=$(python3 -c "
import json
try:
    d = json.load(open('$AUTHORITY_FILE'))
    print(d.get('blocking_transition', {}).get('ci-guard-exception-swallow.sh', {}).get('estimated_blocking_date', 'N/A'))
except Exception:
    print('N/A')
" 2>/dev/null || echo "N/A")
    echo -e "${YELLOW}⚠️     Blocking geçiş tarihi: ${ESTIMATED_BLOCKING_DATE}${NC}"
    exit 0
fi

echo -e "${GREEN}✅ SAB Exception Swallow Guard: PASSED (0 violations)${NC}"
exit 0
