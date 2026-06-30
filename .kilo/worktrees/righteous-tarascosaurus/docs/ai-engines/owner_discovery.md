# Owner Discovery Engine

- **Purpose**: Analyzes external listings to cluster properties by owner, classify owner profiles (Investor, Developer, etc.), and score acquisition targets.
- **Data Source**: External market listings. Projects to `owner_cluster_projections` and `owner_acquisition_signals`.
- **Algorithm / Logic**: Profile clustering by contact info/location, acquisition scoring based on frequency and listing drops.
- **Service**: `OwnerDiscoveryService`
- **Controller**: `OwnerDiscoveryController`
- **Routes**: `GET /advisor/owner-discovery`
- **UI Surface**: `resources/views/advisor/owner-discovery.blade.php`
- **Tests**: `OwnerDiscoveryTest.php`
- **Guard**: SAB Thin Controller, Clean Context7 (No forbidden fields).
- **SSOT Notes**: SSOT documented in README.
- **Integration Points**: Drives active portfolio expansion logic for advisors.
