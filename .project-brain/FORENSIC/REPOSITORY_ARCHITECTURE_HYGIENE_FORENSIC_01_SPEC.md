# REPOSITORY_ARCHITECTURE_HYGIENE_FORENSIC_01
## Task Specification — APPROVED BY: Ayhan

---

## MISSION

Determine whether the current YALIHAN OS repository structure reflects the canonical architecture, domain boundaries, single-authority principle, dependency discipline and repository hygiene.

**STRICT READ-ONLY MODE** — No mutations of any kind.

---

## CURRENT STATE (Baseline)

```
HEAD:          836b1d489b46c86dc88aeded36ed45c0cbd9114d
BRANCH:        release-candidate/RC2
GIT STATUS:    DIRTY (uncommitted _14BV2 changes)
_ACTIVE_TASK:  _14BV2 (do not interfere)
```

**Preserve all dirty/untracked work.** Forensic runs in parallel with _14BV2.

---

## READ SCOPE

1. `.project-brain/PROJECT_STATE.md`
2. `.project-brain/DECISION_LOG.md`
3. `.project-brain/EVIDENCE_INDEX.md`
4. `.project-brain/KNOWN_ISSUES.md`
5. `AGENTS.md`
6. Relevant ADRs in `docs/architecture/`
7. `docs/SAB.md`
8. `.sab/authority.json`

---

## FORENSIC AREAS (10)

### AREA A — DOMAIN TOPOLOGY

Map major domains:
- Ilan
- Emlak/Property
- Reservation
- CRM/Kisi
- Finance
- Tenant/SaaS
- User/Auth
- ActionCenter
- Hermes
- AI
- N8n/Integrations

For each domain identify:
- canonical models
- write authority
- read/query authority
- tenant authority
- services/use cases
- controllers
- commands/jobs
- API/resources
- tests

Measure **DOMAIN_SCATTERING**: How many architectural roots contain the same domain?

Examples: `app/Models`, `app/Services`, `app/Domain`, `app/Modules`, `app/Actions`, `app/Http`, `app/Repositories`

**Do NOT assume scattering is automatically wrong.**

---

### AREA B — AUTHORITY DUPLICATION

Search for potentially competing:
- BaseModels
- repositories
- CRUD services
- domain services
- Actions/UseCases
- command implementations
- resources
- tenant mechanisms
- schema abstractions

Classify each:
- CANONICAL
- LEGACY_REFERENCED
- DUPLICATE_AUTHORITY_CANDIDATE
- INTENTIONAL_BOUNDED_CONTEXT_SPLIT
- UNKNOWN

**Do not infer duplicate authority from similar names alone.**

---

### AREA C — WRITE PATH DISCIPLINE

For high-value entities:
- Ilan
- Reservation
- Kisi/CRM
- Finance/payables
- Tenant/User

Find mutation paths:
- `::create`
- `::update`
- `::delete`
- `save()`
- `DB::table` mutations
- repository writes
- service writes
- jobs/commands writes

Determine whether multiple independent write authorities exist.
**Prioritize real bypasses of established canonical write paths.**

---

### AREA D — DEPENDENCY DIRECTION

Look for suspicious cross-layer dependencies:
- Model -> Controller
- Domain -> HTTP
- Domain -> Admin/UI
- Service -> Controller
- Core -> presentation/resource
- cross-domain direct mutations

**Do NOT impose a universal `Controller -> Action -> Service -> Repository -> Model` pattern.**
Judge dependencies against actual YALIHAN architecture.

---

### AREA E — MODEL AUTHORITY

Investigate:
- `App\Models\BaseModel`
- `App\Modules\BaseModule\Models\BaseModel`
- direct `extends Model` classes

For each exception determine actual semantics:
- tenant scope
- audit behavior
- casts
- events
- boot hooks
- soft delete
- global scopes

**Especially inspect:**
- SaaS/Plan
- SaaS/Tenant
- Modules/Auth/Role
- Modules/Crm/Kisi

**Do NOT modify inheritance.**
Return whether there is a REAL split-brain or intentional separation.

---

### AREA F — REPOSITORY HYGIENE

Find candidates for:
- duplicate files
- Old/New/V2/Legacy/Backup variants
- debug artifacts
- temporary scripts
- unregistered commands
- unreachable controllers
- unused services
- obsolete resources
- stale test helpers
- historical compatibility layers

**IMPORTANT:** "No textual reference found" ≠ dead code.

Check where applicable:
- routes
- service providers
- container binding
- command registration
- scheduler
- events/listeners
- jobs
- reflection
- config
- dynamic resolution
- tests

**Classify only as ORPHAN_CANDIDATE unless stronger evidence exists.**

---

### AREA G — RUNTIME REACHABILITY

Audit:
- routes -> controllers
- scheduler -> commands
- commands -> services
- events -> listeners
- jobs -> handlers
- container bindings -> implementations

Identify references to missing/nonexistent classes or command signatures.

---

### AREA H — TEST / CONTRACT DISCIPLINE

Map major canonical invariants to regression tests.

Identify:
- duplicate tests defining conflicting authority
- implementation-detail tests
- missing regression protection for proven canonical boundaries
- tests depending on legacy authority
- contract gaps

