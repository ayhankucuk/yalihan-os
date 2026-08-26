# YALIHAN OS — AI Agent Operating Rules

## Mission

Work as a careful architect and engineer for YALIHAN OS, an AI-assisted real-estate and property-operations platform.

## Source priority

1. Current repository code and tests
2. `docs/ERA_V/PHASE2-ROADMAP.md` for active roadmap status
3. Other repository documentation, marked as supporting when it conflicts
4. Live VPS/browser evidence supplied with date, command or URL, and result
5. Conversation memory, only as historical context

Never present an inference or old conversation claim as current production truth.

## Evidence labels

Use `REPO_VERIFIED`, `DOCUMENTED`, `PRODUCTION_VERIFIED`, `INFERRED`, or `UNKNOWN` when reporting status.

## Change and deployment rules

- Read and map before changing.
- Preserve unrelated user work.
- Do not run destructive database operations without explicit authorization.
- Do not claim completion until relevant tests and, where applicable, a real browser/HTTP flow are verified.
- Keep local code state, Git commit state, and VPS deployed state separate.
- Production commands must be provided as plain text without a copied shell prompt or output.

## Architecture rules

- Preserve tenant isolation across schema, queries, unique indexes, seeders, authorization, and UI.
- Keep domain logic separate from adapters, AI models, n8n automation, and presentation.
- Treat Hermes as orchestration/event coordination, not as an AI model.
- Require explainable provenance for AI-generated operational recommendations.

## Project brain

After material work, update `.project-brain/PROJECT_STATE.md`, `FEATURE_MATRIX.md`, `EVIDENCE_INDEX.md`, and `KNOWN_ISSUES.md` as applicable. Record important architectural choices in `DECISION_LOG.md`.

## Verification gates

- Backend: focused tests, migration status, error logs, and endpoint response.
- Frontend: asset loading, console errors, responsive layout, and browser flow.
- Production: deployed commit, container health, HTTP result, and rollback awareness.
- Release work: follow `.project-brain/RELEASE_CHECKLIST.md` and require explicit production authorization.
- Security work: follow `.project-brain/SECURITY_PROTOCOL.md`; never persist secrets or raw sensitive logs.
- Incidents: record root cause, evidence, fix, verification, and prevention in `.project-brain/INCIDENT_LOG.md`.
- Before material changes: complete `.project-brain/IMPACT_ANALYSIS.md`; after verification: record results using `.project-brain/EVIDENCE_RECORD_TEMPLATE.md`.
- For schema/API/form changes, run `.project-brain/DATA_CONTRACT_CHECK.md`.
- For runtime/deploy changes, run `.project-brain/OBSERVABILITY_PLAN.md` and `.project-brain/ROLLBACK_SIMULATOR.md`.
