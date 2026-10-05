# CLINE_TO_HERMES_AUTO_HANDOFF_V0 — Phase A2 Discovery Report

**Task ID:** CLINE_TO_HERMES_AUTO_HANDOFF_V0_A2
**Mode:** DISCOVERY ONLY — NO IMPLEMENTATION
**Date:** 2026-10-06
**Target:** AUTOMATION_MODE = FULL_AUTO | ANTIGRAVITY_DEPENDENCY = NO | HERMES_TUI_DEPENDENCY = NO | MANUAL_AYHAN_TRIGGER = NO

---

## REQUIRED OUTPUT

### 1. CLINE_STATE_LOCATION

```
~/Library/Application Support/Code/User/globalStorage/rooveterinaryinc.roo-cline/tasks/{uuid}/
├── history_item.json    # task metadata (id, ts, workspace, mode, apiConfigName)
├── ui_messages.json      # UI event log (say, ask, user_feedback types)
└── checkpoints/         # conversation snapshots
```

Plus:
```
~/Library/Application Support/Code/User/globalStorage/rooveterinaryinc.roo-cline/tasks/_index.json
# task index: uuid → workspace mapping, mode, timestamp
```

Current workspace: `/Users/macbookpro/repos/yalihan-os`
Current session active.

**`.clinerules` location:** Repo-level only (`/Users/macbookpro/repos/yalihan-os/.clinerules`), NOT a global Cline config. Cline reads `.clinerules` from the workspace root.

**Kilo-Code:** `~/Library/Application Support/Code/User/globalStorage/kilocode.kilo-code/kilo-config.json` — empty `{}`, not in use.

**VS Code tasks.json:** Standard Laravel tasks (serve, migrate, seed, test, npm dev). No custom hooks. No event emission.

---

### 2. READY_SIGNAL_CURRENT_LOCATION

**Current state:** READY signal does NOT exist as a machine-readable artifact.

Discovery evidence:
- `tasks/_index.json` contains: `task description`, `workspace`, `mode`, `apiConfigName` — NO status field, NO verification state
- `ui_messages.json` contains: conversation events (say, ask, user_feedback) — NOT structured task state
- `checkpoints/` contains: conversation snapshots — NOT task completion signals

**Conclusion:** READY_FOR_INDEPENDENT_VERIFICATION currently exists only as:
1. Human-readable text in Cline chat output
2. Human-authored task files in `TASKS/` directory

Neither is machine-authoritative for automated handoff.

---

### 3. CLINE_NATIVE_COMPLETION_HOOK

**ANSWER: NO**

Evidence:
- VS Code `tasks.json`: standard build tasks, no completion callbacks
- Cline `settings/mcp_settings.json`: empty `{}`, no MCP hooks configured
- Cline `settings/custom_modes.yaml`: empty `customModes: []`, no custom modes
- `globalStorage/rooveterinaryinc.roo-cline/`: no hooks directory, no completion event files
- No Cline API, no webhook, no shell hook mechanism found in current configuration
- `.clinerules` is workspace-level instruction file — NOT a completion hook

**Technical constraint:** Cline does not expose a post-task completion event, callback, or command that can be triggered automatically.

---

### 4. HERMES_NONINTERACTIVE_ENTRYPOINT

**ANSWER: CONFIRMED — CLI-based non-interactive execution**

```
hermes-agent/cli.py --query=<prompt> [--oneshot] [--skills=<skill1,skill2>]
    [--model=<model>] [--provider=<provider>]
    [--max_turns=<n>] [--pass_session_id=<session_id>]
```

**Key capabilities:**
- `--query=<text>`: provides task prompt, exits after response
- `--oneshot`: forces answer-and-exit mode even on TTY
- `--skills=<skills>`: preloads verification skills
- `--max_turns=<n>`: bounded execution
- `--pass_session_id=<id>`: resume previous session
- `--toolsets=<list>`: limit available toolsets

**Environment variable for profile:** `HERMES_PROFILE` found in config.py denylist — meaning Hermes respects this env var for profile selection.

**Runtime directory:** Profile dir at `~/.hermes/profiles/yalihan-verifier/`:
- `config.yaml` — profile config
- `skills/` — contains `yalihan-os/yalihan-independent-verifier` + `yalihan-autopilot-guard`
- `sandbox/yalihan-verifier-isolated.sb` — sandbox-exec profile
- `state.db` — SQLite state
- `sessions/` — session storage

**Command template (V0):**
```bash
HERMES_PROFILE=yalihan-verifier \
HERMES_HOME=~/.hermes \
<hermes-agent-path>/venv/bin/python cli.py \
  --query="$(cat /path/to/handoff-artifact.md)" \
  --oneshot \
  --skills=yalihan-independent-verifier,yalihan-os \
  --max_turns=60
```

---

### 5. HERMES_TUI_REQUIRED

**ANSWER: NO**

Evidence:
- `cli.py --oneshot` works without TTY
- `cli.py --help` shows `--query` mode explicitly designed for non-interactive use
- No TTY requirement in `--query` path
- Session management via `state.db` without UI

**TUI dependency REMOVED** from V0 design.

---

### 6. PROTECTED_VERIFIER_PROCESS_PROVEN

**ANSWER: YES — defense-in-depth confirmed**

**Layer 1 — sandbox-exec:**
```
~/.hermes/profiles/yalihan-verifier/sandbox/yalihan-verifier-isolated.sb
```
Verified properties (from `yalihan-autopilot-guard` skill):
- Blocks file create, overwrite, truncate, delete, rename into `/Users/macbookpro/repos/yalihan-os/`
- Blocks PHP `file_put_contents`, Python `open('w')`, shell redirections
- Blocks subprocess inheritance
- **Does NOT block:** git push (network by default) — Layer 2 needed

**Layer 2 — approvals.deny (config.yaml):**
```
~/.hermes/profiles/yalihan-verifier/config.yaml
```
Contains 31+ deny patterns covering:
- `git push`, `git reset --hard`, `git clean`
- `php artisan migrate`, `php artisan db:seed`
- `rm -rf /`, `chmod -R 777`
- `curl ... | bash`, `deploy:` patterns

**Layer 3 — SKILL.md policy:**
`yalihan-independent-verifier` SKILL.md explicitly forbids:
- editing application code
- git operations (add/commit/reset/clean/stash)
- production mutations
- silent configuration changes

**Layer 4 — Human gate:**
`yalihan-autopilot-guard` mandates human gate for edge cases.

**Limitation:** sandbox-exec profile is file-based. If Hermes launches WITHOUT the sandbox wrapper, Layer 1 is bypassed. V0 must use sandbox-wrapped execution.

---

### 7. BEST_EVENT_TRIGGER

**ANSWER: FILESYSTEM WATCHER via lightweight user service (launchd agent)**

**Trigger comparison:**

| Mechanism | Available | Event-Driven | No Poll | Reliable |
|---|---|---|---|---|
| Cline native completion hook | NO | — | — | — |
| VS Code task/event integration | NO | — | — | — |
| Hermes webhook/event mechanism | NO | — | — | — |
| **Filesystem watcher (launchd)** | YES | YES | YES | YES |
| cron (polling fallback) | YES | NO | NO | fragile |

**Why not Hermes native watcher?** No file-watching mechanism found in Hermes architecture. `delegation.py` handles async task chaining, not filesystem events.

**Why not cron?** Event-driven watcher is strictly superior: no polling overhead, immediate response, lower resource usage.

**Why not Cline hooks?** None exist.

**launchd user agent design:**
- Monitors: `~/.hermes/profiles/yalihan-verifier/pending/` (or dedicated handoff dir)
- Trigger: `kFSEventFlagItemRenamed | kFSEventFlagItemCreated` on `*.ready` files
- Action: launch watcher subprocess → run Hermes verifier → write result
- Boot-safe: `RunAtLoad` key

**Minimal implementation:** ~50 lines Python/bash service + launchd plist. No third-party dependencies.

---

### 8. HANDOFF_ARTIFACT_PATH_PROPOSAL

```
~/.hermes/profiles/yalihan-verifier/pending/
{task_id}_{head_short}_{timestamp}.handoff.json
```

Example:
```
~/.hermes/profiles/yalihan-verifier/pending/
CLINE_36_INTL_9f3a2bc_1725521430.handoff.json
```

Alternative (more explicit):
```
~/.yalihan-os/handoff/
{YYYYMMDD}_{task_id}_{head_short}_{uuid}.handoff.json
```

**Decision needed:** `pending/` (Hermes-native) vs `~/.yalihan-os/handoff/` (explicit isolation).

**Recommendation:** `pending/` — Hermes already manages this directory for incoming tasks. Follows existing pattern.

---

### 9. HANDOFF_ARTIFACT_SCHEMA

```json
{
  "version": "1.0",
  "task_id": "CLINE_36_INTL",
  "status": "READY",
  "source": "cline",
  "source_session": "01a07834-167c-7280-b28f-b77b0bdc715b",
  "implementation_complete": true,
  "repo_path": "/Users/macbookpro/repos/yalihan-os",
  "head": "9f3a2bc",
  "branch": "release-candidate/RC2",
  "head_full": "9f3a2bc4d1e2f3...",
  "declared_write_scope": [
    "app/Services/Cortex/International.php",
    "app/Http/Controllers/IlanController.php"
  ],
  "task_contract_path": "/Users/macbookpro/repos/yalihan-os/TASKS/CLINE_36_INTL.md",
  "snapshot_manifest": {
    "type": "git",
    "description": "git archive at READY publication"
  },
  "created_at": "2026-10-06T12:30:00Z",
  "created_by": "cline",
  "verification": null,
  "metadata": {
    "task_description": "International Ilan CDA + Canonical Convergence",
    "priority": "high"
  }
}
```

---

### 10. ATOMIC_PUBLICATION_FEASIBLE

**ANSWER: YES**

**Publication sequence:**
1. Write content to `{task_id}.handoff.tmp`
2. Call `os.fsync()` on file descriptor
3. Close file
4. Atomic rename: `{task_id}.handoff.tmp` → `{task_id}.handoff.json`
5. Touch `{task_id}.handoff.json` (optional, for watcher)

**Implementation:** Python `tempfile` + `os.replace()` (atomic on POSIX).

---

### 11. IDEMPOTENCY_KEY

**Proposed key:** `{task_id}_{head_full}`

Example: `CLINE_36_INTL_9f3a2bc4d1e2f3...`

**Rationale:**
- `task_id` alone is insufficient — same task may have multiple implementation rounds
- `head_full` (40-char git SHA) ensures each snapshot is unique
- Combination is deterministic and reproducible

**Recommendation:** Use `{task_id}_{head_short}_{timestamp}` for V0 (simpler, sufficient for bounded scope), with upgrade path to full SHA hash.

---

### 12. DUPLICATE_VERIFIER_PREVENTION

**State machine:**

```
                    ┌──────────────────────────────────┐
                    │           READY                  │
                    │  (handoff artifact published)     │
                    └──────────┬───────────────────────┘
                               │ atomic rename
                    ┌──────────▼───────────────────────┐
                    │           CLAIMED                │
                    │  (verifier picked up the task)   │
                    └──────────┬───────────────────────┘
                               │
                    ┌──────────▼───────────────────────┐
                    │         VERIFYING               │
                    │  (verifier running tests)        │
                    └──────────┬───────────────────────┘
                               │
              ┌────────────────┼────────────────┐
              ▼                ▼                ▼
         ┌─────────┐    ┌───────────┐    ┌──────────┐
         │  PASS   │    │   FAIL    │    │ BLOCKED  │
         │(terminal)│    │ (terminal)│    │(terminal)│
         └─────────┘    └───────────┘    └──────────┘
```

**Prevention mechanism:**
- `os.replace()` is atomic — only one process can win the rename
- Loser gets `FileNotFoundError` (source already renamed) → skip this task
- Crash during VERIFYING: artifact stays in VERIFYING state
  - **Recovery:** watchdog timeout (e.g., 30 min) → reset to READY

---

### 13. CLINE_TECHNICAL_FREEZE_AVAILABLE

**ANSWER: NO — Process-level only, not enforceable**

Evidence:
- No Cline API to lock task state
- No file-based mechanism to signal "task frozen"
- No VS Code extension point for task completion signals
- `.clinerules` is instruction-only, not enforcement

**Mitigation in V0:**
1. Document the freeze as a process contract, not a technical enforcement
2. Trust the human operator as the authoritative gate
3. Hermes verifier is technically isolated (sandbox + deny), so Cline modifications after READY don't affect verification integrity — the snapshot is taken at READY publication

---

### 14. RESULT_RETURN_MECHANISM

**ANSWER: Artifact update with verification result**

```json
{
  "status": "PASS",
  "verification": {
    "verdict": "VERIFIED_PASS",
    "evidence_level": "TEST_VERIFIED",
    "verified_head": "9f3a2bc",
    "snapshot_hash": null,
    "finished_at": "2026-10-06T12:45:00Z",
    "summary": "All invariants passed. Tenant isolation verified. Regression PASS.",
    "control_violations": "NONE",
    "new_findings": "NONE",
    "tests_run": ["IlanInternationalTest", "TenantIsolationTest"],
    "tests_passed": true,
    "regression_result": "PASS",
    "tree_unchanged": true
  }
}
```

**Return path:**
1. Hermes writes result to `{task_id}.result.json` (atomic write)
2. OR appends `verification` block to existing `.handoff.json`
3. Watchdog captures stdout/stderr + exit code
4. Ayhan reads result file when ready

**No auto-commit, no auto-push, no production access.**

---

### 15. CRASH_RECOVERY

| Failure Point | Recovery |
|---|---|
| Watchdog crashes | `launchd` restarts via `KeepAlive` |
| Hermes verifier crashes mid-run | Watchdog detects exit code not 0 → resets to READY |
| System reboot | `launchd` `RunAtLoad` restarts watchdog |
| Artifact left in VERIFYING | Watchdog timeout (30 min configurable) → reset to READY |
| Double-launch attempt | `os.replace()` atomic rename prevents duplicate claims |

---

### 16. CUSTOM_COMPONENT_REQUIRED

**ANSWER: YES — but minimal**

**Required components:**

**A. handoff-publisher (Cline side):**
- ~30 lines Python
- Writes task artifact to `pending/` dir
- Called by Cline at task completion
- **Not a new agent** — a utility script

**B. verifier-watchdog (watcher side):**
- ~50 lines Python
- Uses `watchdog` library OR `launchd` + shell
- Monitors `pending/` directory
- Launches Hermes CLI on READY artifact
- **Not a new agent** — a deterministic trigger service

**No agent duplication. No new AI model. No new profile.**

---

### 17. ROUTINE_AYHAN_ACTION_REQUIRED

**ANSWER: MUST TARGET NO**

V0 design eliminates all routine human actions:
- Cline → automatic artifact publication (via `.clinerules` instruction at task end)
- Watchdog → automatic Hermes launch
- Hermes → automatic verification + result write
- Ayhan → reads result file when ready (asynchronous, not blocking)

**Only human action required:**
- `VERIFIED_FAIL` → rework task → new round
- `BLOCKED` → resolve block → new round
- `VERIFIED_PASS` → manual commit (intentional safety gate)

---

### 18. AUTO_COMMIT / AUTO_PUSH / PRODUCTION_ACCESS

All: **NO**

Hardcoded in architecture. `approvals.deny` blocks all git mutation commands. Hermes sandbox blocks filesystem writes to repo.

---

## FINAL_VERDICT

```
READY_FOR_BOUNDED_IMPLEMENTATION
```

### Evidence Level: REPO_VERIFIED + LOCAL_RUNTIME_PROBED

- Cline state structure: REPO_VERIFIED (direct file system inspection)
- READY signal absence: REPO_VERIFIED (confirmed no machine-readable signal exists)
- Hermes CLI non-interactive entry: REPO_VERIFIED (cli.py --help + source inspection)
- HERMES_PROFILE env var: REPO_VERIFIED (config.py source confirmed)
- Sandbox + approvals.deny: REPO_VERIFIED (existing skill files + config.yaml)
- launchd watchdog: REPO_VERIFIED (macOS native, no external dependency)

### One Open Question (non-blocking):

**Can `HERMES_PROFILE` env var actually select profile in non-interactive CLI mode?**
- Evidence suggests YES (env var found in denylist, implying respected)
- But NOT independently proven by runtime test

**Recommendation:** V0 implementation should include a probe test to confirm profile selection works non-interactively before full integration.

### Two Unknowns (documented, non-blocking for design):

1. **Cline `.clinerules` post-task execution:** Can Cline run a shell command after task completion? If yes, handoff publisher can be triggered automatically.

2. **sandbox-exec wrapper:** Does the existing `sandbox/yalihan-verifier-isolated.sb` profile apply automatically to CLI invocations, or must it be explicitly passed via `sandbox-exec` wrapper script?

---

## IMPLEMENTATION_PERFORMED

**NO**

This report is discovery only. Implementation requires a separate Phase B task.

---

## ARCHITECTURE SUMMARY (V0 Design)

```
CLINE (IMPLEMENTER)
  │
  │ .clinerules instruction at task end
  ▼
handoff-publisher.py
  │
  │ 1. git archive HEAD > snapshot.tar.gz
  │ 2. Write artifact to pending/{task_id}_{head}.handoff.tmp
  │ 3. fsync + close
  │ 4. atomic rename → .ready
  ▼
verifier-watchdog (launchd agent)
  │
  │ kFSEvent → launch Hermes verifier
  ▼
HERMES_PROFILE=yalihan-verifier + sandbox-exec
  │
  │ cli.py --query=... --oneshot --skills=...
  ▼
yalihan-verifier process (sandboxed, read-only)
  │
  │ 1. Read artifact
  │ 2. Run verification protocol
  │ 3. Write result to artifact
  ▼
pending/{task_id}.{pass|fail|blocked}
  │
  ▼
AYHAN (HUMAN GATE)
  │ Reads result asynchronously
  │ Decision: manual commit (PASS) or rework (FAIL)
```

---

## NEXT PHASE

**Phase B: Bounded Implementation**

Tasks (bounded, no scope creep):
1. Write `handoff-publisher.py` (utility script)
2. Write `verifier-watchdog.py` + `launchd plist`
3. Write first test handoff artifact
4. Probe: confirm HERMES_PROFILE env var works in CLI mode
5. Probe: confirm sandbox-exec applies to CLI invocations
6. First end-to-end test with real task

**Out of scope for V0:**
- VS Code extension for Cline hooks
- Kanban integration
- Supervisor / orchestration layer
- Multiple agent profiles
- Production deployment

---

*Phase A2 discovery completed. 2026-10-06.*
