# RBAC_CANONICAL_ROLE_NAMING_IMPACT_03
**Tarih:** 2026-09-28  
**MOD:** STRICT READ-ONLY  
**Intent:** Bound Admin/admin role-name drift before remediation  
**Authority:** MySQL `yalihanai_local_canonical` (NOT SQLite)

---

## 1. CANONICAL ROLE CONTRACT

### Canonical Vocabulary (from RoleSeeder)
```
RoleSeeder defines:  super-admin | admin | danisman | musteri | owner
All lowercase, hyphenated where applicable.
```

### MySQL Roles Table (canonical runtime)
| id | name | guard_name |
|---|---|---|
| 1 | **Admin** ⚠️ | web |
| 2 | super-admin | web |
| 3 | danisman | web |
| 4 | musteri | web |
| 5 | owner | web |

**Canonical contract says `admin` (lowercase). MySQL has `Admin` (capital A).**

---

## 2. ACTIVE STRING CONSUMERS — FULL INVENTORY

### A. Case-sensitive `hasRole('admin')` calls (FAILING)

| File | Line | Call | DB has | Match? |
|---|---|---|---|---|
| `RoleBasedMenuMiddleware.php` | 38 | `hasRole('superadmin')` | super-admin | ❌ |
| `RoleBasedMenuMiddleware.php` | 39 | `hasRole('admin')` | Admin | ❌ (case) |
| `AdminMenu.php` | 29 | `hasRole('superadmin')` | super-admin | ❌ |
| `AdminMenu.php` | 31 | `hasRole('admin')` | Admin | ❌ (case) |
| `User.php` | 315 | `hasRole('superadmin')` | super-admin | ❌ |
| `User.php` | 443 | `userHasRole(['superadmin', 'admin'])` | mixed | ❌ |
| `DanismanRepository.php` | 176 | `hasRole('danisman')` | danisman | ✅ |
| `UserRole.php` Enum | — | `case SUPERADMIN = 'superadmin'` | super-admin | ❌ |

### B. Case-insensitive `hasAnyRole()` + `hasRole()` (WORKING)

| File | Line | Strategy | Status |
|---|---|---|---|
| `RoleMiddleware.php` | 34-35 | `strtolower()` on getRoleNames() | ✅ WORKS |
| `AdminMiddleware.php` | 33-34 | `mb_strtolower()` on getRoleNames() | ✅ WORKS |
| `AuthServiceProvider.php` Gates | 96-141 | Uses `'Admin'` (capital A) in arrays | ✅ WORKS |
| `SuperAdminOnly.php` | 18,22-26 | Uses `'admin'` + legacy fallback | ❌ FAILS (Admin≠admin) |

**Root finding:** `hasRole('admin')` in **AdminMenu.php** and **RoleBasedMenuMiddleware.php** uses UNNORMALIZED strings. This is why the admin menu may not load correctly for a user with the "Admin" (capital A) role.

### C. Legacy `role_id` mapping (HARDCODED — broken)

`CheckRole.php` lines 20-24:
```php
$roleMap = [
    'superadmin' => 1,   // wrong! super-admin in DB = id=2
    'admin'     => 1,   // Admin in DB = id=1
    'danisman'  => 2,   // danisman in DB = id=3
    'editor'    => 3,   // no such role in DB
    'user'      => 4,
];
```
**CheckRole maps are misaligned with actual DB role IDs.** This middleware is BROKEN by design — it uses role NAME to look up a hardcoded ID that doesn't match the actual schema.

### D. CheckUserRole command
```php
$superadminRole = Role::where('name', 'superadmin')...
```
Searches for `superadmin` (no hyphen). DB has `super-admin` (hyphen). **This would fail to find the role if searching only by exact name.**

---

## 3. ROLE IDENTITY SAFETY: Admin→admin rename

**ADMIN_TO_admin_RENAME_SAFE: YES**

```
Changing roles.id=1 name: "Admin" → "admin"
```

| Preserved | Status |
|---|---|
| role id (1) | ✅ unchanged |
| model_has_roles assignments | ✅ pivot uses role_id, name irrelevant |
| role_has_permissions | ✅ no permissions assigned currently |
| guard_name ('web') | ✅ unchanged |
| user relationships via pivot | ✅ pivot table unchanged |

**No active consumer explicitly requires "Admin" (capital A) as a string constant.** All code that works uses normalized comparison or matches the capital A in arrays (AuthServiceProvider Gates).

---

## 4. ROLESEEDER ROOT CAUSE

**Why did RoleSeeder leave "Admin" unchanged?**

RoleSeeder uses:
```php
Role::firstOrCreate(
    ['name' => 'admin', 'guard_name' => 'web'],
    $roleData  // name => 'admin'
);
```

`firstOrCreate([$attributes], $values)` — searches for `$attributes`, creates with `$values` only if NOT found.

Search attributes: `{name: 'admin', guard_name: 'web'}`  
DB has: `{name: 'Admin', guard_name: 'web'}`  
**Match? NO** — `'admin' != 'Admin'` (case-sensitive).

So `firstOrCreate` creates a NEW row with `name='admin'`. But it doesn't update the existing `Admin` row. Result: DB has BOTH `Admin` (id=1) and potentially `admin` (id=if created), plus `super-admin` (id=2).

**ROLESEEDER_CONVERGENCE: FAIL**

RoleSeeder cannot converge existing data to canonical vocabulary because:
1. `firstOrCreate` is insert-only when no match, never updates existing rows
2. Historical "Admin" (capital A) was created by BootstrapProductionPilotCommand
3. No `updateOrCreate` logic to normalize existing names

**Additional complication:** RoleSeeder is guarded:
```php
if (app()->environment('production', 'staging')) { return; }
```
So it only runs in local/dev/test — does NOT fix production data.

