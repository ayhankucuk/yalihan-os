# Quality Gates

## Gate A — Repository

- Current branch and diff reviewed
- Relevant files and migrations mapped
- No unrelated user changes overwritten

## Gate B — Backend

- Focused automated tests run
- Migration order and rollback impact reviewed
- Exact error logs checked for incidents
- Endpoint response verified

## Gate C — Frontend

- CSS and JS assets return successfully
- Browser console has no blocking errors
- Loading, empty and error states checked
- Desktop and mobile layout checked

## Gate D — Production

- VPS deployed commit recorded
- Containers healthy
- Relevant migrations complete
- HTTP route verified
- Browser flow verified where applicable
- Rollback path known

## Gate E — Release claim

Only call a feature `CERTIFIED` when all applicable gates have evidence. Code existence, a passing unit test, or a healthy container alone is insufficient.
## Project brain gate

Run locally before material review:

`bash scripts/tools/project-brain-gate.sh`

This is a read-only prerequisite check. It does not certify production and does not authorize deployment.
