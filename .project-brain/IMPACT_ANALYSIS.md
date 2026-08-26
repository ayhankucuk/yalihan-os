# Change Impact Analysis

Bu kayıt, her material değişiklikten önce doldurulur. Amaç yalnızca değişen dosyayı değil, etkilenen sistemi görmek.

## Analysis template

### Change: [başlık]

- Date:
- Request/goal:
- Baseline commit:
- Proposed files:

## Dependency impact

- Routes:
- Controllers:
- Services/actions:
- Models:
- Migrations/tables:
- API contracts:
- Views/CSS/JS:
- Jobs/queue/events/Hermes:
- External integrations:

## Risk impact

- Tenant isolation: NONE | LOW | MEDIUM | HIGH
- Authentication/session: NONE | LOW | MEDIUM | HIGH
- Data/migration: NONE | LOW | MEDIUM | HIGH
- Queue/cache: NONE | LOW | MEDIUM | HIGH
- Security/secrets: NONE | LOW | MEDIUM | HIGH
- Performance: NONE | LOW | MEDIUM | HIGH
- Rollback complexity: LOW | MEDIUM | HIGH

## Verification plan

- Focused tests:
- Regression tests:
- Build/assets:
- HTTP checks:
- Browser checks:
- Production checks:

## Decision

- Proceed / revise / stop:
- Reason:
- Required approval:

## Result record

- Actual changed files:
- Test result:
- Build result:
- Git commit:
- VPS deployed commit:
- HTTP/browser result:
- Remaining risks:
- Brain files updated:
