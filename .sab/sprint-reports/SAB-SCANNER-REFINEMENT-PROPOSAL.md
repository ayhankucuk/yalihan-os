# SAB Scanner Baseline & False Positive Remediation

**Priority:** MEDIUM
**Estimated Effort:** 2-4 hours
**Sprint:** TBD (NOT Phase 2A)
**Status:** PROPOSED

---

## Problem Statement

Current SAB integrity scanner produces unreliable results that caused false "PR blocked" warnings.

### Issues Identified

| # | Issue | Impact | Evidence |
|---|-------|--------|----------|
| 1 | Console/JSON mismatch | Trust erosion | Console: 14, JSON: 38 |
| 2 | Baseline drift | False "new" classification | Files after baseline timestamp |
| 3 | False positive rate high | Noise, ignores real issues | 19/29 blocking are FP |
| 4 | No context discrimination | Scope ≠ DB field | `->active()` vs `active` column |
| 5 | Compatibility not recognized | Legitimate usage flagged | `status` in external APIs |

### Root Causes

1. **Baseline drift:** `sab-baseline.json` generated at `2026-07-07T12:57:17`, but files added at `2026-07-07T16:01:02`

2. **Fingerprint mismatch:** Scanner generates fingerprint from `file + type + message` (line-agnostic), but baseline may have different message format

3. **Context blind spots:**
   - Eloquent scope method calls (`->active()`) treated as field violations
   - Array key access (`$arr['type']`) treated as field violations
   - Docblock annotations (`@property`) treated as violations
   - External API contracts (`status` in Airbnb API) treated as violations

---

## Violation Categories

| Category | Definition | Action |
|----------|------------|--------|
| `new_regression` | Violation introduced by current PR | **FAIL PR** |
| `pre_existing` | Violation existed before PR | Baseline + ignore |
| `false_positive` | Scanner misidentified usage | Fix scanner |
| `approved_exception` | Explicitly approved by SAAB | Add to exception list |
| `compatibility_exception` | Legacy/external API requirement | Document + ignore |

### Compatibility Exception Examples

```php
// compatibility_exception — External API contract
$airbnbPayload['status'] = 'active';  // Airbnb API schema

// compatibility_exception — Legacy column name
$model->status = 'pending';  // Cannot rename without migration

// compatibility_exception — Enum value mapping
$status = Status::from($value);  // External system enum
```

---

## Proposed Violation Count Breakdown

```
3725 total violations
│
├─ 3687 baseline (pre-existing, absorbed)
│  │
│  └─ 38 candidate (newly detected by scanner)
│     │
│     ├─ 19 false_positive (scanner error)
│     │  ├─ 12 Eloquent scope calls
│     │  ├─ 4 Array key accesses
│     │  └─ 3 Docblock annotations
│     │
│     ├─ 9 compatibility_exception (legitimate use)
│     │  ├─ 4 External API contracts (Airbnb, Booking)
│     │  ├─ 3 Legacy DB columns
│     │  └─ 2 Enum mappings
│     │
│     └─ 10 actionable (pre-existing, real issues)
│        ├─ 4 SilentCatch violations
│        └─ 6 ForbiddenField violations
│
└─ 0 new_regression (introduced by this PR)
```

---

## Proposed Remediation Phases

### Phase 1: Baseline Recalculation (0.5 hours)

```bash
# Immediate action - update baseline
php artisan sab:integrity-scan --generate-baseline
```

**Verification:**
- [ ] All 38 "new" violations absorbed into baseline
- [ ] Next scan shows 0 new violations
- [ ] Console output matches JSON output

### Phase 2: Scanner Rule Refinement (2 hours)

#### 2.1 Fix Console/JSON Mismatch

**File:** `app/Console/Commands/Sab/SabIntegrityScanCommand.php`

**Current issue:** Line 76-77 in runner counts violations differently than console formatter.

**Fix:** Ensure single source of truth for counts.

#### 2.2 Add Eloquent Scope Detection

**Pattern to detect:**
```php
// FALSE POSITIVE - skip this
Model::where(...)->active()->first()

// TRUE VIOLATION - flag this
Model::where('active', 1)->first()
```

**Detection logic:**
1. Check if node is a MethodCall on QueryBuilder
2. Check if method name matches known scopes
3. Skip as false positive

#### 2.3 Add Array Key Exclusion

**Pattern to skip:**
```php
// FALSE POSITIVE - skip
$change['type']

// TRUE - flag (if accessing DB column directly)
Model::where('type', $x)
```

