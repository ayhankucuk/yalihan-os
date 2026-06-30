# Opportunity Engine

- **Purpose**: Cross-references system data to detect arbitrary, high-value actions an advisor can take immediately.
- **Data Source**: Various CQRS read models.
- **Algorithm / Logic**: Opportunity detection heuristics evaluating listing staleness, pricing drops, and high ROI interactions.
- **Service**: `OpportunityEngineService`
- **Controller**: `OpportunityController`
- **Routes**: `GET /advisor/opportunities`
- **UI Surface**: `resources/views/advisor/opportunities.blade.php`
- **Tests**: `OpportunityEngineTest.php`
- **Guard**: SAB Compliant.
- **SSOT Notes**: Verified in README.
- **Integration Points**: Direct feed into Advisor Command Center.
