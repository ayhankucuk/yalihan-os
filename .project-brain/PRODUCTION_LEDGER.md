# Production Observation Ledger

Her deploy veya canlı incident sonrasında doldurulur. Sır, token, parola ve kişisel veri yazılmaz.

## Current known environment

- Host: `157.180.116.63`
- App path: `/opt/yalihan2026/current`
- Services: `yalihanai-app-v2`, `yalihanai-nginx-v2`, `yalihanai-queue-v2`
- Last observed branch: `integration/era-v-phase2a-e01`
- Last observed commit: `ea0549c`

## Entry template

### YYYY-MM-DD — change/incident

- Local commit:
- VPS deployed commit:
- Image/build result:
- Container health:
- Migration result:
- HTTP checks:
- Browser checks:
- Logs/errors:
- Rollback readiness:
- Evidence level:
- Next action:

## Known live observations

- `2026-08-26`: containers were observed healthy.
- `2026-08-26`: `/yazliklar` returned HTTP 500 in a direct curl check during diagnosis.
- `2026-08-26`: `/admin/property-hub` rendered the Laravel Server Error page in the browser.
- `2026-08-26`: `/admin/ilanlar/create` opened but the main stylesheet did not load in the browser.
- `2026-08-26`: root cause analysis found the nginx host `public` mount can hide image-built `public/build` files; deployment fix not yet applied.

These observations are historical snapshots and must be rechecked before declaring the current state.

## Candidate fix awaiting release

- Local candidate: PropertyHub scope fix, nginx asset mount fix, Leaflet loader guard.
- Local report: focused PHPUnit `4 tests, 13 assertions` passed; Vite build passed.
- Production: not deployed.
- Release status: awaiting diff approval and explicit deployment authorization.
