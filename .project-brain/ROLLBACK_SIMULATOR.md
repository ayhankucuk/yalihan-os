# Rollback Simulator

Run this analysis before deploys that affect schema, runtime, routes, assets, auth, queues, or external integrations.

## Simulation questions

- What is the last known-good commit and image?
- Can the application run against the existing database after rollback?
- Are migrations reversible, and are destructive operations absent?
- Will asset hashes and cache headers remain compatible?
- Will queue jobs or event payloads remain readable?
- Will sessions, uploads, and persistent storage survive?
- Is the rollback command known and scoped?
- What data created during the release needs reconciliation?

## Result

- Rollback target:
- Schema compatibility: PASS | FAIL | UNKNOWN
- Runtime compatibility: PASS | FAIL | UNKNOWN
- Asset compatibility: PASS | FAIL | UNKNOWN
- Queue/event compatibility: PASS | FAIL | UNKNOWN
- Data reconciliation: NONE | REQUIRED | UNKNOWN
- Release decision: PROCEED | HOLD | STOP

Never simulate rollback by executing destructive commands on production.
