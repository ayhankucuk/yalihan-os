# Advisor Command Center

- **Purpose**: The central orchestration dashboard that aggregates insights from all AI engines into a single pane of glass for the advisor.
- **Data Source**: Consolidated feed from all other AI engine projections.
- **Algorithm / Logic**: Aggregation, prioritization, and UI normalization.
- **Service**: `AdvisorCommandCenterService`
- **Controller**: `AdvisorCommandCenterController`
- **Routes**: `GET /advisor/command-center`
- **UI Surface**: `resources/views/advisor/command-center.blade.php`
- **Tests**: `AdvisorCommandCenterTest.php`
- **Guard**: SAB Guard Approved.
- **SSOT Notes**: Main integration node in README.
- **Integration Points**: Aggregates Opportunity, Deal Radar, Buyer Match, and Seller Strategies.
