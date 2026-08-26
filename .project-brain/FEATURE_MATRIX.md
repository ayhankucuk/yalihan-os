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
