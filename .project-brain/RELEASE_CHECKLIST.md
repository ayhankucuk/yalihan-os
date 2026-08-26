# Release Manager Checklist

## Before coding

- [ ] Scope and acceptance criteria defined
- [ ] Current branch and dirty worktree reviewed
- [ ] Relevant architecture and tenant boundaries mapped
- [ ] Data migration or external side effects identified

## Before commit

- [ ] Diff is limited to the requested scope
- [ ] Secrets and private identifiers absent
- [ ] Focused tests pass
- [ ] Frontend assets build successfully when applicable
- [ ] Rollback approach documented for schema/runtime changes

## Before production deploy

- [ ] User explicitly authorizes deployment
- [ ] Exact commit recorded
- [ ] Database backup/rollback posture checked
- [ ] Migration order reviewed
- [ ] Environment/config changes reviewed without exposing values

## After deploy

- [ ] Containers healthy
- [ ] Migrations complete
- [ ] Health endpoint passes
- [ ] Relevant HTTP route passes
- [ ] Browser flow passes
- [ ] Console and application logs checked
- [ ] Production ledger updated

Never call a release complete when a required checkbox has no evidence.
