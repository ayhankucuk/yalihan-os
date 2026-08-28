# Feature Matrix

| Feature | Repository evidence | Status | Production/browser evidence | Missing |
|---|---|---|---|---|
| AI-assisted listing foundation | `ROADMAP.md` | Foundation / evolving | Partial historical evidence | Full current E2E certification |
| Listing wizard category/schema | Wizard services and tests | Implemented | Step 1 and publication types observed | Steps 2–draft save with image |
| Tenant-aware feature assignments | migrations, seeder, resolver | Implemented in current branch | migrations observed DONE | Regression certification |
| Yazlık public page | VillaController/VillaService/view | Needs production verification | HTTP 500 observed during diagnosis | Fresh exception + fix verification |
| Session continuity | `ea0549c`, production compose | Deployed / browser verification blocked | Fresh browser navigation redirected admin/advisor pages to `/login` | Authenticated browser session plus expiry/cross-subdomain test |
| Channel Manager | ERA V roadmap | Documented CERTIFIED | Certification document exists | Reconfirm against current HEAD |
| Property Command Center | ERA V roadmap | LAUNCHED / ACTIVE | Current certification boundary unclear | Certification evidence |
| Action Center | ERA V roadmap | PLANNED | None | Implementation and certification |
| Knowledge Core AI | ERA V roadmap | PLANNED | None | Knowledge graph stability, citations, evals |
| Embedding service | Laravel logs | Known dependency issue | Historical connection refused at localhost:11434 | Decide service topology and verify |
| Admin CSS delivery | `resources/css/app.css`, nginx static rules | Broken in live browser | app stylesheet referenced but not loaded | Inspect asset HTTP status, container mount, cache |
| Property Hub dashboard | `routes/admin/property_hub.php`, `DashboardController` | HTTP 500 | Live browser reproduced Server Error | Capture exact Laravel exception |
| Leaflet map runtime | wizard JS + leaflet assets | Warning/partial | `L is not defined` from leaflet-draw | Verify load order and asset bundle |
| Production asset synchronization | Dockerfile + nginx host mount | Candidate fix local only | Main CSS URL referenced but not loaded in earlier browser evidence | Commit, deploy, and live asset HTTP/browser verification |
| Property Hub dashboard 500 | `PropertyHubController`, `aktif()` scope | Candidate fix local only | Earlier live browser reproduced 500; no fresh post-fix HTTP evidence | Commit, deploy, and live HTTP/browser verification |
| Property Type Manager — Turistik Tesisler | Live `/admin/property-type-manager/5` | Configuration surface available | Authenticated browser shows 3 subtypes, active Kiralik/Satilik, but `0 Alan` and no assigned features | Review feature/field rules, test subtype-specific requirements, obtain explicit approval before changes |
| Field dependencies — Turistik Tesisler | Live `/admin/property-type-manager/5/field-dependencies` | Publication-type tabs available | Kiralik and Satilik both show count 0; no field rows/rule controls rendered | Inspect API/query and contract before any configuration change |
| Global Feature Pool | Live `/admin/property-hub/features` | 36 feature records listed | 30 active / 0 passive counters are inconsistent with total 36; visible rows all show `0 atama` | Reconcile counters, relations, value types, and category mappings before production configuration |
| Template Manager | Live `/admin/property-hub/templates` | 91 master templates listed | Most visible templates are empty; visible edit URLs carry `kategori_id=0`; labels use inconsistent ASCII Turkish | Verify category binding/API, reconcile counts, improve empty-state prioritization and controlled AI write flow |
| Property Hub configuration chain | Live Feature Pool + category/type/dependency screens | All four management surfaces are reachable and linked | Feature/template/category dependency counts are mostly zero or inconsistent; complete mapping not proven | Read-only relation/API inventory, contract tests, Turistik Tesisler matrix, then authorized configuration |
| Dependency Rules | Live `/admin/property-hub/dependency-rules` | Rule management surface available | 0 total rules; duplicate-looking selector labels; no conditional form behavior proven | Verify API/IDs and define/test visible/required/enabled rules before production use |
| Checkout / Manuel Ödeme | `CheckoutController`, `CheckoutService`, `Payment` model | COMMITTED / DEPLOY_BLOCKED | 7 backend + 4 E2E testleri geçti; production deploy bekliyor | Deploy + checkout endpoint doğrulama |
| Template edit flow | Local `/admin/property-hub/templates/edit?kategori_id=3&yayin_tipi_id=2` | General and category-specific tabs open | Template has 0 features; active-subcategory list mixes unrelated domains | Fix category scoping, verify payloads, add preview/approval before master application |