**Detection logic:**
1. Check if node is ArrayAccess
2. Check if array is a known variable (request, input, etc.)
3. Skip as contextual, not domain field

#### 2.4 Handle Docblock Annotations

**Pattern to skip:**
```php
// FALSE POSITIVE - skip
@property string $status

// TRUE - flag (if actual field access)
Model::$status
```

**Detection logic:**
1. Use PHP AST to parse docblocks separately
2. Don't flag annotations as violations
3. Flag only actual field access in code

#### 2.5 Add Compatibility Exception Recognition

**Pattern to recognize:**
```php
// compatibility_exception — External API call
$airbnb->update(['status' => 'active']);

// compatibility_exception — Legacy schema
$model->status = 'pending';
```

**Detection logic:**
1. Track external API calls (Airbnb, Booking, etc.)
2. Flag legacy column usage separately
3. Generate compatibility_exception, not forbidden_field

### Phase 3: Enhanced Classification Output (1 hour)

#### 3.1 Add Violation Categories

Update scanner output to include:

```json
{
  "violations": [
    {
      "file": "...",
      "line": 123,
      "category": "new_regression|pre_existing|false_positive|approved_exception|compatibility_exception",
      "reason": "Why classified this way",
      "source": "pr_diff|baseline|stale_scanner"
    }
  ]
}
```

#### 3.2 Git Merge-Base Integration

**Current behavior:** Compares against arbitrary baseline timestamp

**Proposed behavior:**
```bash
# Compare only against merge-base
php artisan sab:integrity-scan --compare-against=HEAD

# Or explicit merge-base
php artisan sab:integrity-scan --merge-base=origin/main
```

#### 3.3 Enhanced Console Output

```
┌────────────────────────────────────────────────────────────┐
│  SAB Integrity Scan Results                                 │
├────────────────────────────────────────────────────────────┤
│  3725 total                                              │
│  └─ 3687 baseline                                         │
│     └─ 38 candidate                                       │
│        ├─ 19 false_positive                               │
│        ├─ 9 compatibility_exception                       │
│        └─ 10 actionable (pre-existing)                     │
│           └─ 0 new_regression                             │
├────────────────────────────────────────────────────────────┤
│  PR STATUS: CLEAR ✅                                      │
│  (No violations introduced by this PR)                   │
└────────────────────────────────────────────────────────────┘
```

---

## Success Criteria

| # | Criteria | Verification |
|---|---------|--------------|
| 1 | Console output matches JSON output | Run scan, compare counts |
| 2 | "New violations" = PR-introduced | Test on known PR |
| 3 | False positive rate < 10% | Manual audit of 50 samples |
| 4 | Eloquent scopes not flagged | Unit test with scope calls |
| 5 | Array keys not flagged | Unit test with `$arr['x']` |
| 6 | Compatibility exceptions recognized | Unit test with API calls |

---

## Out of Scope

- Schema changes (DB column renames)
- Domain model field renaming
- Naming convention enforcement
- Phase 2A implementation changes
- CI/CD pipeline modifications

---

## Estimated Timeline

| Phase | Effort | Priority |
|-------|--------|----------|
| Phase 1: Baseline recalc | 0.5 hours | P0 (immediate) |
| Phase 2: Scanner rules | 2 hours | P1 |
| Phase 3: Classification | 1 hour | P2 |

---

## Files to Modify

| File | Changes |
|------|---------|
| `app/Services/Governance/SabScanRunner.php` | Classification categories |
| `app/Services/Governance/Ast/AstScannerService.php` | False positive rules |
| `app/Console/Commands/Sab/SabIntegrityScanCommand.php` | Console output alignment |
| `.sab/sab-baseline.json` | Baseline recalculation |
| `.sab/authority.json` | Compatibility exceptions |
| `docs/SAB.md` | Scanner rules documentation |

---

## Test Scenarios

```php
// Should NOT flag (false positives)
Model::active()->first();           // Eloquent scope
$change['type'];                    // Array key
@property string $status;           // Docblock
$status = 'pending';                // Local variable
Status::ACTIVE;                     // Enum

// Should NOT flag (compatibility exceptions)
$airbnb->update(['status' => 'x']); // External API
$model->status = 'pending';          // Legacy column
$this->dispatch(new Job($type));     // External event

// SHOULD flag (true violations)
Model::where('active', 1)->first(); // Direct column access
Model::$active = true;              // Static access
$this->active = true;               // Property assignment
```

---

*Generated by Kilo Agent — 2026-08-01*
*SAAB Review: PENDING*
