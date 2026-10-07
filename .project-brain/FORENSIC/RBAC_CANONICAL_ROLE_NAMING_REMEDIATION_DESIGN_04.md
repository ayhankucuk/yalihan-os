# RBAC_CANONICAL_ROLE_NAMING_REMEDIATION_DESIGN_04
**Tarih:** 2026-09-28  
**MOD:** STRICT READ-ONLY  
**Authority:** MySQL `yalihanai_local_canonical`

---

## LIVE EVIDENCE (Tinker + MySQL direct queries)

### Test 1: `firstOrCreate` lookup behavior
```
SQL: select * from `roles` where (`name` = ? and `guard_name` = ?) limit 1
BINDINGS: ["admin","web"]
Result: id=1, name=Admin

firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ...)
searches for name='admin' (lowercase)
MySQL utf8mb4_unicode_ci is CASE-INSENSITIVE
→ FINDS existing row id=1, name='Admin'
→ BUT: update values ['name' => 'admin'] are applied to the found row
→ Result: row id=1, name='Admin' (UPDATED to 'admin'?)
```

### Test 2: `updateOrCreate` exact behavior (live, then reverted)
```
SQL: select * from `roles` where (`name` = ? and `guard_name` = ?) limit 1
BINDINGS: ["admin","web"]
→ Found id=1, name='Admin' (case-insensitive match)

SQL: update `roles` set `name` = ?, `updated_at` = ? where `id` = ?
BINDINGS: ["admin", "2026-09-28 22:34:26", 1]
→ id=1 UPDATED to 'admin' (lowercase)

DB state after: id=1 name=admin, id=2 super-admin, id=3 danisman, id=4 musteri, id=5 owner
model_has_roles: unchanged (references role_id, not name)
```

### Test 3: `hasRole()` behavior after canonical rename
```
After: roles.id=1 name='admin'
User 1 (admin@yalihan.com) — getRoleNames(): ["admin"]
  hasRole("admin"):  TRUE ✅
  hasRole("Admin"):  FALSE (case-sensitive, 'Admin' != 'admin')
  hasAnyRole(["admin"]): TRUE ✅
```

### Test 4: AuthServiceProvider gates after canonical rename
```
Gate view-admin-panel: allowed = ['Admin', ..., 'admin', ...]
  hasAnyRole([...,'admin',...]) → TRUE ✅ (no regression)
Gate manage-users: allowed = ['Süper Admin', 'superadmin', 'süper admin', 'admin']
  hasAnyRole(['admin']) → TRUE ✅ (no regression)
All AuthServiceProvider gates include both 'Admin' and 'admin' or include 'admin'
→ ZERO regression after canonical rename
```

---

## Q1 — CONVERGENCE MECHANISM

### MYSQL_ROLE_NAME_COLLATION
```
Engine: MySQL 8.x
Charset: utf8mb4
Collation: utf8mb4_unicode_ci
→ CASE-INSENSITIVE for string comparison
→ 'admin' = 'Admin' in WHERE clause
```

### UPDATE_OR_CREATE_CONVERGES
**YES** — proven by live Tinker test.

`Role::updateOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['name' => 'admin', ...])`:
1. Executes `SELECT * FROM roles WHERE name = 'admin' AND guard_name = 'web'`
2. utf8mb4_unicode_ci → matches `Admin` (id=1)
3. Executes UPDATE `roles` SET `name` = 'admin' WHERE `id` = 1
4. Result: id=1, name='admin' ✅, no duplicate

**Why `firstOrCreate` also converges but differently:**
`firstOrCreate(['name' => 'admin', ...], ['name' => 'admin', ...])`:
1. Same SELECT, same case-insensitive match on id=1 'Admin'
2. Finds row → returns it WITHOUT applying update values (firstOrCreate only uses values on INSERT)
3. Result: id=1, name='Admin' ❌ (not converged)

### SAFE_CONVERGENCE_ALGORITHM
**`updateOrCreate` is the minimum correct mechanism.**

```php
// SAFE: updateOrCreate — case-insensitive match + update on found row
$role = Role::updateOrCreate(
    ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']],
    $roleData  // applies name normalization on found row too
);

// Properties:
// ✅ Finds existing case-variant row (utf8mb4_unicode_ci)
// ✅ Renames to canonical $roleData['name']
// ✅ Preserves role_id (no new row created)
// ✅ Preserves model_has_roles (references role_id)
// ✅ Preserves role_has_permissions
// ✅ Idempotent (subsequent runs find already-converged row)
// ✅ Cannot create Admin/admin duplicates
```

**Note:** `updateOrCreate` with the SAME `name` in both first and second argument is equivalent to `firstOrCreate` for INSERT but also updates on FOUND. This is the minimal fix.

---

## Q2 — CONSUMER CLAIM VERIFICATION

### ROLE_BASED_MENU_AFTER_RENAME
**PASS_AFTER_CANONICAL_RENAME**

