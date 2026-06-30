#!/usr/bin/env bash
# ============================================================
# sprint-isolation-check.sh — Sprint Branch Protocol Guard
# SAB v6.0 | Yalıhan Emlak Governance
# ============================================================
# Purpose: gov/* branch'lerde sprint dosyalarının bulunup
#          bulunmadığını kontrol eder. Varsa CI'ı kırar.
#
# Kullanım:
#   ./scripts/sprint-isolation-check.sh           # exit 0 veya 1
#   ./scripts/sprint-isolation-check.sh --report  # sadece rapor, kırmaz
#
# Çağrıldığı yer:
#   quality-gate.sh Step 3.4 (gov branch üzerinde çalışırken)
# ============================================================

set -euo pipefail

REPORT_ONLY="${1:-}"
CURRENT_BRANCH=$(git branch --show-current 2>/dev/null || echo "unknown")
EXIT_CODE=0

echo ""
echo "🔀  Sprint Isolation Check"
echo "    Branch: $CURRENT_BRANCH"
echo ""

# ── Yalnızca gov/* branch'lerde enforce et ──────────────────
if [[ "$CURRENT_BRANCH" != gov/* ]]; then
  echo "  ℹ️  gov/* dışı branch — kontrol atlandı."
  echo ""
  exit 0
fi

# ── Untracked sprint dosyalarını tara ───────────────────────
SPRINT_PATTERNS=(
  "app/Contracts/Resilience/"
  "app/Events/CRM/"
  "app/Http/Middleware/SabAuth"
  "app/Http/Middleware/SabCompliance"
  "app/Http/Requests/Api/KisiStoreRequest"
  "app/Listeners/CRM/"
  "app/Models/BaseModel.php"
  "app/Models/SabGovernanceLog.php"
  "app/Rules/PHPStan/"
  "app/Services/AI/NoHallucinationGuard"
  "app/Services/AI/StructuredDataNormalizer"
  "app/Services/AI/WinProbability"
  "app/Services/AI/DanismanAIConfig"
  "app/Services/AILearningEngine"
  "app/Services/CRM/"
  "app/Services/Intelligence/MarketIntelligence"
  "app/Services/Resilience/"
  "app/Services/SabAuthService"
  "app/Traits/SabGuard"
  "phpstan.neon"
  "phpstan-baseline.neon"
  "scripts/ci-guard-phpstan.sh"
  "SAB.md"
  ".sab/.commit-msg-sprint"
  "tests/Feature/CRM/"
  "tests/Feature/Resilience/"
  "tests/Feature/Api/KisiController"
  "tests/Unit/Services/AI/NoHallucination"
  "tests/Unit/Services/AI/StructuredData"
  "tests/Unit/Services/AI/WinProbability"
  "tests/Unit/Services/TkgmServiceSealed"
  "docs/CRM_KISI_BLUEPRINT"
  "app/Events/Lead"
  "app/Services/Lead"
  "app/Listeners/Lead"
  "tests/Feature/Lead"
  "tests/Unit/Models/Lead"
)

# Untracked dosyalar
UNTRACKED=$(git ls-files --others --exclude-standard 2>/dev/null || true)

VIOLATIONS=()
for pattern in "${SPRINT_PATTERNS[@]}"; do
  matches=$(echo "$UNTRACKED" | grep -F "$pattern" || true)
  if [[ -n "$matches" ]]; then
    while IFS= read -r file; do
      [[ -n "$file" ]] && VIOLATIONS+=("$file")
    done <<< "$matches"
  fi
done

# ── Sonuç ────────────────────────────────────────────────────
if [[ ${#VIOLATIONS[@]} -eq 0 ]]; then
  echo "  ✅ Sprint izolasyonu temiz — gov branch'te sprint dosyası yok."
  echo ""
  exit 0
fi

# İhlal bulundu
echo "  ❌ SPRINT İZOLASYON İHLALİ — gov branch'te sprint dosyaları tespit edildi!"
echo ""
echo "  Kirli dosyalar (${#VIOLATIONS[@]} adet):"
for f in "${VIOLATIONS[@]}"; do
  echo "    · $f"
done
echo ""
echo "  ÇÖZÜM:"
echo "    1. git checkout sprint/phase12"
echo "    2. git add <dosya>  &&  git commit"
echo "    3. git checkout $CURRENT_BRANCH"
echo "    4. Bu scripti tekrar çalıştır → ✅ olmalı"
echo ""

if [[ "$REPORT_ONLY" == "--report" ]]; then
  echo "  ℹ️  --report modu: exit 0 (CI kırmıyor, sadece rapor)"
  exit 0
fi

EXIT_CODE=1
exit $EXIT_CODE
