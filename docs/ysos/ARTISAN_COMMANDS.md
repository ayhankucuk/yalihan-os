# YSOS — Artisan Command Contracts

> Design contracts for YSOS Artisan commands. Implementation is optional unless requested.

---

## Command Philosophy

YSOS commands follow the principle:
> **Automation over memory. Commands over documentation.**

Every repetitive task that can be automated becomes a command.

---

## Command Structure

```
ysos
├── :sprint
│   ├── init           Create new sprint workspace
│   ├── status         Show sprint health
│   ├── validate       Run quality gates
│   ├── handoff       Generate handoff package
│   └── close         Close sprint
├── :context
│   ├── refresh       Refresh context files
│   └── sync          Sync context across agents
└── :architecture
    ├── audit         Audit architecture compliance
    └── drift         Detect architecture drift
```

---

## ysos:sprint:init

### Purpose
Create a new sprint workspace from the SPRINT_TEMPLATE.

### Signature
```bash
php artisan ysos:sprint:init {sprintId}
    {--from=}          Previous sprint to copy context from
    {--mission=}      Sprint mission statement
    {--scope=}        Sprint scope (in/out)
    {--skip-template}  Skip template copy
```

### Contract
```
INPUT:
  sprintId: string (required) — e.g., "4.3"
  from: ?string (optional) — e.g., "4.2"
  mission: ?string (optional)
  scope: ?string (optional)

OUTPUT:
  Creates: docs/sprints/SPRINT_4_3/
  ├── 00_CHARTER.md  (pre-filled)
  ├── 01_CONTEXT.md  (pre-filled from previous sprint)
  ├── 02_TASKS.md    (template)
  ├── 03_DECISIONS.md (template)
  ├── 04_PROGRESS.md (template)
  ├── 05_TEST_REPORT.md (template)
  ├── 06_CERTIFICATION.md (template)
  └── 07_HANDOFF.md  (template)

VALIDATION:
  - sprintId must be valid format (X.Y)
  - sprint directory must not exist
  - previous sprint must exist (if --from specified)

ERRORS:
  - "Sprint 4.3 already exists" → abort
  - "Previous sprint 4.2 not found" → abort
  - "Invalid sprint ID format" → abort

SIDE EFFECTS:
  - Creates sprint directory
  - Copies template files
  - Updates PROGRESS-TRACKER.md
```

### Example
```bash
php artisan ysos:sprint:init 4.3 \
  --from=4.2 \
  --mission="AI Workforce Zinciri" \
  --scope="In: Photo Agent | Out: Description Agent"
```

---

## ysos:sprint:status

### Purpose
Show current sprint health at a glance.

### Signature
```bash
php artisan ysos:sprint:status
    {--sprint=}       Sprint ID (default: current)
    {--detailed}      Show detailed output
```

### Contract
```
OUTPUT:
  SPRINT 4.3 — AI Workforce Zinciri
  Status: IN PROGRESS
  Started: 2026-07-03

  TASKS:
    P0: 3/5 complete
    P1: 1/3 complete
    P2: 0/2 complete

  QUALITY GATES:
    Tests:   ⚠️  12/15 (3 pre-existing)
    Build:   ✅  PASS
    SAB:     ✅  0 new violations
    Tenant:  ✅  PASS

  PROGRESS: ████████░░░░░░░  45%

  BLOCKERS:
    - None

VALIDATION:
  - Sprint directory must exist
  - Sprint documents must be readable

ERRORS:
  - "Sprint not found" → abort
  - "Sprint documents missing" → warning
```

### Example
```bash
php artisan ysos:sprint:status
php artisan ysos:sprint:status --sprint=4.2 --detailed
```

---

## ysos:sprint:validate

### Purpose
Run all quality gates for the current sprint.

### Signature
```bash
php artisan ysos:sprint:validate
    {--sprint=}       Sprint ID (default: current)
    {--gate=}         Run specific gate (1-5)
    {--fail-fast}     Stop on first failure
```

### Contract
```
OUTPUT:
  Running Quality Gates for Sprint 4.3...

  GATE 1: Tests
  Command: php artisan test --filter=Sprint43Test
  Result: ✅ PASS (20/20)

  GATE 2: Build
  Command: php artisan route:cache
  Result: ✅ PASS

  GATE 3: Integrity Scan
  Command: php artisan sab:integrity-scan --dirty
  Result: ✅ PASS (0 new violations)

  GATE 4: Tenant Isolation
  Command: php artisan test --filter=TenantIsolation
  Result: ✅ PASS (6/6)

  GATE 5: Migration Safety
  Command: php artisan migrate:status
  Result: ✅ PASS (All Ran)

  ─────────────────────────────────────
  ALL GATES PASSED — SPRINT 4.3 CERTIFIED

VALIDATION:
  - All gates must pass for certification

ERRORS:
  - "GATE 1 FAILED" → show failure details
  - "GATE 3 FAILED: 5 new violations" → list violations

EXIT CODE:
  0 = All gates passed
  1 = One or more gates failed
```

### Example
```bash
php artisan ysos:sprint:validate
php artisan ysos:sprint:validate --gate=3 --fail-fast
```

---

## ysos:sprint:handoff

### Purpose
Generate a handoff package for sprint close.

### Signature
```bash
php artisan ysos:sprint:handoff
    {sprintId}         Sprint ID
    {--output=}         Output format (md/json)
    {--include-evidence} Include full evidence
```

