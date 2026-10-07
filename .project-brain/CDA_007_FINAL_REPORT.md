# CDA-007 FINAL FORENSIC REPORT
## Tenant Active-State Contract Drift Discovery

**TASK_ID:** CDA_007_TENANT_ACTIVE_STATE_CONTRACT_DISCOVERY_01  
**STATUS:** ACTIVE → REMEDIATION PENDING  
**BASELINE:** caf20738deda4725413241b457f684c30b1fa355  
**EVIDENCE_LEVEL:** TEST_VERIFIED  
**DATE:** 2026-10-04

---

## EXECUTIVE SUMMARY

| Metric | Finding |
|--------|---------|
| **Canonical Write Model** | `App\Models\SaaS\Tenant` |
| **Write Field** | `status` (VARCHAR, default='active') |
| **Canonical Read Query** | HuntOpportunitiesCommand |
| **Primary Read Field** | `aktiflik_durumu` (VARCHAR, default='active') |
| **Root Cause** | MIGRATION_INCOMPLETE + MODEL_CONTRACT_DRIFT |
| **Current Risk** | MASKED by DB default + legacy fallback |
| **Write/Read Mismatch** | REPRODUCIBLE (theoretical) |

---

## 1. SCHEMA STATE

### Local SQLite Schema (VERIFIED)
```
Column             Type         Default     Nullable
-------------------------------------------------
status             varchar      'active'    NOT NULL
durum              varchar      'active'    NOT NULL
aktiflik_durumu    varchar      'active'    NOT NULL
is_active          tinyint(1)  1           NOT NULL
```

### Production Schema (UNKNOWN — needs VPS access)
Production'da 3 veya 4 kolon olup olmadığı doğrulanmadı.

---

## 2. WRITE AUTHORITY CONTRACT

### SaaS\Tenant Model
```php
// app/Models/SaaS/Tenant.php
protected $fillable = ['uuid', 'name', 'domain', 'status'];
```

**PROBLEM:** `aktiflik_durumu` ve `durum` fillable'da **YOK**.

### TenantFactory
```php
// database/factories/SaaS/TenantFactory.php
'status' => 'active',  // Writes ONLY to status column
```

### TenantBaselineSeeder
```php
// database/seeders/TenantBaselineSeeder.php
'status' => 'active',  // Writes ONLY to status column
```

### BootstrapProductionPilotCommand
```php
// app/Console/Commands/BootstrapProductionPilotCommand.php
// Uses firstOrCreate with domain matching
// aktiflik_durumu NOT explicitly set
```

---

## 3. READ AUTHORITY CONTRACT

### HuntOpportunitiesCommand (lines 41-46)
```php
$tenants = Tenant::where(function ($query) {
    $query->where('aktiflik_durumu', 1)           // INT type
        ->orWhere('aktiflik_durumu', 'active')     // STRING
        ->orWhere('aktiflik_durumu', 'aktif')      // TURKISH
        ->orWhere('status', 'active');             // LEGACY FALLBACK
})->orderBy('id')->get();
```

**ANALYSIS:**
- Primary filter: `aktiflik_durumu IN (1, 'active', 'aktif')`
- Fallback: `status = 'active'` (LEGACY COVERAGE)
- The fallback is LUCKY COINCIDENCE, not intentional fix

---

## 4. MIGRATION CHRONOLOGY

| Date | Migration | Action |
|------|-----------|--------|
| Original | tenants table | Created with `status` column |
| 2026-05-06 | 2026_05_06_173500 | Added `durum` column (default='active') |
| 2026-05-17 | 2026_05_17_194127 | Added `aktiflik_durumu` (default='active'), copied status→aktiflik_durumu |
| 2026-09-28 | 2026_09_28_180340 | Added `deleted_at` (SoftDeletes) |

**KEY FINDING:** Migration 2026_05_17 created `aktiflik_durumu` as a **COPY** of `status`, then set both with default='active'. The `status` column was **NOT removed** — intentional for backward compatibility but deferred cleanup.

---

## 5. REPRODUCTION RESULTS

### TEST: Proof aktiflik_durumu NOT in fillable
```
RESULT: ✅ PASS
SaaS\Tenant::$fillable = ['uuid', 'name', 'domain', 'status']
aktiflik_durumu: NOT IN FILLABLE
```

