# 02-CODE-REFERENCES.md

## Code Reference Index — Sprint 12D Discovery

### 1. Property (CANONICAL — Sprint 12C)

**File:** `app/Models/Property.php`
**Classification:** CANONICAL ✅
**Line range:** 1–120+

**Current responsibility:**
- Aggregate root for physical real estate asset
- Owns `tenant_id`, `workspace_id`, `uuid`, `idempotency_key`
- Immutable: `tkgm_id`, `ada`, `parsel`
- Invariant: requires `workspace_id` on creation
- Lifecycle state machine (DRAFT → ...)

**Tenant behavior:** Uses `BelongsToTenant` trait. All queries scoped to tenant.
**Relationship direction:** Property → PropertyWorkspace (1:1), Property → Ilan (1:many)
**Active:** YES — production code
**Reused elsewhere:** Referenced by 40+ files (controllers, services, tests)

```php
// Key invariant from Property.php:77
if (!self::$skipWorkspaceIdGuard && empty($property->workspace_id)) {
    throw new \DomainException('Property must belong to a Workspace.');
}
```

---

### 2. PropertyWorkspace (CANONICAL — Sprint 12C)

**File:** `app/Models/PropertyWorkspace.php`
**Classification:** CANONICAL ✅
**Key columns:** `property_id` (unique FK), `workspace_uuid` (unique), `intent`, `state`

**Current responsibility:**
- Operational executor for a Property
- Manages publishing intent and template
- State machine for workspace lifecycle

**Tenant behavior:** Has `tenant_id`, scoped via TenantScope
**Active:** YES

---

### 3. Kisi (REUSABLE — Existing)

**File:** `app/Models/Kisi.php`
**Classification:** REUSABLE

**Current responsibility:**
- Natural person record (ad, soyad, telefon, eposta, tc_kimlik, meslek)
- CRM entity with `kisi_tipi` enum
- `ilan_sahibi_id` FK from Ilan → Kisi (legacy ownership)

**Structural fields:**
```
kisi_tipi     — enum (Müşteri, ..., primary party type)
aktiflik_durumu — bool
tc_kimlik    — string (national ID)
user_id      — link to authenticated User
danisman_id  — assigned advisor FK
```

**MISSING for Owner use:**
- No `vergi_kimlik` / tax ID (needed for company)
- No `kurum_tipi` / legal entity type
- No `yetkili_temsilci` relationship (representative)
- No beneficial owner flag

**Tenant behavior:** Has `tenant_id` (null in schema), uses `HasActiveScope`
**Relationship direction:** Kisi is referenced by Ilan as `ilan_sahibi_id`
**Active:** YES — production
**Reused elsewhere:** 30+ files reference Kisi

---

### 4. Ilan (REUSABLE — Legacy)

**File:** `app/Models/Ilan.php`
**Classification:** LEGACY (bridged to Property)

**Current responsibility:**
- Legacy listing entity — now bridged to Property via `property_id`
- Has `ilan_sahibi_id` → Kisi (current owner reference — OVERWRITES on transfer)
- Has `workspace_id`, `property_id`
- Multiple publish channels

**Problem:** `ilan_sahibi_id` is a mutable FK — overwrites on ownership transfer. **Does not preserve history.**

```php
// Ilan has this relationship (CANONICAL violation)
public function ilanSahibi(): BelongsTo
{
    return $this->belongsTo(Kisi::class, 'ilan_sahibi_id');
}
```

**Active:** YES — production, being migrated
**Classification:** LEGACY — transitioning to Property-centric model

---

### 5. User (REUSABLE — Existing)

**File:** `app/Models/User.php`
**Classification:** REUSABLE

**Current responsibility:**
- Authenticated advisor/user
- `owner_id` in OwnerReport* tables points to User
- NOT the same as property owner (CONFLICT)

**Active:** YES

---

### 6. PortfolioDriveWorkspace (VALID SEPARATE MODEL — OBS-1)

**File:** `app/Models/PortfolioDriveWorkspace.php`
**Classification:** VALID SEPARATE MODEL ✅

**Current responsibility:**
- Google Drive workspace metadata for a listing
- `ilan_id` FK (not `property_id` — Drive predates Property canonicalization)
- `workspace_status` (creating/ready/error) — Drive API state
- `lifecycle_state` (WorkspaceState enum) — publishing lifecycle
- `ai_completion_percent`, `ai_completion_flags`
- `subfolders_json`, `metadata_json`
- Drive webhook channel management

**Tenant behavior:** Uses `TenantScope` global scope
**Relationship direction:** PortfolioDriveWorkspace → Ilan → Property
**Active:** YES — production
**Not reused elsewhere:** Standalone Drive integration entity

