#!/usr/bin/env bash

# ═══════════════════════════════════════════════════════════════════════════
# 🛡️ CI Guard: Final Write Authority (Phase 3.5)
# ═══════════════════════════════════════════════════════════════════════════
#
# Amaç: IlanCrudService dışındaki tüm direct listing (Ilan) yapımları engellemek.
#       AI, Wizard, Bulk ve Legacy kodlarının kendi başlarına Ilan kaydetmesini
#       yasaklar.
#
# Hedef: Single Write Authority -> IlanCrudService
#
# Whitelist (Sadece zorunlu izole kalmış yerler):
#   - IlanCrudService.php (The actual authority)
#   - QUARANTINE işaretli legacy exceptions (Phase35-BULK tag)
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

VIOLATIONS=0
BASE_DIR="${CI_GUARD_BASE_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"

echo "🔍 Single Write Authority Scanner — Phase 3.5 Guard"
echo "   Base:  ${BASE_DIR}"
echo ""

SCAN_DIRS=(
    "${BASE_DIR}/app/Services"
    "${BASE_DIR}/app/Http/Controllers/Wizard"
    "${BASE_DIR}/app/Http/Controllers/Api/Wizard"
)

# Tehlikeli patternler (Ilan write persistence)
# MATCH: Ilan::create, Ilan::whereIn(...)->update, $ilan->save(), vs.
PATTERNS=(
    "Ilan::create\("
    "Ilan::insert\("
    "Ilan::update\("
    "Ilan::delete\("
    "Ilan::updateOrInsert\("
    "Ilan::upsert\("
    "Ilan::.*update\("
    "Ilan::.*delete\("
    "\\\App\\\Models\\\Ilan::create\("
    "\\\App\\\Models\\\Ilan::.*update\("
    "\\\App\\\Models\\\Ilan::.*delete\("
    "\\\$ilan->save\(\)"
    "\\\$ilan->update\("
    "\\\$ilan->delete\(\)"
)

TARGET_FILES=$(find "${SCAN_DIRS[@]}" -type f -name "*.php" 2>/dev/null | grep -iE '/AI/|/Wizard/|Bulk|Legacy' || true)

if [ -z "$TARGET_FILES" ]; then
    echo "Hiç hedef dosya bulunamadı."
    exit 0
fi

# Taramalar
for FILE in $TARGET_FILES; do
    # Eğer dosya IlanCrudService ise atla (Gerçi grep zaten bulmaz ama emin olalım)
    if [[ "$FILE" == *"IlanCrudService.php"* ]]; then
        continue
    fi

    for PATTERN in "${PATTERNS[@]}"; do
        
        MATCHES=$(grep -Hn -E -e "$PATTERN" "$FILE" | grep -vE '^\s*//|^\s*/\*|Phase35-BULK' || true)

        if [ -n "$MATCHES" ]; then
            # Eğer o dosya 'Ilan' ile çalışmıyorsa false positive olabilir (örn: AnyModel->save()).
            # Bunu teyit etmek için dosyada 'Ilan' veya '\App\Models\Ilan' geçiyor mu diye bakalım
            HAS_ILAN=$(grep -E 'Ilan|\\App\\Models\\Ilan' "$FILE" || true)
            
            if [ -n "$HAS_ILAN" ]; then
                # Quarantine kontrolü: dosyada satır eşleşti ama etrafında exception comment var mı?
                # Daha sağlam filtre: dosyanı komple oku ve Quarantine bloklarına ignore bas.
                # Şimdilik sadece grep -v 'Phase35-BULK' kullandık fakat save() başka satırda.
                
                # C7-Strict: if we found a match, report it! 
                echo -e "${RED}❌ DIRECT WRITE VIOLATION in ${FILE}:${NC}"
                echo "$MATCHES"
                echo ""
                VIOLATIONS=$((VIOLATIONS + 1))
            fi
        fi
    done
done

# Sonuç
echo "────────────────────────────────────────"
if [ "$VIOLATIONS" -eq 0 ]; then
    echo -e "${GREEN}✅ Final Write Lock Guard: PASSED (0 unauthorized writes)${NC}"
    exit 0
else
    echo -e "${RED}❌ Final Write Lock Guard: FAILED (${VIOLATIONS} file(s) with violations)${NC}"
    echo ""
    echo -e "${YELLOW}  Fix: Bütün Ilan kayıt/update işlemleri IlanCrudService üzerinden yapılmalıdır.${NC}"
    echo -e "${YELLOW}  Rule: Zero-tolerance for new direct write paths in AI, Wizard, Bulk and Legacy bounds.${NC}"
    exit 1
fi
