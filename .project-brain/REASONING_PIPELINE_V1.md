# YALIHAN OS — Evidence-Grounded Reasoning Pipeline V1
**Document ID:** `GOV-REASONING-V1-2026-10-04`  
**Status:** `CANONICAL_CONTRACT` (Level 2 Protocol)  
**Parent Authority:** `AGENTS.md` (Rule 2 Truth & Evidence Standard, Rule 3 Agent Task Contract)  
**Human Decision Owner:** Ayhan  
**Approved Via:** Human Gate `APPROVE WITH REQUIRED MODIFICATIONS` (Post-Pilot #1 & Pilot #2)

---

## 1. Purpose & Scope

The Evidence-Grounded Reasoning Pipeline governs **material defect remediations and bug investigations** across YALIHAN OS. Its purpose is to prevent:
1. Premature implementations based on superficial root cause assumptions.
2. Inferred truths claiming higher evidence levels than demonstrated.
3. Arbitrary scope expansion (opportunistic refactoring).
4. Self-certification of changes by the implementation author.
5. Assuming production fixes merely because automated tests passed locally.

This contract is a **governance-level protocol**. It creates zero database tables, introduces no application runtime code, and does not alter Hermes event dispatchers.

---

## 2. Pipeline Execution Lifecycle

Every material defect remediation must execute through this linear sequence:

```text
CURRENT STATE & REVALIDATION
            ↓
CLAIM + CLAIM-SCOPED EVIDENCE
            ↓
ROOT CAUSE GATE (Empirical Isolation)
            ↓
CANONICAL FIX SELECTION (Authority SSOT)
            ↓
DECLARED BOUNDED WRITE SCOPE
            ↓
BOUNDED IMPLEMENTATION
            ↓
REGRESSION TEST EXECUTION
            ↓
INDEPENDENT READ-ONLY VERIFICATION (Implementer ≠ Verifier)
            ↓
SELECTIVE / ISOLATED COMMIT
            ↓
PRODUCTION = UNKNOWN (Until Independently Verified on Live)
```

---

## 3. Core Contract Rules (V1 Formalization)

### C01: Claim-Scoped Evidence Classification
- Evidence Levels are **NOT an ordinal hierarchy** (`UNKNOWN` does not automatically climb a single ladder to `PRODUCTION_VERIFIED`).
- Evidence is evaluated **per claim independently**:
  - A schema or code claim may be `REPO_VERIFIED`.
  - A query runtime behavior claim may be `TEST_VERIFIED`.
  - A user-facing production impact claim may be `UNKNOWN`.
- Permitted Evidence Levels: `REPO_VERIFIED`, `TEST_VERIFIED`, `PRODUCTION_VERIFIED`, `INFERRED`, `UNKNOWN`.
- Evidence Type (`SOURCE_AST_INSPECTION`, `DATABASE_ENGINE_EXECUTION`, `SCHEMA_INSPECTION`, etc.) is recorded separately from Evidence Level.

### C02: Stale Guard & Invariant Revalidation
- `HEAD advancement ≠ STALE`. A finding is not stale merely because new commits exist.
- When `reproduced_at_head != current_head`, the state transitions to `REVALIDATION_REQUIRED`.
- The finding transitions to `STALE_FINDING` ONLY if the relevant invariant has actually changed in the repository. If the invariant remains broken, the finding transitions to `CURRENT`.

### C03: Explicit Bounded Write Scope
- Before touching code, the agent MUST explicitly declare `DECLARED_WRITE_SCOPE`.
- No arbitrary file count ceilings: scope must be as small as possible to solve the verified root cause, but as large as necessary for correctness.
- Code edits must remain strictly within the declared files. Unrelated files must not be touched.

### C04: Root Cause Gate
- Before selecting a fix, the agent must empirically isolate the root cause among competing hypotheses (e.g., A: Type/Coercion mismatch, B: Model/Relation scoping, C: Presentation scope misuse, D: Other).
- The root cause must be supported by repository and executable evidence. If unverified: `BLOCKED: ROOT_CAUSE_NOT_VERIFIED`.

### C05: Canonical Scope & Abstraction Preference Order
When remediating queries or domain operations, abstractions must be selected in strict priority:
1. Existing domain scope representing the exact concept (e.g., `whereYayinda()`).
2. Existing backed enum contract (e.g., `IlanDurumu::YAYINDA->value`).
3. Raw database literal only if no canonical abstraction exists in the codebase.
*Creating duplicate scopes or parallel authorities is strictly forbidden.*

### C06: Independent Verification & Separation of Duties
- **Core Invariant:** `Implementer ≠ Verifier`.
- The Verifier role MUST be strictly `READ-ONLY` and possesses **zero authority to mutate code or repair test failures**.
- The Verifier must independently verify:
  1. Defect existence and pre-fix mechanism.
  2. Alignment between verified root cause and chosen fix.
  3. Canonical authority compliance.
  4. Adherence to `DECLARED_WRITE_SCOPE`.
  5. Domain semantics and isolation preservation.
  6. Regression test execution results.
  7. Preservation of unrelated working tree changes.
- Session or worktree isolation is recorded as supporting evidence; opening a new context alone is insufficient without role and permission separation.

### C07: Negative Isolation & Regression Requirement
- Every remediation must execute:
  1. A focused regression test proving the defect is resolved.
  2. Negative tenant / owner isolation tests proving foreign data cannot be accessed or counted.
  3. Domain regression suites verifying adjacent workflows remain unbroken.

### C08: Selective Staging & Isolated Commit Standard
- Never run `git add .` or `git commit -a`.
- Only files within `DECLARED_WRITE_SCOPE` may be staged (`git add <file1> <file2>`).
- Every commit must represent a single, isolated remediation with structured prefix (e.g., `fix(domain): ...`).

### C09: Working Tree Preservation Standard
- Active work in the working tree (untracked or modified files belonging to concurrent sessions or tasks) MUST be preserved intact.
- Destructive git operations (`git reset --hard`, `git checkout .`, `git clean -fd`, `git stash`) are strictly prohibited during remediation.

### C10: Truth & Evidence Boundary (Production Guard)
- `Tests Passed Locally ≠ Production Verified` (Reconfirmed from `AGENTS.md` Rule 2).
- Resolving a defect in local tests leaves the production claim as `UNKNOWN` unless live read-only production evidence is obtained.

### C13: Engine Evidence Boundary
- Database-engine-specific behavior (e.g., SQL type coercion, type affinity, collation) cannot be assumed across different database engines (e.g., SQLite vs MySQL).
- Claims regarding engine behavior must be backed by engine-specific executable evidence or classified as `INFERRED` / `UNKNOWN`.

---

## 4. Promotion Accounting Summary

| Category | Count | Item Identifiers | Notes |
|---|:---:|---|---|
| **Promoted V1 Rules** | **11** | `C01`, `C02`, `C03`, `C04`, `C05`, `C06`, `C07`, `C08`, `C09`, `C11`, `C13` | Formalized from empirical evidence in Pilot #1 & Pilot #2. |
| **Reconfirmed Canonical Rules** | **3** | `C10`, `C14`, `C15` | Pre-existing canonical rules: `C10` = Truth & Evidence Boundary (`AGENTS.md` Rule 2), `C14` = Tenant Isolation Test (`AGENTS.md` Rule 5), `C15` = Human Gate (`AGENTS.md` Rule 4); zero new authority created. |
| **Candidate / Deferred** | **1** | `C12` (3-Agent Topology) | Kept as `NEEDS_MORE_EVIDENCE`; will naturally gather evidence during future remediations without artificial pilots. |
| **Total Evaluated** | **15** | — | Clean, verified promotion accounting. |

---

## 5. Machine-Readable Schema (Informational Template)

```yaml
reasoning_remediation:
  task_id: string
  head_before: string
  head_after: string
  dirty_tree_preserved: boolean
  current_state: CURRENT | STALE_FINDING | REVALIDATION_REQUIRED
  claims:
    - id: string
      statement: string
      evidence_level: REPO_VERIFIED | TEST_VERIFIED | PRODUCTION_VERIFIED | INFERRED | UNKNOWN
      evidence_type: string
      reference: string
  root_cause:
    classification: A | B | C | D
    empirical_proof: string
    gate: PASS | FAIL | BLOCKED
  canonical_fix:
    authority_file: string
    chosen_abstraction: string
    preference_order_matched: 1 | 2 | 3
  declared_write_scope:
    - path/to/file1.php
    - path/to/test.php
  independent_verification:
    verifier_role: READ_ONLY
    verifier_identity: string
    verdict: PASS | FAIL | BLOCKED
  commit:
    sha: string | NOT_CREATED
  production_status: UNKNOWN | PRODUCTION_VERIFIED
```