**OBS-1 Verdict:** VALID SEPARATE MODEL. It owns Drive-specific metadata and AI completion tracking. `WorkspaceDashboardController` is the read layer for this model. Does NOT conflict with `PropertyWorkspace` — they serve different concerns (Drive metadata vs. operational workspace).

---

### 7. WorkspaceDashboardController (VALID SEPARATE MODEL — OBS-1)

**File:** `app/Http/Controllers/Admin/WorkspaceDashboardController.php`
**Classification:** VALID SEPARATE MODEL ✅

**Current responsibility:**
- Property Digital Twin Cockpit (Sprint 4.6)
- Reads PortfolioDriveWorkspace + Ilan + WorkspaceSummaryService
- Delivers dashboard data (summary, events, health, next actions)
- Route: `GET /admin/workspace/{id}`

**Active:** YES
**Not conflicting:** Operates on PortfolioDriveWorkspace, not PropertyWorkspace

---

### 8. Site / SiteApartman (LEGACY)

**File:** `app/Models/Site.php`, `app/Models/SiteApartman.php`
**Classification:** LEGACY / DEPRECATED

**Current responsibility:**
- Compound/building representation
- `Site`: name, blok_adi, is_active
- `SiteApartman`: total apartments, floors, manager info, dues

**Problem:** Not referenced by Property. Ad-hoc structures.
**Tenant behavior:** Uses `HasCountryScope`
**Active:** Partially — data exists, not actively extended

---

### 9. OwnerReport* Models (CONFLICTING)

**Files:** `app/Models/OwnerReportMetric.php`, `OwnerReportExport.php`, `OwnerReportRow.php`
**Classification:** CONFLICTING

**Current responsibility:**
- Financial metrics per owner (advisor) per listing
- `owner_id` → `users.id` (advisor, NOT property owner)

**CONFLICT:** "Owner" here means report owner (advisor), not property owner.
**Action:** No rename needed — different domain context. Document this clearly.

---

### 10. PropertyReservation (REUSABLE)

**File:** `app/Models/PropertyReservation.php`
**Classification:** REUSABLE

**Current responsibility:**
- Rental booking/block on a Property
- Has `property_id` FK

**Active:** YES

---

### 11. PropertyEventApiController (IRRELEVANT)

**File:** `app/Http/Controllers/Admin/PropertyEventApiController.php`
**Classification:** DEPRECATED / IRRELEVANT

**Current responsibility:**
- Returns reservation events for calendar
- Does NOT deal with ownership events

---

### 12. References to `owner_id` in Ilan

**Search results:**
```
app/Models/Ilan.php        — ilan_sahibi_id (Kisi FK, mutable)
app/Models/OwnerReport*.php — owner_id (User FK, advisor)
```

**No `owner_id` on Property, PropertyWorkspace, or any canonical model.**
This is CORRECT — ownership must be a separate historical record.

---

### 13. References to site/project/block

```
app/Models/Ilan.php        — proje_id, ada_no, parsel_no (legacy, free strings)
app/Models/Site.php         — blok_adi (legacy)
app/Models/SiteApartman.php — kat_sayisi (building-level)
app/Models/Property.php     — kat_sayisi, bulundugu_kat, ada, parsel (physical specs)
```

**No structured Site/Block/Building/Entrance/Floor/Apartment hierarchy exists in canonical Property.**
These are MISSING.

---

### 14. References to key/access/custody

**NONE found in models.**
This is MISSING — to be designed in Sprint 12D.

---

### 15. References to document/contract/title_deed

**NONE found in models related to Property.**
Document storage for Property is MISSING.

---

### Summary Classification Table

| Item | Classification | Action |
|------|---------------|--------|
| `Property` | CANONICAL | Extend only |
| `PropertyWorkspace` | CANONICAL | Extend only |
| `Kisi` | REUSABLE | Use for natural person Party |
| `Ilan` | LEGACY | Migrate owner to PropertyOwnership |
| `User` | REUSABLE | Use for authenticated actor |
| `PortfolioDriveWorkspace` | VALID SEPARATE MODEL | Keep as-is, do not refactor |
| `WorkspaceDashboardController` | VALID SEPARATE MODEL | Keep as-is |
| `Site` / `SiteApartman` | LEGACY | Do not extend |
| `OwnerReport*` | CONFLICTING | Document, no rename |
| `PropertyReservation` | REUSABLE | Use existing |
| `PropertyEventApiController` | DEPRECATED | Ignore |
| `PropertyOwnership` | MISSING | Design in Sprint 12D |
| `PropertyRepresentative` | MISSING | Design in Sprint 12D |
| `PropertyAccessAsset` | MISSING | Design in Sprint 12D |
| `PropertyDocument` | MISSING | Design in Sprint 12D |
| Legal entity / Company | MISSING | Determine in Party model review |

---

*References indexed: 2026-07-17*
