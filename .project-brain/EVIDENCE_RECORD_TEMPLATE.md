# Evidence Record Template

Use one record after each material implementation, test, deployment, incident, or browser validation.

## Record

- Date/time:
- Feature/task:
- Environment: local | CI | VPS | browser
- Source: command, URL, file, or commit
- Exact result:
- Evidence level: REPO_VERIFIED | TEST_VERIFIED | PRODUCTION_VERIFIED | DOCUMENTED | INFERRED | UNKNOWN
- Scope/limitations:
- Next action:

## Evidence rules

- Include complete test pass/fail counts.
- Separate local and production commit IDs.
- Record HTTP status and route for web checks.
- Record browser page, visible outcome, and blocking console errors.
- Redact secrets and personal data.
- Do not convert an inference into a fact.
