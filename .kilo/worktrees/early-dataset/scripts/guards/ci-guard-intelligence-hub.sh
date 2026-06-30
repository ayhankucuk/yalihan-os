#!/usr/bin/env bash
# ============================================================
# ci-guard-intelligence-hub.sh
# Intelligence Hub Authority Guard
# P0 BLOCKER — runs as Step 5.9 in quality-gate.sh
# 16 Nisan 2026
# ============================================================
set -euo pipefail

PASS=0
FAIL=0

ok()  { echo "  ✅ $1"; PASS=$((PASS+1)); }
err() { echo "  ❌ $1"; FAIL=$((FAIL+1)); }

echo ""
echo "=============================="
echo "🧠 Intelligence Hub Guard"
echo "=============================="

# ----------------------------------------------------------------
# RULE-IH-1: No direct OllamaService/OpenAIService injection inside
# controller methods (must route through YalihanCortex)
# ----------------------------------------------------------------
echo ""
echo "RULE-IH-1: No direct provider injection in controller methods"

PROVIDER_BYPASS=$(grep -rln \
  "function.*OllamaService\|function.*OpenAIService\|function.*GeminiService\|function.*ClaudeService\|function.*DeepSeekService" \
  app/Http/Controllers/ 2>/dev/null | \
  grep -v "PropertyHubController" | head -5) || true

if [ -z "$PROVIDER_BYPASS" ]; then
  ok "No controller method params inject AI providers directly"
else
  err "Direct AI provider injection found in controllers: $PROVIDER_BYPASS"
fi

# ----------------------------------------------------------------
# RULE-IH-2: AiCostGuardService must be injected in YalihanCortex
# ----------------------------------------------------------------
echo ""
echo "RULE-IH-2: AiCostGuardService injected in YalihanCortex"

if grep -q "AiCostGuardService \$costGuard" app/Services/AI/YalihanCortex.php 2>/dev/null; then
  ok "YalihanCortex injects AiCostGuardService"
else
  err "YalihanCortex missing AiCostGuardService injection"
fi

# ----------------------------------------------------------------
# RULE-IH-3: guardCostBudget() present in YalihanCortex
# ----------------------------------------------------------------
echo ""
echo "RULE-IH-3: guardCostBudget helper present in YalihanCortex"

if grep -q "private function guardCostBudget" app/Services/AI/YalihanCortex.php 2>/dev/null; then
  ok "guardCostBudget() helper present"
else
  err "guardCostBudget() helper missing from YalihanCortex"
fi

# ----------------------------------------------------------------
# RULE-IH-4: hard_cap_enabled defaults to true in ai-budgets.php
# ----------------------------------------------------------------
echo ""
echo "RULE-IH-4: hard_cap_enabled defaults to true"

if grep -q "env('AI_HARD_CAP_ENABLED', true)" config/ai-budgets.php 2>/dev/null; then
  ok "ai-budgets default hard_cap_enabled = true"
else
  err "ai-budgets hard_cap_enabled default is NOT true"
fi

# ----------------------------------------------------------------
# RULE-IH-5: No forbidden Context7 telemetry keys in AI surfaces
# Forbidden: 'success_rate', 'total_requests' (in return context7-skip is ok)
# ----------------------------------------------------------------
echo ""
echo "RULE-IH-5: No forbidden telemetry keys in AI controller JSON responses"

TELEMETRY_VIOLATIONS=$(grep -rn \
  "'success_rate'\|'total_requests'\|'successful_requests'\|'failed_requests'" \
  app/Http/Controllers/Admin/AITelemetryController.php \
  app/Services/AI/YalihanCortex.php \
  2>/dev/null | grep -v "// context7-ignore" | grep "return \[") || true

if [ -z "$TELEMETRY_VIOLATIONS" ]; then
  ok "No forbidden telemetry keys in AI controller JSON responses"
else
  err "Forbidden telemetry keys found in response: $TELEMETRY_VIOLATIONS"
fi

# ----------------------------------------------------------------
# RULE-IH-6: AiLog fillable must use hata_mesaji (not error_message)
# ----------------------------------------------------------------
echo ""
echo "RULE-IH-6: AiLog uses hata_mesaji (not error_message)"

if grep -q "'hata_mesaji'" app/Models/AiLog.php 2>/dev/null; then
  ok "AiLog fillable uses hata_mesaji"
else
  err "AiLog fillable is missing hata_mesaji"
fi

if grep -q "'error_message'" app/Models/AiLog.php 2>/dev/null; then
  err "AiLog fillable still contains forbidden error_message"
else
  ok "AiLog fillable clean of error_message"
fi

# ----------------------------------------------------------------
# RULE-IH-7: DanismanAIService::chat() calls checkBudget before provider
# ----------------------------------------------------------------
echo ""
echo "RULE-IH-7: DanismanAIService::chat() budget-guarded"

if grep -q "guardCostBudget\|checkBudget" app/Services/AI/DanismanAIService.php 2>/dev/null; then
  ok "DanismanAIService has cost guard"
else
  err "DanismanAIService missing cost guard"
fi

# ----------------------------------------------------------------
# RULE-IH-8: EmbeddingService constructor injects AiCostGuardService
# ----------------------------------------------------------------
echo ""
echo "RULE-IH-8: EmbeddingService cost guard injection"

if grep -q "AiCostGuardService \$costGuard" app/Services/AI/EmbeddingService.php 2>/dev/null; then
  ok "EmbeddingService injects AiCostGuardService"
else
  err "EmbeddingService missing AiCostGuardService injection"
fi

# ----------------------------------------------------------------
# RULE-IH-9: AiUsageController must not use $this->wallet (should be walletService)
# ----------------------------------------------------------------
echo ""
echo "RULE-IH-9: AiUsageController uses \$this->walletService consistently"

if grep -q "\$this->wallet->" app/Http/Controllers/Admin/AiUsageController.php 2>/dev/null; then
  err "AiUsageController still has \$this->wallet-> (should be walletService)"
else
  ok "AiUsageController clean — \$this->walletService"
fi

# ----------------------------------------------------------------
# SUMMARY
# ----------------------------------------------------------------
echo ""
echo "=============================="
echo "Intelligence Hub Guard: ${PASS} PASS / ${FAIL} FAIL"
echo "=============================="

if [ "$FAIL" -gt 0 ]; then
  echo "❌ P0 BLOCKER — Fix all failures before merge"
  exit 1
else
  echo "✅ All rules PASS"
  exit 0
fi
