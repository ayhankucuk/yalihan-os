# YSOS — Quality Gates

> Every sprint must pass all quality gates. Failing gates block certification.

---

## Gate Definitions

```
┌────────────────────────────────────────────────────────────┐
│                    QUALITY GATES                            │
├────────────────────────────────────────────────────────────┤
│                                                            │
│  GATE 1: Tests                                            │
│  ├─ php artisan test --filter=SPRINT_TEST                 │
│  └─ Expected: 100% pass                                    │
│                                                            │
│  GATE 2: Build                                           │
│  ├─ php artisan route:cache                               │
│  ├─ php artisan config:cache                              │
│  └─ Expected: No errors                                   │
│                                                            │
│  GATE 3: Integrity Scan                                  │
│  ├─ php artisan sab:integrity-scan --dirty                │
│  └─ Expected: 0 new violations                           │
│                                                            │
│  GATE 4: Tenant Isolation                                 │
│  ├─ Verified by feature tests                            │
│  └─ Expected: All tenant tests pass                        │
│                                                            │
│  GATE 5: Migration Safety                                 │
│  ├─ php artisan migrate:status                            │
│  └─ Expected: All migrations Ran                          │
│                                                            │
│  GATE 6: Naming Authority                                 │
│  ├─ Context7 field naming verified by sab:scan            │
│  └─ Expected: No new violations                           │
│                                                            │
│  GATE 7: Thin Controller                                  │
│  ├─ sab:integrity-scan THIN_CONTROLLER_GUARD             │
│  └─ Expected: No violations in changed files              │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

---

## Gate 1: Tests

### Command
```bash
php artisan test --filter=SPRINT_TEST
```

### Expected
- 100% of sprint-specific tests pass
- No new test failures introduced

### Failure Action
- Do not certify sprint
- Fix failing tests or document as known debt
- Re-run gate

---

## Gate 2: Build

### Commands
```bash
php artisan route:cache
php artisan config:cache
php artisan view:cache
```

### Expected
- All commands succeed without errors
- No missing routes
- No missing configs

### Failure Action
- Fix route or config errors
- Do not proceed until cache builds succeed

---

## Gate 3: Integrity Scan

### Command
```bash
php artisan sab:integrity-scan --dirty
```

### Expected
- 0 new violations in changed files
- Pre-existing violations are baseline (not counted)

### Violation Categories
| Category | Blocking? |
|----------|-----------|
| NamingAuthorityAST | LOW — acceptable with context7-ignore |
| THIN_CONTROLLER_GUARD | MEDIUM — must fix complex branching |
| SILENT_CATCH_GUARD | HIGH — must log errors |
| TenantIsolation | CRITICAL — must fix immediately |

### Failure Action
- HIGH or CRITICAL: Do not certify. Fix immediately.
- LOW: Document as known debt. May certify with approval.

---

## Gate 4: Tenant Isolation

### Verification
```bash
php artisan test --filter=TenantIsolation
```

### Expected
- All tenant isolation tests pass
- No cross-tenant data access

### Failure Action
- CRITICAL: Do not certify. Fix immediately.

---

## Gate 5: Migration Safety

### Command
```bash
php artisan migrate:status
```

### Expected
- All migrations show "Ran"
- No pending migrations
- No broken migrations

### Failure Action
- Run `php artisan migrate`
- If migration fails, do not certify

---

## Gate 6: Naming Authority

### Context
Context7 Turkish canonical field names must be used for domain model fields.

| Check | Tool |
|-------|------|
| Database fields | `context7:migrate-field-check` |
| Controller input | `sab:integrity-scan --dirty` |
| Service fields | Manual review |

### Failure Action
- Use `// context7-ignore` for non-DB fields (local variables)
- Use Turkish names for domain fields
- Do not mix naming conventions

---

## Gate 7: Thin Controller

### Rule
All write operations must flow through the service layer.

### Verification
```bash
php artisan sab:integrity-scan --dirty --guard=THIN_CONTROLLER_GUARD
```

