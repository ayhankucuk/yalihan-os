# Market Valuation Engine

- **Purpose**: Automated, data-driven pricing estimates based on contextual comparable analysis.
- **Data Source**: `yalihan_market.market_listings`, projects to `market_valuation_reports`
- **Algorithm / Logic**: Comparable listing retrieval, IQR Outlier filtering, Median price derivation, Confidence Scoring.
- **Service**: `MarketValuationService`
- **Controller**: `MarketValuationController`
- **Routes**: `POST /api/advisor/valuation/query`
- **UI Surface**: `resources/views/advisor/market-valuation.blade.php`
- **Tests**: `MarketValuationEngineTest.php`
- **Guard**: SAB Thin Controller, CQRS Read Model. Context7 Compliant (using `is_success`).
- **SSOT Notes**: Fully documented in README. CQRS isolated.
- **Integration Points**: Used by Conversational Advisor for natural language valuation queries.
