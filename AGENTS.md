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

## Multi-Agent Worktree Protocol

### Problem
Running multiple agents in the same Git repository simultaneously causes:
- Working tree pollution: untracked/staged changes accumulate from concurrent work
- Commit conflicts: different agents may stage changes for the same files
- SQLite/test DB corruption: parallel test runs write to the same `database.sqlite` file
- Unpredictable diffs: changes are interleaved without clear authorship

### Solution: Worktree Isolation

Every writing agent MUST operate in its own Git worktree on a dedicated branch. The main repository (`integration/era-v-phase2a-e01`) remains read-only for all agents except the designated writer.

```
integration/era-v-phase2a-e01  ← main worktree (read-only for agents)
  ├── worktree-1/agent-alpha  ← writing agent A (own branch)
  ├── worktree-2/agent-beta   ← writing agent B (own branch)
  └── worktree-N/agent-n     ← reading agents (own branches)
```

### Rules

**Before starting any work:**
1. Run `git branch --show-current` — confirm you know which branch you're on
2. Run `git status --short` — check for uncommitted work already present
3. If you are in the main worktree with uncommitted changes from another session, **do not overwrite them**

**Writing agents (mutating work):**
1. Use a dedicated Git worktree for each writing session
2. Keep changes focused: stage only files relevant to the current task
3. Verify `git diff --staged` before committing
4. Never commit migration + code in one batch without explicit production authorization

**Read-only agents:**
- May operate in the main worktree or a dedicated worktree
- Must never run `git add`, `git commit`, `git push`, or destructive DB commands
- Must check `git status` before starting to avoid overwriting others' work

**Production operations:**
- Migration, seed, and deploy commands require explicit user authorization
- Never run `php artisan migrate` in production without user approval
- `BLOCKED_PENDING_PRODUCTION_AUTH` label must be applied to any staged migration

### Evidence Labels for Multi-Agent Work
| Label | Meaning |
|-------|---------|
| `UNVERIFIED` | Not yet tested against production or fresh DB |
| `REPO_VERIFIED` | Code review passed; correct for current schema |
| `TEST_VERIFIED` | Automated tests pass |
| `PRODUCTION_VERIFIED` | Live production evidence captured |
| `BLOCKED_PENDING_PRODUCTION_AUTH` | Migration/deploy blocked until user approves |

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