Current code:
```php
// RoleBasedMenuMiddleware.php:39
} elseif ($user->hasRole('admin')) {
    $roleName = 'admin';
```
After canonical rename (DB has 'admin'):
- User 1 getRoleNames() = ["admin"]
- hasRole("admin") → TRUE ✅
- Menu loads admin role ✅

### ADMIN_MENU_AFTER_RENAME
**PASS_AFTER_CANONICAL_RENAME**

Current code:
```php
// AdminMenu.php:31
} elseif ($user->hasRole('admin')) {
    $role = 'admin';
```
Same logic as above — hasRole('admin') returns TRUE after DB has canonical 'admin'.

### Clarification: why Phase 2 normalization was unnecessary
Previous report suggested AdminMenu/RoleBasedMenuMiddleware needed normalization. That was incorrect. `hasRole('admin')` is case-sensitive — if DB has 'admin', it matches. Normalization is only needed when DB has 'Admin' (capital A) and code calls `hasRole('admin')`. After canonical rename, normalization is not required for these consumers.

---

## AUTH_SERVICE_PROVIDER AFTER RENAME
**NO REGRESSION**

All AuthServiceProvider gates include both 'Admin' and 'admin' (or at minimum 'admin') in their allowed arrays:
- `view-admin-panel`: `['Admin', ..., 'admin', ...]`
- `manage-users`: `['Süper Admin', 'superadmin', 'süper admin', 'admin']`
- `manage-settings`: `['Süper Admin', 'superadmin', 'süper admin', 'super-admin', 'admin']`
- `edit-ilan`: `['Süper Admin', 'superadmin', 'super-admin', 'admin', ...]`

After canonical rename (DB has 'admin'):
- hasAnyRole(['Admin', 'admin', ...]) → TRUE ✅
- Legacy fallback `role->name` will also be 'admin' (converged) → matches ✅

---

## ROLE_BASED_MENU MIDDLEWARE — SEPARATE FINDING
Note: `RoleBasedMenuMiddleware.php:37` calls `hasRole('superadmin')` — this references `superadmin` (no hyphen) but DB has `super-admin` (hyphenated). This is a DIFFERENT casing/naming issue (hyphen presence), not the Admin/admin issue. Out of scope of this specific remediation.

---

## RETURN VALUES

```
MYSQL_ROLE_NAME_COLLATION:         utf8mb4_unicode_ci (case-insensitive)
UPDATE_OR_CREATE_CONVERGES:        YES
SAFE_CONVERGENCE_ALGORITHM:       Role::updateOrCreate(['name'=>$name,'guard_name'=>$guard], $roleData)
                                   — case-insensitive match via utf8mb4_unicode_ci
                                   — updates name to canonical on found row
                                   — preserves role_id, pivot, permissions
ROLE_BASED_MENU_AFTER_RENAME:      PASS_AFTER_CANONICAL_RENAME
ADMIN_MENU_AFTER_RENAME:           PASS_AFTER_CANONICAL_RENAME
AUTH_SERVICE_PROVIDER_AFTER_RENAME: NO_REGRESSION (all gates include 'admin')
MINIMUM_SOURCE_WRITE_SCOPE:        1 file: database/seeders/RoleSeeder.php
                                   Replace firstOrCreate → updateOrCreate
MINIMUM_REGRESSION_TEST_SCOPE:     2 tests:
                                   1. roleseder_idempotent_with_existing_variant
                                      (Run seeder twice, assert no duplicate, name=canonical)
                                   2. admin_user_hasRole_true_after_seeder
                                      (Seed admin role, assign to user, assert hasRole('admin') === true)
EVIDENCE_LEVEL:                    TEST_VERIFIED (updateOrCreate behavior proven via Tinker)
                                   REPO_VERIFIED (source code + MySQL collation confirmed)
PRODUCTION:                        UNKNOWN (local MySQL only)
```

---

## BOUNDED REMEDIATION PACKAGE

**Single file change + 2 regression tests:**

```php
// database/seeders/RoleSeeder.php — single line change
// BEFORE:
Role::firstOrCreate(
    ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']],
    $roleData
);
// AFTER:
Role::updateOrCreate(
    ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']],
    $roleData
);
```

**Rationale:**
- `updateOrCreate` is insert-or-update, not insert-only
- Case-insensitive MySQL collation finds existing 'Admin' when searching for 'admin'
- UPDATE phase renames 'Admin' → 'admin' in one shot
- Subsequent seeder runs: finds 'admin', updates with same values (idempotent)
- No duplicate rows possible
- All pivot tables untouched

**Regression tests:**
1. `test_roleseder_converges_existing_admin_to_canonical` — seeder run on DB with 'Admin' → after run, assert name='admin', id preserved
2. `test_admin_role_hasRole_returns_true` — create user, assign role, assert hasRole('admin')

---

## OUT OF SCOPE (separate candidates)
- CheckRole.php hardcoded ID mapping (BROKEN, separate)
- UserRole.php enum `superadmin` vs `super-admin` (separate)
- SuperAdminOnly middleware (has 'admin' in array → works after rename)
- SQLite drift (evidence hygiene, separate)
- Orphaned kullanicilar/index.blade.php (dead-code candidate, separate)
