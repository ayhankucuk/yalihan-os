# Market Intelligence Engine

- **Purpose**: Establishes the foundational tracking of broad market trends and analytics.
- **Data Source**: `yalihan_market` database
- **Algorithm / Logic**: Statistical aggregation, volume tracking, supply/demand mapping.
- **Service**: `MarketIntelligenceService`
- **Controller**: `MarketIntelligenceController`
- **Routes**: `/admin/intelligence/dashboard`, `/admin/intelligence/compare`, etc.
- **UI Surface**: Admin Intelligence Dashboard, Advisor Intelligence View
- **Tests**: `MarketIntelligenceTest.php` (Integration/Feature)
- **Guard**: SAB CQRS Compliant.
- **SSOT Notes**: Documented in Core README.
- **Integration Points**: Powers pricing and volume baselines for Market Valuation and Deal Radar.
- **Phase 11 (Cognitive Shield)**: Protected by AST-based semantic auditing to ensure 0 silent catches in statistical aggregation logic.
