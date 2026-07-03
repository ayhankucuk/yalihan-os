# 01_CONTEXT.md — Sprint 4.2

## How We Got Here

### Sprint 4.1 Closing (Session 66)
Sprint 4.1 (Alpine.js UI Stabilization) closed with:
- Finans Komisyonlar blade + 4 SAB violation fixes
- `php artisan sab:integrity-scan --dirty` → 0 violations
-bekci:health → 68.89%

### Post-Sprint 4.1 Regression Found
Running `php artisan test --filter=IlanCrudTest` revealed:
- **11 failed, 9 passed** in `OwnerIlanCrudTest`

---

## Root Cause Analysis

### Defect 1: `ucfirst()` on IlanDurumu Enum (500 Error)

**File:** `resources/views/owner/ilanlar/index.blade.php:45`
```php
{{ ucfirst($ilan->yayin_durumu ?? 'Pasif') }}
```

**File:** `resources/views/owner/ilanlar/show.blade.php:27`
```php
{{ ucfirst($ilan->yayin_durumu ?? 'Pasif') }}
```

`yayin_durumu` is cast as `IlanDurumu` enum in `Ilan` model.
PHP's `ucfirst()` requires `string`, throws `TypeError` on enum.

**Impact:** 500 error on `owner.ilanlar.index` and `owner.ilanlar.show`
→ Authorization tests that check for 404 instead get 500

---

### Defect 2: Missing Controller Methods

Routes registered but methods missing in `OwnerIlanController`:

| Route | Controller Method | Status |
|-------|-------------------|--------|
| `owner.ilanlar.edit` | `edit()` | **MISSING** |
| `owner.ilanlar.update` | `update()` | **MISSING** |
| `owner.ilanlar.destroy` | `destroy()` | **MISSING** |
| `owner.ilanlar.readiness` | `readiness()` | **MISSING** |

**Error:** `BadMethodCallException: Method App\Http\Controllers\Owner\OwnerIlanController::edit does not exist.`

---

## Evidence of Defects

### Test Output (abbreviated)
```
FAILED owner_cannot_view_other_owners_ilan
  Expected response status code [404] but received 500.

FAILED owner_cannot_access_edit_form_of_other_owners_ilan
  BadMethodCallException: Method edit does not exist.

FAILED owner_cannot_update_other_owners_ilan
  BadMethodCallException: Method update does not exist.

FAILED owner_cannot_delete_other_owners_ilan
  Expected response status code [404] but received 500.
```

---

## Known Pre-Existing Issues (Not in Sprint Scope)

### 1. sab:integrity-scan: 47 violations (pre-existing, committed code)
- `app/Services/Wizard/WizardAIAssistantService.php` — `type` field
- `app/Services/Wizard/WizardContextService.php` — `category` + `type` fields
- `app/Traits/EnforcesContext7Guard.php` — `status`, `is_active`
- `app/Traits/Ilan/IlanRelationships.php` — `active` forbidden field
- All MEDIUM/LOW severity, not blocking this sprint

### 2. bekci:health: 68.89% (MCP Server offline)
- Non-blocking for sprint completion

### 3. Git dirty: 8 unstaged + 22 untracked files
- Previous session incomplete commit state
- Will be resolved at sprint close

---

## Technical Context

### OwnerIlanController (current)
```
OwnerIlanController
  ├── index()  ✅
  ├── show()   ✅ (but view has ucfirst bug)
  ├── create() ✅
  ├── store()  ✅
  ├── edit()   ❌ MISSING
  ├── update() ❌ MISSING
  ├── destroy() ❌ MISSING
  └── readiness() ❌ MISSING
```

### Ilan Model Enum Cast
```php
// Ilan.php
protected $casts = [
    'yayin_durumu' => IlanDurumu::class,
];
```

### IlanDurumu Enum
```php
enum IlanDurumu: string {
    case TASLAK = 'taslak';
    case YAYINDA = 'yayinda';
    case PASIF = 'pasif';
    case SILINDI = 'silindi';
}
```

### Fix Strategy

1. **Blade fixes:** Replace `ucfirst($ilan->yayin_durumu)` with `$ilan->yayin_durumu->label()` or `$ilan->yayin_durumu->value`
2. **Missing methods:** Implement edit/update/destroy/readiness following SAB thin-controller pattern
3. **All write operations via IlanService → IlanCrudService**

---

## Sprint Boundary

**What belongs to Sprint 4.2:**
- Fixing the 11 test failures in OwnerIlanCrudTest
- Making all Owner Portal CRUD routes functional
- Ensuring enum-safe blade templates

**What does NOT belong:**
- Admin Ilan CRUD improvements
- Kisi/Talep/Komisyon CRUD (Sprint 4.2 backlog item for future)
- AI Workforce (Sprint 4.3)
- MCP Server activation
- 47 pre-existing SAB violations
