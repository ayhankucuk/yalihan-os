# Incident Learning Log

This is an append-only summary. Detailed secrets and raw sensitive logs do not belong here.

## INC-2026-08-26-ADMIN-UI

- Symptom: admin listing page lacked styling; Property Hub returned HTTP 500.
- Evidence: live browser and HTTP checks.
- Root cause found: nginx host `/public` mount can hide image-built `public/build` assets.
- Separate issue: Leaflet reported `L is not defined`.
- Status: root cause identified; code/deploy fix pending.
- Lesson: healthy containers do not prove asset delivery or route health; validate the exact browser/HTTP path.

## INC-2026-08-26-TERMINAL-PASTE

- Symptom: shell reported `command not found` and command-substitution syntax errors.
- Cause: explanatory text, prompts, and command output were pasted into the shell as commands.
- Prevention: commands must be sent as plain text, without prompts or output; run one command block at a time.

## INC-2026-08-28-GOVERNANCE-COMMAND-CENTER

- Symptom: `/admin/analytics/command-center` returns HTTP 500 on every authenticated request.
- Detection: Antigravity read-only audit (Kilo Agent, 2026-08-28).
- Evidence level: `REPO_VERIFIED` — browser screenshot + HTTP response captured.
- Scope: Admin Governance Command Center (Livewire). Advisor Command Center (`/command-center`) and `/fetch` unaffected.
- Root cause: `GovernanceCommandCenter.php:55` queries `governance_decisions.occurred_at` which does not exist. Available: `karar_tarihi` for decisions, `occurred_at` for events.
- Fix: Commit `7d402de` (2026-08-29 00:47) — `occurred_at` → `karar_tarihi` for decisions, split query to `governance_events` for violations. No migration required.
- Semantic approval: `karar_tarihi` = decision date (correct for governance_decisions). `occurred_at` = event timestamp (correct for governance_events).
- Test: `GovernanceCommandCenterTest::it_counts_recent_governance_decisions_by_decision_date` — PASS.
- Verification: Audit report at `audits/ADVISOR_COMMAND_CENTER_READONLY_AUDIT_2026-08-28.md`. Migration security audit: `audits/COMMIT_B_MIGRATION_SCHEMA_SECURITY_AUDIT_2026-08-29.md`.
- Prevention: Add schema-validated tests for Livewire components referencing new tables.
- Remaining risk: G-04 operator timing pending. Sprint 14 certification unblocked.

## Incident template

### INC-YYYY-MM-DD-NAME

- Symptom:
- Detection:
- Evidence level:
- Scope:
- Root cause:
- Fix:
- Verification:
- Prevention:
- Remaining risk:
