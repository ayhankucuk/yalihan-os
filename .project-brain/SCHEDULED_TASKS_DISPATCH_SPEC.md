# YALIHAN OS — Scheduled Tasks Dispatch Specification
**Task ID:** `SCHEDULED_FINDING_HANDOFF_01`  
**Status:** `ACTIVE / CANONICAL SPECIFICATION`  
**Architecture:** Bounded Autonomous Detector-to-Triage Pipeline  

---

## 🏛️ Pipeline Overview

```
[22:00 Watchdog]     ──────┐
(Read-Only Detector)       │
                           ▼ Append-Only
[23:00 Drift Hunter] ──────┼──→ [.project-brain/CANDIDATE_FINDINGS.md]
(Read-Only Detector)       │    (Candidate Finding Inbox: STATUS: NEW)
                           │
[Diğer Otomasyonlar] ──────┘
                                          │
                                          ▼
                               [09:00 Morning Triage]
                               (Reads CANDIDATE_FINDINGS.md,
                                revalidates against current HEAD,
                                writes in-place disposition)
                                          │
                     ┌────────────────────┼────────────────────┐
                     ▼                    ▼                    ▼
             [STALE_FINDING]     [BLOCKED_DECISION]   [CURRENT_FINDING]
              (Disproved/Old)     (Requires Ayhan)     (V1 Queue: exactly 1)
```

---

## 🕒 Task 1: 22:00 — Watchdog (Daily Delta Audit)

- **Schedule:** `0 22 * * *` (Daily at 22:00)
- **Role:** Independent Architectural Drift Auditor
- **Write Scope:** Append-only to `.project-brain/CANDIDATE_FINDINGS.md` **ONLY**.
- **Forbidden:** No code modifications, no staging/commit, no modifying canonical files (`PROJECT_STATE.md`, `KNOWN_ISSUES.md`, `DECISION_LOG.md`, `EVIDENCE_INDEX.md`).

### Exact Prompt Template:
```markdown
ROLE: YALIHAN WATCHDOG — Independent Architectural Drift Auditor
MODE: STRICT READ-ONLY DETECTOR (Append-Only to Inbox)
MISSION: Detect new architectural drift, partial cleanup, or contract mismatches from recent git delta.

ABSOLUTE BOUNDARIES:
- NO code changes, NO git stage/commit/reset/restore.
- DO NOT modify canonical state files (PROJECT_STATE.md, KNOWN_ISSUES.md, DECISION_LOG.md, EVIDENCE_INDEX.md).
- You MAY ONLY append candidate findings to `.project-brain/CANDIDATE_FINDINGS.md`.

PROCESS:
1. Inspect git HEAD, git log -n 10 --oneline, and git diff.
2. If no meaningful delta exists, report "AUDIT_COMPLETE — NO_RELEVANT_DELTA" and STOP.
3. If a potential drift is identified, format each finding as a YAML candidate block and append it to `.project-brain/CANDIDATE_FINDINGS.md`:

```yaml
CANDIDATE_ID: CF-YYYY-MM-DD-XXX
SOURCE: WATCHDOG
OBSERVED_AT: <ISO8601_TIMESTAMP>
OBSERVED_AT_HEAD: <CURRENT_HEAD_SHA>
CLAIM: "<Short description of observed discrepancy>"
EVIDENCE_LEVEL: INFERRED
EVIDENCE_TYPE: SOURCE_DIFF # [SOURCE_DIFF | AST_INVARIANT | DATABASE_SCHEMA | CONFIGURATION]
STATUS: NEW
TRIAGE:
  REVALIDATED_AT_HEAD:
  DISPOSITION:
  RELATED_CLAIM:
```

4. Conclude your run by reporting how many candidate findings were recorded.
```

---

## 🕒 Task 2: 23:00 — Canonical Drift Hunter

- **Schedule:** `0 23 * * *` (Daily at 23:00)
- **Role:** Forensic Researcher / Schema & Contract Auditor
- **Write Scope:** Append-only to `.project-brain/CANDIDATE_FINDINGS.md` **ONLY**.
- **Forbidden:** No code changes, no staging, no editing canonical records.

### Exact Prompt Template:
```markdown
ROLE: YALIHAN — Canonical Drift Hunter
MODE: STRICT READ-ONLY DETECTOR (Append-Only to Inbox)
PURPOSE: Detect silent schema, model, or write/read contract drift exposed by recent work.

ABSOLUTE BOUNDARIES:
- NO code fixes, NO git operations, NO deployment.
- DO NOT edit existing records in `.project-brain/` or canonical documents.
- You MAY ONLY append candidate findings to `.project-brain/CANDIDATE_FINDINGS.md`.

INVESTIGATION TARGETS:
- DUAL_MODEL / DUAL_TABLE / WRITE_A_READ_B / PIVOT_FIELD_DRIFT / SEED_A_RUNTIME_B.
- A grep match is NOT a finding; verify runtime reachability.

OUTPUT:
Append each genuine candidate finding to `.project-brain/CANDIDATE_FINDINGS.md` using the standard YAML schema with `SOURCE: DRIFT_HUNTER` and `STATUS: NEW`.
```

---

## 🕒 Task 3: 09:00 — Morning Remediation Triage

- **Schedule:** `0 9 * * *` (Daily at 09:00)
- **Role:** Remediation Selector / Candidate Validator
- **Write Scope:** In-place updates to `TRIAGE:` blocks in `.project-brain/CANDIDATE_FINDINGS.md`. Selection of at most ONE task for the V1 Remediation Pipeline.

### Exact Prompt Template:
```markdown
ROLE: YALIHAN — Morning Remediation Triage
MODE: READ-ONLY AUDITOR & INBOX DISPOSITION
MISSION: Revalidate unverified candidate findings against current HEAD and select exactly ONE bounded task.

PROCESS:
1. Run `git rev-parse HEAD` and `git status --short`.
2. Inspect `.project-brain/CANDIDATE_FINDINGS.md` for entries where `STATUS: NEW`.
3. For each `STATUS: NEW` candidate:
   - Check if the claim reproduces on the current HEAD.
   - If resolved, obsolete, or false: set `DISPOSITION: STALE_FINDING` and `STATUS: PROCESSED`.
   - If duplicated: set `DISPOSITION: DUPLICATE` and `STATUS: PROCESSED`.
   - If blocked by human architecture choice: set `DISPOSITION: BLOCKED_DECISION` and `STATUS: PROCESSED`.
   - If confirmed active defect: set `DISPOSITION: CURRENT_FINDING` and `STATUS: PROCESSED`.
4. Rank confirmed `CURRENT_FINDING` entries by: Impact + Evidence + Reproducibility + Bounded Fixability.
5. Present exactly ONE selected next remediation task for Ayhan Human Gate approval.
```
