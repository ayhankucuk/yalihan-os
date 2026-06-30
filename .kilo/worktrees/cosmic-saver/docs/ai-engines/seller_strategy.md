# Seller Strategy Engine

- **Purpose**: Creates actionable data-driven strategies for sellers to optimize their listing's performance and close rate.
- **Data Source**: CQRS projections
- **Algorithm / Logic**: Factor-based priority weighting using time-on-market and view counts.
- **Service**: `SellerStrategyService`
- **Controller**: `SellerStrategyController`
- **Routes**: `GET /advisor/seller-strategy`
- **UI Surface**: `resources/views/advisor/seller-strategy.blade.php`
- **Tests**: `SellerStrategyEngineTest.php`
- **Guard**: Fully passes all SAB validation guards.
- **SSOT Notes**: Core optimization layer in README.
- **Integration Points**: Powers the Advisor Command Center insights.