### TEST: Direct INSERT respects explicit values
```
RESULT: ✅ PASS
INSERT aktiflik_durumu='inactive' → Stored as 'inactive'
INSERT status='active' → Stored as 'active'
```

### TEST: Write/Read mismatch scenario
```
SCENARIO:
1. Migration or manual process sets aktiflik_durumu='inactive'
2. SaaS\Tenant model writes status='active'
3. HuntOpportunitiesCommand queries aktiflik_durumu='active'

RESULT: Tenant INVISIBLE to HuntOpportunities (without fallback)
```

### TEST: HuntOpportunities has legacy fallback
```
RESULT: ✅ PASS
Tenant found via OR status='active' clause
CURRENT MITIGATION: Works by accident
```

---

## 6. ROOT CAUSE ANALYSIS

### Classification
```
ROOT_CAUSE: MIGRATION_INCOMPLETE + MODEL_CONTRACT_DRIFT
```

### Causal Chain
```
1. Sprint 2 migration added aktiflik_durumu as Context7 standard
2. Data copied: status → aktiflik_durumu (forward compatibility)
3. status column NOT removed (backward compatibility)
4. SaaS\Tenant model NOT updated to write aktiflik_durumu
5. HuntOpportunitiesCommand reads aktiflik_durumu (Context7)
6. HuntOpportunities has fallback to status (masks the gap)
7. DB default aktiflik_durumu='active' (masks the gap further)
```

### Why it's currently masked
1. **DB Default:** `aktiflik_durumu` has default='active', so even without model writing it, it gets 'active'
2. **Legacy Fallback:** HuntOpportunities includes `OR status='active'`
3. **No active corruption:** No production path actively writing different values to these columns

---

## 7. VALUE DOMAIN

| Field | Accepted Values | Type |
|-------|-----------------|------|
| `status` | 'active', 'inactive' (string) | VARCHAR |
| `durum` | 'active' (from seeder) | VARCHAR |
| `aktiflik_durumu` | 1 (int), 'active', 'aktif' (string) | VARCHAR |
| `is_active` | 0, 1 | TINYINT |

---

## 8. BOUNDED REPRODUCTION

### Scenario 1: Normal SaaS\Tenant::create()
```php
Tenant::create(['name' => 'X', 'domain' => 'x.test', 'status' => 'active']);
```
- Writes: `status='active'`
- aktiflik_durumu: DB default='active' → VISIBLE to HuntOpportunities
- **RESULT:** WORKS (by luck)

### Scenario 2: Migration/manual process
```sql
UPDATE tenants SET aktiflik_durumu='inactive' WHERE id=1;
```
- status: 'active'
- aktiflik_durumu: 'inactive'
- HuntOpportunities: MISSES tenant (without fallback)
- **RESULT:** WOULD FAIL (but fallback saves it)

### Scenario 3: Strict aktiflik_durumu query
```php
Tenant::where('aktiflik_durumu', 'active')->get();
```
- Would MISS tenants with `aktiflik_durumu='inactive'` even if `status='active'`
- **RESULT:** DEPENDS on data state

---

## 9. RECOMMENDED FIX

### Option A: Model-Centric Fix (SAFE)
**Changes:**
1. `SaaS\Tenant::$fillable` → add `aktiflik_durumu`
2. `SaaS\Tenant` → add mutator to sync `status` → `aktiflik_durumu`
3. Factory/Seeder → update to write `aktiflik_durumu`

**Migration Required:** NO  
**Data Backfill Required:** NO  
**Risk:** LOW

### Option B: Query-Centric Fix (BOUNDED)
**Changes:**
1. HuntOpportunitiesCommand → use ONLY `aktiflik_durumu` filter
2. Remove `OR status='active'` fallback (intentional behavior)
3. Add test to verify write/read contract

**Migration Required:** NO  
**Data Backfill Required:** NO  
**Risk:** MEDIUM (may break if data inconsistency exists)

### Option C: Full Cleanup (CANONICAL)
**Changes:**
1. Migration: DROP `status`, `durum`, `is_active` columns
2. Model: `$fillable = ['uuid', 'name', 'domain', 'aktiflik_durumu']`
3. Factory/Seeder: update to write `aktiflik_durumu`
4. HuntOpportunitiesCommand: query `aktiflik_durumu` only
5. Data backfill: `UPDATE tenants SET aktiflik_durumu='active' WHERE aktiflik_durumu IS NULL`

