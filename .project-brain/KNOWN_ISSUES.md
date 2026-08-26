# Known Issues and Open Questions

- `/yazliklar` had a recorded HTTP 500. The definitive current exception is not yet indexed.
- Historical application logs show embedding requests failing against `localhost:11434`; container-localhost may not be the intended model-service address.
- Some terminal output was accidentally pasted back as shell input, causing `command not found` and command-substitution errors. Keep commands separate from prompts and output.
- A healthy Docker container does not prove the requested route or database query is healthy.
- Automated wizard tests do not prove that all fields render in the browser or that a draft can be saved.
- Live admin listing page references the main app stylesheet but the browser did not load it; inspect `/build/assets/css/app-F0wQNZdk.css` response and nginx/app public mounts.
- Live `/admin/property-hub` currently renders Laravel HTTP 500; likely backend path is `DashboardController@index` → `PropertyHubOrchestrator::getDashboardStats()`, but this is not confirmed until the matching exception is captured.
- `leaflet-draw.js` reported `L is not defined`, indicating a frontend dependency/load-order defect for map features.
- Repository has a large and historically layered route/controller/service surface; route ownership and duplicate legacy paths need a dedicated drift audit before broad refactoring.
- Nginx host mount `/opt/yalihan2026/current/public:/app/public:ro` can hide image-built `public/build` assets; this is the confirmed likely cause of the live missing CSS. Fix: `docker-compose.production.yml` replaces host overlay with named volume `yalihan-storage:/app/storage:ro` — pending commit + deploy.
- Local hardening changes are not yet committed or deployed; production remains on the separately recorded commit until an explicit release approval.
- `/admin/property-hub` HTTP 500: `PropertyHubController.php` called undefined `active()` scope; fix `aktif()` pending commit + deploy.
- The project-brain gate is local and read-only; it is a prerequisite check, not production certification.
- Fresh browser verification currently lands on `/login` for Property Hub, listing wizard, and Portfolio Doctor; cross-subdomain/admin session continuity needs a fresh authenticated browser setup before UI certification.
- Golden Thread certification is blocked until an authenticated browser session is available; no real draft creation or publication test has been performed in this certification cycle.
