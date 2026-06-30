#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════
# 🛡️ Yalıhan Bekçi — FontAwesome Azaltma Koruyucusu
# SAB Kural: FA yasak — tek standart inline SVG (x-icon bileşeni)
# Model: new-only-fail (mevcut legacy dokunulmaz, YENİ FA yasak)
# ═══════════════════════════════════════════════════════════════════
# Kullanım: bash scripts/guards/ci-guard-fontawesome.sh
# CI: Bu script quality-gate.sh içinden çağrılır
# ═══════════════════════════════════════════════════════════════════

set -euo pipefail

BLADE_DIR="${1:-resources/views}"
BASELINE_FILE=".sab/baselines/fontawesome-baseline.txt"
VIOLATIONS=0

echo "🛡️  FontAwesome Azaltma Koruyucusu"
echo "   Kural: <i class=\"fa-\"> yasak — x-icon kullan"
echo "   Model: new-only-fail (legacy pre-existing, yeni ekleme BLOCKING)"
echo "────────────────────────────────────────────────────────────────"

# Mevcut FA kullanan dosyaları listele
CURRENT_FILES=$(grep -rl 'class="[^"]*fa-[^"]*"' "$BLADE_DIR" --include="*.blade.php" 2>/dev/null | sort || true)
CURRENT_COUNT=$(echo "$CURRENT_FILES" | grep -c . 2>/dev/null || echo 0)

echo "   Toplam FA dosyası: $CURRENT_COUNT"

# Baseline dosyası yoksa oluştur (ilk çalışma)
if [ ! -f "$BASELINE_FILE" ]; then
    mkdir -p "$(dirname "$BASELINE_FILE")"
    echo "$CURRENT_FILES" > "$BASELINE_FILE"
    echo "   ℹ️  Baseline oluşturuldu: $CURRENT_COUNT dosya kaydedildi"
    echo "✅ FontAwesome Guard PASSED (ilk çalışma — baseline kuruldu)"
    exit 0
fi

BASELINE_FILES=$(cat "$BASELINE_FILE")
BASELINE_COUNT=$(echo "$BASELINE_FILES" | grep -c . 2>/dev/null || echo 0)

echo "   Baseline FA dosyası: $BASELINE_COUNT"

# Baseline'da olmayan yeni FA dosyalarını bul
NEW_FA_FILES=""
while IFS= read -r file; do
    [ -z "$file" ] && continue
    if ! echo "$BASELINE_FILES" | grep -qF "$file"; then
        NEW_FA_FILES="$NEW_FA_FILES\n  ❌ YENİ FA: $file"
        VIOLATIONS=$((VIOLATIONS + 1))
    fi
done <<< "$CURRENT_FILES"

# Azalma kontrolü (ödüllendirici mesaj)
DELTA=$((BASELINE_COUNT - CURRENT_COUNT))
if [ "$DELTA" -gt 0 ]; then
    echo "   ✅ $DELTA dosya FA'dan temizlendi — baseline güncelleniyor"
    echo "$CURRENT_FILES" > "$BASELINE_FILE"
fi

echo ""
if [ "$VIOLATIONS" -gt 0 ]; then
    echo "🚨 FontAwesome Guard FAILED — $VIOLATIONS yeni FA kullanımı tespit edildi:"
    echo -e "$NEW_FA_FILES"
    echo ""
    echo "   Çözüm: <i class=\"fa-XXX\"> yerine <x-icon name=\"YYY\" class=\"w-5 h-5\" /> kullan"
    echo "   Referans: resources/views/components/icon.blade.php"
    exit 1
else
    echo "✅ FontAwesome Guard PASSED"
    if [ "$CURRENT_COUNT" -gt 0 ]; then
        echo "   ⚠️  $CURRENT_COUNT legacy FA dosyası temizlenmeyi bekliyor (pre-existing, kademeli)"
    fi
    exit 0
fi