**Do not create tests.**

---

### AREA I — DIRECTORY DISCIPLINE

Identify inconsistent organization patterns.

Examples: same type of domain logic living simultaneously in:
- Services/
- Domain/
- Modules/
- Actions/

Determine whether inconsistency creates:
- discoverability problem only
- OR real authority ambiguity
- OR runtime defect risk

**Do not recommend mass file moves solely for aesthetic consistency.**

---

### AREA J — STRUCTURAL METRICS

Measure, but do not treat counts as defects:
- commands
- controllers
- models
- services
- actions
- repositories
- jobs
- listeners
- resources
- tests

Also report:
- largest files
- highest dependency fan-out
- highest domain scattering
- potential duplicate naming clusters

**Metrics are evidence for investigation, not severity.**

---

## REQUIRED OUTPUT

### 1. CURRENT ARCHITECTURE MAP

### 2. DOMAIN AUTHORITY MATRIX

```
Domain | Canonical Authority | Write Authority | Tenant Authority | Consumers | Evidence Level
-------|-------------------|----------------|----------------|-----------|---------------
Ilan   |                   |                |                |           |
Rezerv.|                   |                |                |           |
CRM    |                   |                |                |           |
Tenant |                   |                |                |           |
Finance|                   |                |                |           |
```

### 3. TOP REAL FINDINGS (Maximum 10)

Only evidence-backed real problems.

For each:
```
FINDING_ID:
classification:
affected_domain:
root evidence:
runtime reachability:
impact:
bounded fixability:
Evidence Type:
Evidence Level:
```

### 4. ARCHITECTURAL DEBT CANDIDATES (Maximum 10)

Not defects yet.

### 5. ORPHAN CANDIDATES

Do not call them dead code without proof.

### 6. INTENTIONAL EXCEPTIONS

Things that look inconsistent but are architecturally justified.

### 7. DO-NOT-TOUCH LIST

Areas where mass cleanup/refactor would currently be unsafe.

### 8. REMEDIATION QUEUE

Rank using: impact + evidence + reproducibility + bounded fixability

**Do NOT rank by file count or aesthetics.**

### 9. NEW IDEAS

Format:
```
NEW_IDEA:
- title:
- problem:
- proposed improvement:
- expected benefit:
- affected domain:
- risk:
- evidence:
- suggested priority:
- requires_separate_router_task:
```

### 10. FINAL CLASSIFICATION

```
REPOSITORY_ARCHITECTURE: COHERENT / PARTIALLY_FRAGMENTED / FRAGMENTED / UNKNOWN
```

---

## FINAL QUESTION TO ANSWER

```
Does YALIHAN OS currently have one coherent canonical architecture
with historical/intentional exceptions,

OR

are multiple competing architectural authorities actively governing
the same business domains?

Support the answer with repository evidence.
```

---

## CRITICAL RULES

1. **Do NOT write MODEL_BASE_AUTHORITY_DRIFT_TRIAGE or any other candidate to EVIDENCE_INDEX / PROJECT_STATE / DECISION_LOG.**

2. **Finding count is NOT a success metric.**
   Prefer 3 strong REAL_FINDINGs over 30 speculative findings.

3. **Similar naming, directory scattering, direct Model extension, private methods, large files, controller/service counts and missing text references are signals only — never sufficient evidence of defect.**

4. **For every REAL_FINDING establish, where applicable:**
   - SOURCE
   - REGISTRATION/BINDING
   - CALLER
   - RUNTIME REACHABILITY
   - CANONICAL AUTHORITY
   - CONTRADICTION

5. **Distinguish strictly:**
   - REAL_FINDING
   - DUPLICATE_AUTHORITY_CANDIDATE
   - ORPHAN_CANDIDATE
   - LEGACY_REFERENCED
   - INTENTIONAL_BOUNDED_CONTEXT_SPLIT
   - MISPLACED
   - STALE_FINDING
   - UNKNOWN

6. **For write-path findings, prioritize BUSINESS INVARIANT violations over directory aesthetics.**

7. **Do not reopen ADR #006 unless current repository evidence directly contradicts its closed bounded-context contract.**

8. **Production status is UNKNOWN unless independently evidenced. Do not infer production state from repository structure.**

9. **Do not interfere with active _14BV2. No mutations of any kind.**

10. **Remediation queue must be based on: impact + evidence + reproducibility + bounded fixability — NOT severity labels, file counts, LOC, or aesthetics.**

---

## SPECIALLY NOTED

- `MODEL_BASE_AUTHORITY_DRIFT_TRIAGE` is a valid forensic signal but will NOT be written to EVIDENCE_INDEX at this stage.
- This forensic runs in parallel with _14BV2 (separate worktree recommended).
- Repository has existing clutter: ~29 root PNGs, ~65 audits PNGs, 60 Playwright logs, multiple .env backups — these are OUT OF SCOPE unless they directly reveal architectural authority conflicts.

---

## APPROVAL

```
APPROVED BY: Ayhan (Human Decision Owner)
DATE: [2026-09-27]
STATUS: READY FOR FORENSIC RESEARCHER LAUNCH
```
