# INC-2026-0627-T01: Tenant Model Architecture Mismatch

**Date:** 2026-06-27
**Severity:** MEDIUM
**Type:** Architecture / Technical Debt
**Status:** OPEN

---

## Summary

`AIResilienceTest` is blocked by a tenant model architecture mismatch between the legacy `App\Models\Tenant` and the new `App\Models\SaaS\Tenant`.

---

## Root Cause

```
User.tenant() relationship
└── returns App\Models\Tenant (legacy)

TenantContextResolver.getTenant()
└── expects App\Models\SaaS\Tenant (new)
```

The `User` model has a `tenant()` relationship that returns the legacy `Tenant` model:

```php
// app/Models/User.php:139-141
public function tenant()
{
    return $this->belongsTo(Tenant::class);  // App\Models\Tenant
}
```

But `TenantContextResolver.getTenant()` declares a return type of `App\Models\SaaS\Tenant`:

```php
// app/Application/Shared/Services/TenantContextResolver.php:56
public function getTenant(): \App\Models\SaaS\Tenant  // TypeError on mismatch
{
    return $user->tenant;  // Returns App\Models\Tenant
}
```

---

## Affected Classes

| Class | Issue |
|-------|-------|
| `App\Models\User` | `tenant()` returns wrong Tenant type |
| `App\Application\Shared\Services\TenantContextResolver` | Requires `SaaS\Tenant` |
| `tests/Feature/AI/AIResilienceTest` | Blocked by TypeError |

---

## Why Partial Test Fix Is Insufficient

A test-only fix (creating `SaaS\Tenant` instead of `Tenant`) would:
1. Mask the underlying architecture problem
2. Not resolve the type declaration mismatch
3. Create a fragile test that depends on production bug

---

## Fix Options

### Option A: Migrate User.tenant() to SaaS\Tenant (Recommended)

Update `User.tenant()` relationship to return `SaaS\Tenant`:

```php
// app/Models/User.php
public function tenant(): BelongsTo
{
    return $this->belongsTo(\App\Models\SaaS\Tenant::class, 'tenant_id');
}
```

**Pros:** Clean architecture, single Tenant model
**Cons:** Requires migration of existing tenant data

### Option B: Update TenantContextResolver Return Type

Change return type to accept both models:

```php
public function getTenant(): \App\Models\Tenant
```

**Pros:** Minimal change
**Cons:** Maintains dual model architecture

### Option C: Merge Tenant Models

Deprecate `App\Models\Tenant`, migrate all to `App\Models\SaaS\Tenant`

**Pros:** Clean slate
**Cons:** Large migration scope

---

## Verification Command

```bash
php artisan test tests/Feature/AI/AIResilienceTest
```

Expected after fix: All 3 tests pass

---

## Related

- INC-2026-0625-R08, R09, R10 (previous incidents)
- Sprint 3.1 Technical Debt items

---

## Resolution

Requires architectural decision before proceeding.
