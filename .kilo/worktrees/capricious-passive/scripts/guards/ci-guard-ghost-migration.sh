#!/usr/bin/env bash
# ============================================================
# CI Guard: Ghost Migration Detector
# Direktif: P2-F — .disabled migration dosyası ile aynı tablo
# adını hedefleyen aktif migration varsa BLOKLAMA yapılır.
# Çıkış 0 = temiz, Çıkış 1 = Ghost DNA tespit edildi (FAIL)
# ============================================================
set -euo pipefail

MIGRATIONS_DIR="${1:-database/migrations}"
FAIL=0

# Canonical ghost list — authority.json'dan türetilmiş (P2-F)
declare -a GHOST_DISABLED=(
    "2025_12_31_220000_create_leads_table_context7.php.disabled"
    "2025_11_02_000001_create_polymorphic_features_system.php.disabled"
    "2025_12_19_221834_create_ai_feature_usages_table.php.disabled"
    "2025_11_24_205259_create_notifications_table.php.disabled"
    "2025_12_21_065535_create_admin_notifications_table.php.disabled"
    "2025_12_31_203446_create_opportunities_table.php.disabled"
    "2025_11_05_140000_create_ref_sequences_table.php.disabled"
    "2025_11_08_142309_create_ilan_price_history_table.php.disabled"
    "2025_11_19_160001_create_ilan_goruntulenme_gunluk_table.php.disabled"
)

echo "========================================="
echo " Ghost Migration CI Guard (P2-F)"
echo "========================================="

# Guard-1: Bilinen ghost listesi mevcut mu?
echo ""
echo "Guard-1: Known ghost disabled files present?"
for ghost in "${GHOST_DISABLED[@]}"; do
    filepath="${MIGRATIONS_DIR}/${ghost}"
    if [[ -f "$filepath" ]]; then
        echo "  [OK-ARCHIVED] $ghost"
    else
        echo "  [INFO] $ghost bulunamadı (zaten temizlendi veya yeniden adlandırıldı)"
    fi
done

# Guard-2: Herhangi bir .disabled dosyası aktif migration ile aynı tablo adını paylaşıyor mu?
echo ""
echo "Guard-2: Unexpected ghost conflicts scan..."
DISABLED_FILES=$(find "$MIGRATIONS_DIR" -name "*.disabled" 2>/dev/null)

if [[ -z "$DISABLED_FILES" ]]; then
    echo "  [OK] Hiç .disabled migration dosyası bulunamadı."
else
    while IFS= read -r disabled_file; do
        basename_disabled=$(basename "$disabled_file" .disabled)
        # Tablo adını extract et: create_X_table pattern (macOS uyumlu sed)
        table_hint=$(echo "$basename_disabled" | sed -n 's/.*create_\([a-z_]*\)_table.*/\1/p' || true)
        if [[ -z "$table_hint" ]]; then
            continue
        fi

        # Aktif migration'larda (non-disabled) aynı tablo var mı?
        ACTIVE_MATCH=$(find "$MIGRATIONS_DIR" -name "*.php" -not -name "*.disabled" \
            | xargs grep -l "Schema::create('${table_hint}'" 2>/dev/null | head -1 || true)

        if [[ -n "$ACTIVE_MATCH" ]]; then
            echo "  [WARN] Ghost conflict: tablo='${table_hint}'"
            echo "         Disabled: $(basename "$disabled_file")"
            echo "         Active:   $(basename "$ACTIVE_MATCH")"
            echo "         --> authority.json migration_canonicalization.conflict_tables kontrol edin."
        fi
    done <<< "$DISABLED_FILES"
    echo "  [DONE] Conflict scan tamamlandı."
fi

# Guard-3: string crm_durumu yazımı kaldı mı? (P0-A drift)
echo ""
echo "Guard-3: crm_durumu string drift check..."
STRING_CRM=$(grep -rn "crm_durumu.*['\"]new\b\|crm_durumu.*['\"]qualified\b\|crm_durumu.*['\"]reached\b" \
    app/ --include="*.php" 2>/dev/null \
    | grep -v "//\|CRM_STRING_MAP\|setCrmDurumu\| \* \|'enum_map'" \
    | grep -vc "^$" || true)

if [[ "$STRING_CRM" -gt 0 ]]; then
    echo "  [FAIL] crm_durumu string yazimi tespit edildi: ${STRING_CRM} yer"
    grep -rn "crm_durumu.*['\"]new\b\|crm_durumu.*['\"]qualified\b" app/ --include="*.php" \
        | grep -v "//\|CRM_STRING_MAP\|setCrmDurumu\| \* " || true
    FAIL=1
else
    echo "  [OK] crm_durumu string yazimi yok."
fi

# Guard-4: status_code in AIService kaldı mı? (P0-B drift)
echo ""
echo "Guard-4: AIService status_code drift check..."
STATUS_CODE=$(grep -n "'status_code'" app/Services/AIService.php 2>/dev/null \
    | grep -v "//\| \* \|aktiflik_kodu" | grep -vc "^$" || true)

if [[ "$STATUS_CODE" -gt 0 ]]; then
    echo "  [FAIL] AIService icinde 'status_code' kullanimi (canonical: aktiflik_kodu): ${STATUS_CODE} yer"
    grep -n "'status_code'" app/Services/AIService.php | grep -v "//\| \* " || true
    FAIL=1
else
    echo "  [OK] AIService status_code kullanimi yok."
fi

echo ""
echo "========================================="
if [[ "$FAIL" -eq 0 ]]; then
    echo " Ghost Migration Guard: PASSED (exit 0)"
    echo "========================================="
    exit 0
else
    echo " Ghost Migration Guard: FAILED (exit 1)"
    echo "========================================="
    exit 1
fi
