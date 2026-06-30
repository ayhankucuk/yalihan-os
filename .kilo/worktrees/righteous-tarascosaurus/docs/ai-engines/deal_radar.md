# Deal Radar Engine

- **Purpose**: Surfaces under-priced property listings hitting the market for quick acquisition/arbitrage.
- **Data Source**: CQRS Projections, `yalihan_market` listings.
- **Algorithm / Logic**: Comparative ratio checking, time-on-market vs median-price deviation.
- **Service**: `DealRadarService`
- **Controller**: `DealRadarController`
- **Routes**: `GET /advisor/deal-radar`
- **UI Surface**: `resources/views/advisor/deal-radar.blade.php`
- **Tests**: `DealRadarEngineTest.php`
- **Guard**: Full SAB Integration.
- **SSOT Notes**: Documented in README as primary acquisition mechanic.
- **Integration Points**: Triggers Advisor Action workflows and Telegram Alerts.
