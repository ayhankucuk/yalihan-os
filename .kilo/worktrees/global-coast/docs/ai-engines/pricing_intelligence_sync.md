# Pricing Intelligence Sync Service

## Purpose

Converts AI-generated valuation reports into pricing intelligence signals that can be consumed by the suggestion layer. Strictly forbids direct core listing price mutation. Signals feed the advisor review workflow.

## Data Source

- Input: Structured valuation report from `ConversationalAdvisorService`
- Output: Log signal to `ai_audit` channel (signal-only, no DB write in current implementation)
- Future: Write to `pricing_signals` projection table in `yalihan_market` DB

## Algorithm / Logic

1. Receives valuation `payload` from orchestrator
2. Validates payload is non-empty
3. Normalises signal fields: `median_price`, `confidence_score`, `generated_at`, `is_enabled`
4. Logs signal via `Log::channel('ai_audit')`
5. Returns `bool` — no return to the response surface

## Service

`App\Services\AI\PricingIntelligenceSyncService`

Key method: `recordPricingSignal(array $valuationPayload, int $advisorId = null): bool`

## Controller

Not directly exposed. Called internally by `ConversationalAdvisorController::query()` after a successful valuation.

## Routes

No direct route. Invoked as a post-processing side effect.

## UI Surface

Pricing sync is invisible to the end user. Signals are consumed by:

- Seller Strategy Engine
- Portfolio Doctor Engine
- Deal Radar Engine (future integration)

## Tests

Covered indirectly by `ConversationalAdvisorTest::test_service_detects_market_valuation_intent` which calls the full pipeline.

## Guard

- SAB `sab:scan` — 0 violations
- No direct core DB write
- Context7 naming: `is_enabled` (not `active`), `hata_mesaji` (not `error_message`)

## SSOT Notes

Signal generation = allowed. Core listing mutation = forbidden. Pricing suggestion = advisory only until approved via command flow.

## Integration Points

- `ConversationalAdvisorService` — triggers `recordPricingSignal()` on `MARKET_VALUATION` success
- Future: Seller Strategy, Portfolio Doctor pricing band update via approved command

## Phase 11: Cognitive Shield (AST Awareness)

The Pricing Sync layer is strictly monitored by the **Cognitive Guardian**:
- **Write Authority Lock**: AST rules ensure no direct `Model::update()` calls bypass the approved command flow.
- **Silent Exception Prevention**: Every pricing signal must be observable. AST-based scans catch any swallowed exceptions during the sync process.
- **Sealed Integrity**: Any change to the sync logic must pass the semantic audit before it can be sealed into the core manifest.
