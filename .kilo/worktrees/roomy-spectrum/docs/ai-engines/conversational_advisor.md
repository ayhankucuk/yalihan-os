# AI Conversational Valuation Advisor

## Purpose

Orchestration layer that parses natural language real estate queries and routes them to the appropriate AI engines. Returns a unified response envelope across Advisor Panel, Public Web, and Telegram surfaces. Acts as the central conversational intelligence interface.

## Supported Intents (8)

| Intent                     | Target Engine               | Logic / Purpose                                              |
| :------------------------- | :-------------------------- | :----------------------------------------------------------- |
| **MARKET_VALUATION**       | `MarketValuationService`    | Real-time price estimation & confidence scoring.             |
| **MARKET_INTELLIGENCE**    | `MarketIntelligenceService` | Regional demand, liquidity, and trend analysis.              |
| **INVESTMENT_OPPORTUNITY** | `DealRadarService`          | Detection of high-ROI listings and "good deals".             |
| **SELLER_PRICING**         | `SellerStrategyService`     | Optimal sale price recommendations for owners.               |
| **LISTING_DIAGNOSTIC**     | `PortfolioDoctorService`    | Analyzing why a listing isn't selling (overpriced, etc).     |
| **OWNER_ACQUISITION**      | `OwnerDiscoveryService`     | Identifying high-potential owners for portfolio growth.      |
| **BUYER_MATCH**            | `BuyerMatchQueueService`    | Finding potential buyers for a specific listing.             |
| **PORTFOLIO_HEALTH**       | `PortfolioDoctorService`    | Holistic analysis of the overall property portfolio quality. |

## Algorithm / Logic

1. **Intent parsing** — uses keyword triggers (e.g., 'fiyat', 'fırsat', 'performans') to classify the query.
2. **Entity extraction** — extracts Location (ilce, mahalle), Asset Category, Area (dönüm to m2 conversion), and Room Count.
3. **Engine routing** — central `routeIntent()` method dispatches the payload to the specific engine handler.
4. **Report normalization** — encapsulates raw engine output into a unified schema (`is_success`, `intent_detected`, `advisor_response`, `data_payload`).
5. **Pricing sync signal** — on valuation success, triggers `PricingIntelligenceSyncService` via controller.

## Service

`App\Services\AI\ConversationalAdvisorService`

Key methods: `processQuery()`, `parseIntent()`, `extractEntities()`, `routeIntent()`

## Entry Surfaces & Controllers

- **Advisor Panel**: `Advisor\ConversationalAdvisorController`
- **Public Web Chat**: `Public\ConversationalAdvisorPublicController`
- **Telegram Integration**: `Api\Integrations\TelegramAdvisorAdapterController`

## Routes

| Method | URI                                     | Name                           |
| :----- | :-------------------------------------- | :----------------------------- |
| GET    | `/advisor/conversational`               | `advisor.conversational`       |
| POST   | `/advisor/conversational/query`         | `advisor.conversational.query` |
| GET    | `/ai-advisor`                           | `public.conversational`        |
| POST   | `/ai-advisor/query`                     | `public.conversational.query`  |
| POST   | `/api/v1/integrations/telegram/webhook` | `api.telegram.webhook`         |

## Response Schema (Unified)

```json
{
  "is_success": true,
  "intent_detected": "MARKET_VALUATION",
  "entities_parsed": { "location_ilce": "Bodrum", "m2_brut": 500 },
  "advisor_response": "Tahmini piyasa değeri: 1.000.000 TL...",
  "data_payload": { ... },
  "source_engines": ["market_valuation"]
}
```

## Testing & Verification

Comprehensive coverage across 3 dedicated test suites:

- `ConversationalAdvisorIntentTest` (18 tests) — Intent & Entity logic.
- `ConversationalAdvisorResponseTest` — Unified controller architecture check.
- `TelegramAdapterResponseTest` — Webhook adapter stability.

## Guard Compliance

- **SAB Production Seal**: Clean `sab:scan` (0 violations).
- **Context7**: Compliant naming; dark mode variants implemented on all Blade surfaces.
- **CQRS**: Read-only orchestrator. Direct DB writes are strictly forbidden.

## Phase 11: Cognitive Shield (AST Awareness)

The Conversational Advisor is now protected by the **Yalıhan Bekçi v2.1** cognitive layer:
- **Semantic Audit**: AST analysis ensures no silent catches exist in the complex routing logic. All exceptions must be logged or reported.
- **Context Integrity**: AST scans prevent forbidden `env()` calls within the advisor controllers, ensuring 100% config SSOT compliance.
- **Living Memory**: Any regression in intent parsing or entity extraction is "learned" by the guardian to prevent future architectural drift.
