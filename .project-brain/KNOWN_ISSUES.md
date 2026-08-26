# Known Issues and Open Questions

- `/yazliklar` had a recorded HTTP 500. The definitive current exception is not yet indexed.
- Historical application logs show embedding requests failing against `localhost:11434`; container-localhost may not be the intended model-service address.
- Some terminal output was accidentally pasted back as shell input, causing `command not found` and command-substitution errors. Keep commands separate from prompts and output.
- A healthy Docker container does not prove the requested route or database query is healthy.
- Automated wizard tests do not prove that all fields render in the browser or that a draft can be saved.
- Live admin listing page references the main app stylesheet but the browser did not load it; inspect `/build/assets/css/app-F0wQNZdk.css` response and nginx/app public mounts.
- Live `/admin/property-hub` **RESOLVED 2026-08-26**: `kategori_yayin_tipi_field_dependencies` table restored via migration `9723c2e` → `migration/fix-kytfd-table` branch. HTTP 200 verified in browser (Ayhan Küçük session, full dashboard loaded).
- `leaflet-draw.js` reported `L is not defined`, indicating a frontend dependency/load-order defect for map features — fix deployed in `80b6703` / `a0a52bf`.
- Repository has a large and historically layered route/controller/service surface; route ownership and duplicate legacy paths need a dedicated drift audit before broad refactoring.
- Nginx host mount `/opt/yalihan2026/current/public:/app/public:ro` can hide image-built `public/build` assets; this is the confirmed likely cause of the live missing CSS. Fix: `docker-compose.production.yml` replaces host overlay with named volume `yalihan-storage:/app/storage:ro` — pending commit + deploy.
- Local hardening changes are not yet committed or deployed; production remains on the separately recorded commit until an explicit release approval.
- `/admin/property-hub` HTTP 500: RESOLVED. Root cause was missing `kategori_yayin_tipi_field_dependencies` table. Fix: `active()`→`aktif()` (correct scope) + migration restore `9723c2e`. Both deployed to production 2026-08-26.
- The project-brain gate is local and read-only; it is a prerequisite check, not production certification.
- Property Hub, Copilot modal, and admin listing page now accessible in authenticated session. Browser session established via Ayhan Küçük (ayhankucuk@gmail.com / admin123), verified 2026-08-26.
