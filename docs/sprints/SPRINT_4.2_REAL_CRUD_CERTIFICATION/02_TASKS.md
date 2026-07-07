# 02_TASKS.md — Sprint 4.2

## Task List

### Task 1: Fix ucfirst() on IlanDurumu enum in blade views
**Priority:** HIGH
**Type:** Bug Fix
**Files:** `resources/views/owner/ilanlar/index.blade.php`, `resources/views/owner/ilanlar/show.blade.php`

**Change:**
```php
// BEFORE (line 45 / 27)
{{ ucfirst($ilan->yayin_durumu ?? 'Pasif') }}

// AFTER
{{ $ilan->yayin_durumu->label() ?? 'Pasif' }}
```

### Task 2: Implement edit() method in OwnerIlanController
**Priority:** HIGH
**Type:** Feature Implementation
**Files:** `app/Http/Controllers/Owner/OwnerIlanController.php`

**Implementation:**
- `edit($id)` method: return `owner.ilanlar.edit` view
- Load ilan with relationships, check ownership
- Load form data (kategoriler, iller)
- Must return 404 if not owner

### Task 3: Implement update() method in OwnerIlanController
**Priority:** HIGH
**Type:** Feature Implementation
**Files:** `app/Http/Controllers/Owner/OwnerIlanController.php`

**Implementation:**
- `update(StoreOwnerIlanRequest $request, $id)` method
- Validate ownership (same as show/edit)
- Strip `yayin_durumu` from request (owner cannot change status)
- Delegate to `IlanService::updateListing()`
- Redirect to `owner.ilanlar.show`

### Task 4: Implement destroy() method in OwnerIlanController
**Priority:** HIGH
**Type:** Feature Implementation
**Files:** `app/Http/Controllers/Owner/OwnerIlanController.php`

**Implementation:**
- `destroy($id)` method
- Check ownership (same pattern)
- Soft delete via service
- Redirect to `owner.ilanlar.index`

### Task 5: Implement readiness() method (if truly missing)
**Priority:** MEDIUM
**Type:** Feature Implementation
**Files:** `app/Http/Controllers/Owner/OwnerIlanController.php`

**Check first:** Is readiness view/template required? Route exists. If no view needed, stub with 501.

### Task 6: Run OwnerIlanCrudTest — verify 20/20 pass
**Priority:** HIGH
**Type:** Verification
**Command:** `php artisan test --filter=OwnerIlanCrudTest`

### Task 7: Run full test suite — no new failures
**Priority:** HIGH
**Type:** Verification
**Command:** `php artisan test`

### Task 8: sab:integrity-scan --dirty
**Priority:** HIGH
**Type:** Verification
**Command:** `php artisan sab:integrity-scan --dirty`
**Expected:** 0 violations

---

## Task Execution Order

```
Task 1 (blade fix)
       ↓
Task 2 (edit) + Task 3 (update) + Task 4 (destroy) + Task 5 (readiness)
       ↓
Task 6 (OwnerIlanCrudTest)
       ↓
Task 7 (full test suite)
       ↓
Task 8 (sab:dirty scan)
       ↓
Sprint Close Documents
```
