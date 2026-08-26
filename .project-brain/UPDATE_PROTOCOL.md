# Project Brain Update Protocol

## When to update

Update after any material code change, test run, deployment, live browser check, roadmap decision, incident, or newly discovered dependency.

## Required record

For each update capture:

- Date
- Area/feature
- Source or command
- Result
- Evidence level
- Current status
- Next action

## Status transition

`UNKNOWN` → `DOCUMENTED` or `REPO_VERIFIED` → `TEST_VERIFIED` → `PRODUCTION_VERIFIED` → `CERTIFIED`.

Do not skip a gate. A passing unit test is not a browser certification. A healthy container is not route certification.

## Conflict handling

When two sources disagree, retain both facts, mark the older/supporting source, identify the canonical source, and add a decision-log entry if the conflict affects implementation.

## Minimum post-task checklist

- Git diff and current commit recorded
- Relevant tests recorded with complete result
- Production commit and live result recorded when deployment occurred
- Open issues and next action updated
- No secrets, tokens, passwords, or private URLs persisted

## Impact-analysis gate

Before material changes, complete `.project-brain/IMPACT_ANALYSIS.md`. If the change affects tenant isolation, authentication, migrations, finances, reservations, external integrations, or production deployment, stop for explicit approval before the risky action.

After verification, use `.project-brain/EVIDENCE_RECORD_TEMPLATE.md` to capture the result and link it from the relevant state, feature, incident, or production record.

For schema/API/form changes, run `DATA_CONTRACT_CHECK.md`. For runtime or deploy work, run `OBSERVABILITY_PLAN.md` and `ROLLBACK_SIMULATOR.md` before release approval.
