# Portfolio Doctor Engine

- **Purpose**: Diagnostics and health scoring of existing internal and external listings to optimize sale speed.
- **Data Source**: CQRS projections
- **Algorithm / Logic**: Listing quality scoring, photo assessment rules, text density evaluation.
- **Service**: `PortfolioDoctorService`
- **Controller**: `PortfolioDoctorController`
- **Routes**: `GET /advisor/portfolio-doctor`
- **UI Surface**: `resources/views/advisor/portfolio-doctor.blade.php`
- **Tests**: `PortfolioDoctorEngineTest.php`
- **Guard**: SAB Governance OS.
- **SSOT Notes**: Verified against SSOT standards.
- **Integration Points**: Feeds automated suggestions into the Command Center.