### Expected
- No direct Eloquent::create/update/delete in controllers
- No raw DB queries in controllers

### Failure Action
- Refactor to service layer
- Document if exception is necessary

---

## Gate 8: Schema Contract Verification (SAAB-QG-009)

> **Effective:** 2026-08-05
> **Pattern ID:** LP-008

### Rule
On any "table/column not found" or "no such table" error, verify schema contract before making infrastructure changes.

### Verification Order (Canonical Chain)
```
Model ($table)
    ↓
Migration (Schema::create)
    ↓
Service (Eloquent / Query)
    ↓
Test (Assertion / Fixture)
    ↓
CI Bootstrap
```

### Verification Commands
```bash
# 1. Check Model $table
grep -n '$table' app/Models/PropertyAvailability.php

# 2. Check Migration table name
grep -n "Schema::create\|Schema::table" database/migrations/*availability*.php

# 3. Check Service queries
grep -n "PropertyAvailability::" app/Services/**/*.php

# 4. Check Test assertions
grep -n "property_availability" tests/**/*.php

# 5. Check CI migration
grep -n "migrate" .github/workflows/*.yml
```

### Expected
- All layers use consistent table name
- Test assertions match Model/Migration contract
- No hardcoded table names in tests (use Model::getTable() instead)

### Failure Action
- Fix test assertions, NOT infrastructure
- ❌ Do NOT add RefreshDatabase unless proven necessary
- ❌ Do NOT modify CI bootstrap or workflow
- ❌ Do NOT create new migrations for existing tables
- ❌ Do NOT modify Models or Services if they are consistent

### Rejected Hypotheses Pattern
When diagnosing schema errors, explicitly document and reject:
- ❌ RefreshDatabase missing
- ❌ CI bootstrap migration problem
- ❌ Migration file missing for existing table
- ❌ Model table name mismatch

---

## Automated Gate Execution

Quality gates should be run automatically at sprint close:

```bash
#!/bin/bash
# ysos:sprint:validate

echo "Running Quality Gates..."

php artisan sab:integrity-scan --dirty
if [ $? -ne 0 ]; then
    echo "GATE 3 FAILED: Integrity Scan"
    exit 1
fi

php artisan test --filter=SPRINT_TEST
if [ $? -ne 0 ]; then
    echo "GATE 1 FAILED: Tests"
    exit 1
fi

php artisan route:cache
if [ $? -ne 0 ]; then
    echo "GATE 2 FAILED: Build"
    exit 1
fi

echo "ALL GATES PASSED"
```

---

## Gate Results Template

```
┌─────────────────────────────────────────────────────────┐
│ SPRINT X.Y — QUALITY GATE RESULTS                       │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ GATE 1: Tests                                           │
│ Command: php artisan test --filter=SPRINT_TEST          │
│ Result: ✅ PASS / ❌ FAIL                               │
│ Details: X passed, Y failed                              │
│                                                          │
│ GATE 2: Build                                           │
│ Command: php artisan route:cache                        │
│ Result: ✅ PASS / ❌ FAIL                               │
│ Details: [Build output]                                 │
│                                                          │
│ GATE 3: Integrity Scan                                 │
│ Command: php artisan sab:integrity-scan --dirty         │
│ Result: ✅ PASS / ❌ FAIL                               │
│ Details: 0 new violations / X new violations           │
│                                                          │
│ GATE 4: Tenant Isolation                                │
│ Command: php artisan test --filter=TenantIsolation      │
│ Result: ✅ PASS / ❌ FAIL                               │
│ Details: X passed, Y failed                             │
│                                                          │
│ GATE 5: Migration Safety                                │
│ Command: php artisan migrate:status                     │
│ Result: ✅ PASS / ❌ FAIL                               │
│ Details: All migrations Ran                              │
│                                                          │
│ ──────────────────────────────────────────────────────── │
│ OVERALL: ✅ ALL GATES PASSED — SPRINT CERTIFIED         │
│          ❌ GATES FAILED — SPRINT NOT CERTIFIED         │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

---

*Failing gates block certification. No exceptions.*