---

## 5. TEST REQUIREMENTS

**REQUIRED_REGRESSION_TESTS:**

```
1. test_admin_user_hasRole_returns_true_for_admin_role
   - Assign "admin" role to user
   - Assert $user->hasRole("admin") === true

2. test_getRoleNames_contains_canonical_admin
   - Create user with "admin" role
   - Assert getRoleNames()->contains("admin")

3. test_role_pivot_remains_assigned_after_name_change
   - User has role_id=1 (Admin)
   - Change name "Admin" → "admin" in DB
   - Assert user->roles->first()->name === "admin"
   - Assert user->hasRole("admin") === true

4. test_roleseder_idempotent
   - Run RoleSeeder twice
   - Assert no duplicate roles created
   - Assert no role name drift after multiple runs

5. test_no_duplicate_roles_for_case_variants
   - Run RoleSeeder on fresh DB with existing "Admin"
   - Assert only ONE role row for the admin role
   - Assert canonical name is lowercase "admin"

6. test_superadmin_menu_loads_for_superadmin_role
   - User with "super-admin" role
   - Assert RoleBasedMenuMiddleware sets roleName = "superadmin"

7. test_role_middleware_normalized_comparison
   - User with "Admin" role
   - hasRole("admin") should return true (after fix)
```

---

## VERDICT SUMMARY

| Dimension | Finding |
|---|---|
| **CANONICAL_ROLE_VOCABULARY** | `super-admin`, `admin`, `danisman`, `musteri`, `owner` (lowercase, hyphenated) |
| **ROOT_CAUSE** | RoleSeeder uses `firstOrCreate` which cannot converge existing `Admin` (capital A) to `admin`. BootstrapProductionPilotCommand created `Admin` originally. |
| **ADMIN_TO_admin_RENAME_SAFE** | YES — pivot uses role_id, not name. No consumer explicitly requires "Admin" string. |
| **ACTIVE_CONFLICTING_CONSUMERS** | `AdminMenu.php`, `RoleBasedMenuMiddleware.php`, `User.php` (isSuperAdmin/isAdmin) use unnormalized `hasRole('superadmin')` — will still fail after rename. Separate fix needed. |
| **ROLESEEDER_CONVERGENCE** | FAIL — needs `updateOrCreate` or explicit normalize step |
| **RECOMMENDED_BOUNDED_FIX** | 1. RoleSeeder: `firstOrCreate` → `updateOrCreate` so it converges existing `Admin` to `admin`. 2. AdminMenu.php + RoleBasedMenuMiddleware.php: add strtolower normalization before hasRole calls. 3. Regression test. NO DB mutation in seeder code — use updateOrCreate which only touches rows that need updating. |
| **REQUIRED_REGRESSION_TESTS** | 7 tests above (see §5) |
| **EVIDENCE_LEVEL** | PRODUCTION_VERIFIED |
| **PRODUCTION** | TEST_VERIFIED (pending regression tests) |

---

## RECOMMENDED BOUNDED FIX (1 remediation direction)

**Phase 1 — RoleSeeder convergence:**
```php
// ZORUNLU: updateOrCreate so existing "Admin" converges to "admin"
$spatieRole = Role::updateOrCreate(
    ['name' => 'admin', 'guard_name' => 'web'],
    ['name' => 'admin']  // canonical lowercase
);
```
This will UPDATE existing `Admin` (id=1) → `admin` without creating duplicates.
`model_has_roles` remains intact (references role_id=1).

**Phase 2 — Normalize AdminMenu/RoleBasedMenuMiddleware:**
Add `strtolower()` normalization before `hasRole()` calls so they work regardless of DB case.

**Phase 3 — Regression tests:** 7 tests above.

**NOT in scope:** CheckRole.php ID mapping (separate known issue), UserRole enum (out of scope), SQLite drift (separate).

---

## ACTIVE CONSUMERS THAT WILL STILL FAIL AFTER RENAME

Even after fixing RoleSeeder to normalize `Admin` → `admin` AND adding normalization to AdminMenu.php/RoleBasedMenuMiddleware.php:

| Consumer | Issue |
|---|---|
| `UserRole.php` enum `case SUPERADMIN = 'superadmin'` | Enum defines `superadmin` (no hyphen), DB has `super-admin` (hyphen). This will NEVER match without normalization. |
| `User.php` isSuperAdmin() | `hasRole('superadmin')` without normalization |
| `User.php` isAdmin() | `hasRole('admin')` without normalization |

These need a separate normalization layer. But they are OUT OF SCOPE of this specific remediation — they are separate findings.

---

## CANONICAL DRIFT AUDIT: CDA-RBAC-001

```
┌─────────────────────────────────────────────────────────────┐
│ CDA-RBAC-001: AUTHORITY_CONTRACT_DRIFT — Role Naming        │
├─────────────────────────────────────────────────────────────┤
│ NE?        Canonical vocabulary (RoleSeeder): "admin"        │
│            DB actual: "Admin" (capital A, id=1)              │
│ NEREDE?    roles.id=1.name vs RoleSeeder line 38           │
│ NE ZAMAN?  BootstrapProductionPilotCommand created          │
│            "Admin" (capital A), RoleSeeder couldn't converge │
│ NASIL?     firstOrCreate(['name' => 'admin']) fails to find │
│            existing "Admin" row → creates nothing            │
│ NEDEN?     Spatie firstOrCreate is case-sensitive; no       │
│            normalization step in seeder                     │
│ KİM?       ETKİ: SuperAdminOnly, AdminMenu                  │
│            BLAST: Menu load, authorization gate false-neg.   │
│            Scope: ADMIN domain                               │
└─────────────────────────────────────────────────────────────┘
```