**Migration Required:** YES  
**Data Backfill Required:** YES  
**Risk:** HIGH (production data impact)

---

## 10. RECOMMENDED BOUNDED FIX

**CHOICE:** Option A (Model-Centric) + targeted Query cleanup

**Rationale:**
- Minimal risk
- Maintains backward compatibility
- Aligns model with intended Context7 standard
- HuntOpportunities fallback can stay (defensive)

**Implementation:**
```php
// 1. SaaS\Tenant.php
protected $fillable = ['uuid', 'name', 'domain', 'status', 'aktiflik_durumu'];

// 2. Add boot method to sync
protected static function booted(): void
{
    static::saving(function ($tenant) {
        if ($tenant->isDirty('status') && !$tenant->isDirty('aktiflik_durumu')) {
            $tenant->aktiflik_durumu = $tenant->status;
        }
    });
}

// 3. Factory update
'status' => 'active',
'aktiflik_durumu' => 'active',

// 4. Seeder update
'status' => 'active',
'aktiflik_durumu' => 'active',
```

---

## 11. TEST CASES FOR FIX

```php
/** @test */
public function tenant_model_syncs_status_to_aktiflik_durumu(): void
{
    $tenant = Tenant::create([
        'name' => 'Sync Test',
        'domain' => 'sync.test',
        'status' => 'active',
    ]);
    
    $this->assertEquals('active', $tenant->aktiflik_durumu);
    $this->assertEquals('active', DB::table('tenants')->where('id', $tenant->id)->value('aktiflik_durumu'));
}

/** @test */
public function hunt_opportunities_finds_syncd_tenant(): void
{
    $tenant = Tenant::create([
        'name' => 'Hunt Test',
        'domain' => 'hunt.test',
        'status' => 'active',
    ]);
    
    $found = Tenant::where(function ($q) {
        $q->where('aktiflik_durumu', 1)
          ->orWhere('aktiflik_durumu', 'active')
          ->orWhere('aktiflik_durumu', 'aktif')
          ->orWhere('status', 'active');
    })->where('id', $tenant->id)->exists();
    
    $this->assertTrue($found);
}
```

---

## 12. FILES TO MODIFY

| File | Change |
|------|--------|
| `app/Models/SaaS/Tenant.php` | Add aktiflik_durumu to fillable, add syncing logic |
| `database/factories/SaaS/TenantFactory.php` | Add aktiflik_durumu |
| `database/seeders/TenantBaselineSeeder.php` | Add aktiflik_durumu |
| `tests/Unit/CDA/CDA007WriteReadMismatchReproductionTest.php` | Add fix verification tests |

---

## EVIDENCE CHAIN

1. ✅ SCHEMA: 4 active-state columns confirmed (SQLite)
2. ✅ MODEL: aktiflik_durumu NOT in fillable (TEST_VERIFIED)
3. ✅ FACTORY: Writes status only (REPO_VERIFIED)
4. ✅ SEEDER: Writes status only (REPO_VERIFIED)
5. ✅ QUERY: HuntOpportunities reads aktiflik_durumu (REPO_VERIFIED)
6. ✅ QUERY: Has legacy fallback to status (REPO_VERIFIED)
7. ✅ REPRODUCTION: Direct INSERT respects explicit values (TEST_VERIFIED)
8. ⏳ PRODUCTION: Schema state UNKNOWN

---

## NEXT STEPS

1. **Ayhan onayı:** Remediation option seçimi
2. **Implementation:** Option A veya Option C
3. **Production verification:** Schema ve data durumu kontrolü
4. **Regression tests:** Mevcut testlerin geçtiğinden emin ol
5. **Deploy:** Migration + code deployment

---

## AUTHORITY STATEMENT

**Canonical Write Authority:** `App\Models\SaaS\Tenant` (via `$fillable`)

**Canonical Read Authority:** `HuntOpportunitiesCommand` (primary: `aktiflik_durumu`)

**Gap:** Model writes `status`, reader primarily reads `aktiflik_durumu`

**Current State:** MASKED (DB default + legacy fallback)

**Fix Required:** YES (to prevent future silent failures)

---

*Report Generated: 2026-10-04*  
*Forensic Researcher: Cline (Claude Opus 5.5)*  
*Verification: All critical tests PASS*