### Contract
```
OUTPUT:
  Generating Handoff for Sprint 4.2...

  ✓ Loaded context
  ✓ Generated 07_HANDOFF.md
  ✓ Generated 05_TEST_REPORT.md
  ✓ Generated 06_CERTIFICATION.md

  HANDOFF PACKAGE: docs/sprints/SPRINT_4_2/
  ├── 07_HANDOFF.md      ✅
  ├── 05_TEST_REPORT.md  ✅
  └── 06_CERTIFICATION.md ✅

  Next Sprint: 4.3

VALIDATION:
  - All sprint documents must exist
  - Test results must be available

ERRORS:
  - "Sprint not found" → abort
  - "Documents missing" → list missing files

SIDE EFFECTS:
  - Updates 04_PROGRESS.md
  - Updates PROGRESS-TRACKER.md
  - Updates BEKCI_CHANGELOG.md
```

### Example
```bash
php artisan ysos:sprint:handoff 4.2
php artisan ysos:sprint:handoff 4.2 --include-evidence
```

---

## ysos:sprint:close

### Purpose
Close a sprint and prepare for the next one.

### Signature
```bash
php artisan ysos:sprint:close
    {sprintId}         Sprint ID
    {--next=}           Next sprint ID (e.g., 4.3)
    {--message=}        Commit message
    {--push}           Push after close
```

### Contract
```
OUTPUT:
  Closing Sprint 4.2...

  ✓ Validated quality gates
  ✓ Generated handoff
  ✓ Updated PROGRESS-TRACKER.md
  ✓ Updated BEKCI_CHANGELOG.md
  ✓ Created commit
  ✓ Pushed to origin

  SPRINT 4.2 CLOSED ✅
  NEXT: php artisan ysos:sprint:init 4.3

VALIDATION:
  - All gates must pass (or CONDITIONAL PASS approved)
  - All documents must be complete
  - Git working tree must be clean

ERRORS:
  - "Sprint not certified" → abort unless --force
  - "Uncommitted changes" → abort
  - "Git push failed" → warning

SIDE EFFECTS:
  - Marks sprint as CLOSED in documents
  - Creates git tag (optional)
  - Pushes to remote
```

### Example
```bash
php artisan ysos:sprint:close 4.2 --next=4.3 --push
```

---

## ysos:context:refresh

### Purpose
Refresh context files from repository state.

### Signature
```bash
php artisan ysos:context:refresh
    {--what=}          What to refresh (all|sprint|architecture)
    {--dry-run}        Show what would change
```

### Contract
```
OUTPUT:
  Refreshing YSOS context...

  ✓ PROGRESS-TRACKER.md: Updated
  ✓ ROADMAP.md: Verified
  ✓ BEKCI_CHANGELOG.md: Synced

  Context files refreshed.

VALIDATION:
  - Repository must be clean

ERRORS:
  - "Uncommitted changes" → warning
```

### Example
```bash
php artisan ysos:context:refresh
php artisan ysos:context:refresh --dry-run
```

---

## ysos:architecture:audit

### Purpose
Audit architecture compliance across the codebase.

### Signature
```bash
php artisan ysos:architecture:audit
    {--scope=}         Scope (full|changed|module)
    {--module=}       Specific module to audit
    {--format=}       Output format (table|json|md)
```

### Contract
```
OUTPUT:
  Architecture Audit for Sprint 4.3

  THIN CONTROLLER:     ✅ COMPLIANT
  TENANT ISOLATION:    ✅ COMPLIANT
  NAMING AUTHORITY:    ⚠️  12 violations
  REPOSITORY PATTERN:  ✅ COMPLIANT

  VIOLATIONS:
  ┌────────────────────────────────────┬────────┬──────────┐
  │ File                               │ Line   │ Type     │
  ├────────────────────────────────────┼────────┼──────────┤
  │ app/Controller/UserController.php  │ 43     │ NAMING   │
  │ app/Service/UserService.php        │ 12     │ CATCH    │
  └────────────────────────────────────┴────────┴──────────┘

VALIDATION:
  - sab:integrity-scan results parsed

ERRORS:
  - "Audit failed" → list failures
```

### Example
```bash
php artisan ysos:architecture:audit
php artisan ysos:architecture:audit --scope=changed --format=json
```

---

## Command Design Principles

### 1. Fail Loudly
Commands fail with clear messages, not silently.

### 2. Validate Early
Validate inputs at the start, not the end.

### 3. Idempotent by Default
Running the same command twice produces the same result.

### 4. Document Side Effects
Every command documents what it changes.

### 5. Return Exit Codes
0 = success, 1 = failure, 2 = warning.

### 6. Progress Indicators
Long-running commands show progress.

---

## Implementation Priority

| Command | Priority | Status |
|---------|----------|--------|
| `ysos:sprint:init` | HIGH | Design only |
| `ysos:sprint:status` | HIGH | Design only |
| `ysos:sprint:validate` | HIGH | Design only |
| `ysos:sprint:handoff` | MEDIUM | Design only |
| `ysos:sprint:close` | MEDIUM | Design only |
| `ysos:context:refresh` | LOW | Design only |
| `ysos:architecture:audit` | LOW | Design only |

---

*Commands are designed, not implemented unless requested.*
