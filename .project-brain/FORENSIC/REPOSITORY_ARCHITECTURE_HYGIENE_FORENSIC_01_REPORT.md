# 🔬 REPOSITORY_ARCHITECTURE_HYGIENE_FORENSIC_01
## Final Forensic Report — READ-ONLY

```
TASK: REPOSITORY_ARCHITECTURE_HYGIENE_FORENSIC_01
MODE: STRICT READ-ONLY
CONDUCTED: 2026-09-27
BASELINE: 836b1d4 / release-candidate/RC2
DO NOT WRITE TO: EVIDENCE_INDEX / PROJECT_STATE / DECISION_LOG
```

---

## 1. CURRENT ARCHITECTURE MAP

### Architectural Roots

```
app/
├── Models/              → Canonical model registry (App\Models)
│   ├── BaseModel.php    → FOUNDATION (minimal, no SoftDeletes)
│   └── [150+ domain models]
├── Modules/             → Bounded-context modules
│   ├── BaseModule/Models/
│   │   └── BaseModel.php → MODULE_BASE (abstract, SoftDeletes, $dates, $with)
│   ├── Auth/
│   ├── Crm/Models/Kisi.php  → CRM Kisi (extends Model, no traits)
│   ├── Emlak/Models/   → Feature, IlanFotografi, Proje (module context)
│   ├── Finans/Models/Komisyon.php → Finance domain
│   └── [others]
├── Domain/              → CQRS/DDD bounded contexts
│   ├── Ilan/
│   │   ├── Actions/StoreIlanAction.php → CQRS write
│   │   ├── Actions/UpdateIlanAction.php
│   │   ├── Services/Wizard*.php
│   │   ├── Repositories/IlanReadRepository.php
│   │   └── IlanDomainYonetici.php → BoundedContextContract
│   ├── Kisi/
│   │   ├── Repositories/KisiReadRepository.php
│   │   ├── Projections/KisiProjectionHandler.php
│   │   └── KisiDomainYonetici.php
│   ├── CQRS/            → Messaging, Aggregates
│   └── Core/            → Contracts, Security
├── Services/            → Application services
│   ├── Ilan/IlanCrudService.php → CANONICAL Ilan write authority
│   ├── Kisi/BulkKisiService.php
│   ├── ReservationService.php
│   ├── Calendar/IlanReservationService.php
│   └── Finance/FinancialLedgerService.php
├── Http/Controllers/
│   ├── Admin/           → 150 controllers
│   ├── Api/             → 81 controllers
│   └── Owner/           → 10 controllers
└── Traits/
    ├── BelongsToTenant.php   → Tenant resolution via TenantContextService
    ├── HasCountryScope.php
    └── HasActiveScope.php
```

---

## 2. DOMAIN AUTHORITY MATRIX

```
Domain      | Canonical Model            | Write Authority              | Tenant Mechanism          | Consumers           | Evidence Level
------------|--------------------------|------------------------------|--------------------------|---------------------|---------------
Ilan        | App\Models\Ilan           | IlanCrudService              | BelongsToTenant + TenantScope | Admin, Owner, API   | REPO_VERIFIED
            | (app/Models/Ilan)         | (app/Services/Ilan/)        | (TenantContextService→SaaS\Tenant) | Wizard, Publish | TEST_VERIFIED
Rezervasyon | App\Models\PropertyReservation | ReservationService      | BelongsToTenant          | ChannelManager, API | REPO_VERIFIED
            | (property_reservations tablosu) | + IlanReservationService |                          | Owner, Calendar     |
CRM/Kisi    | App\Models\Kisi           | KisiService (Modules/Crm)    | BelongsToTenant + TenantScope | Admin, API      | REPO_VERIFIED ⚠️SPLIT
            | (kisiler tablosu)        | + BulkKisiService           |                          | Bulk, Matching      |
Finans      | App\Models\LedgerEntry    | FinancialLedgerService      | BelongsToTenant          | Jobs, API           | REPO_VERIFIED
            | (Finance domain)         | + KomisyonService (Modules/Finans) |                  | Komisyon, Dashboard |
Tenant      | App\Models\SaaS\Tenant    | TenantContextService        | Self (root boundary)     | All domains         | REPO_VERIFIED
            | (tenant_context_service) |                              |                          |                     |
            | App\Models\Tenant (ayrı)  | — (sadece model, yazma yok) | BelongsToTenant → SaaS\Tenant | —              | LEGACY_REF
Emlak/Proje | Modules/Emlak/Models/Proje | ProjeController (Modules) | BelongsToTenant          | Ilan (FK)           | REPO_VERIFIED ⚠️SPLIT
            | (emlak_projeleri tablosu) |                             |                          | IlanForm, Wizard    |
            | App\Models\Proje (ayrı)  | — (farklı tablo: projeler)  |                          | —                   | LEGACY_REF (diff table)
```

---

## 3. TOP REAL FINDINGS

---

### FINDING_01 (RECLASSIFIED — HIGH VALUE CANDIDATE)
```
classification:    HIGH_VALUE_FINDING_CANDIDATE
severity:          HIGH (pending runtime reproduction)
affected_domain:   CRM/Kisi

EVIDENCE UPGRADE REQUIRED:
  "KisiService creates without tenant_id" is REPO_VERIFIED (code inspection).
  BUT: runtime tenant bypass is not TEST_VERIFIED.
  Alternative mechanisms may enforce tenant elsewhere:
    - Global scope
    - Observer
    - Service-layer caller
    - BaseModel boot hook
    - Database trigger
    - Caller-level tenant enforcement

root_evidence:
  - app/Models/Kisi.php → extends BaseModel, has TenantScope, BelongsToTenant, HasCountryScope
  - app/Modules/Crm/Models/Kisi.php → extends Model (NOT BaseModel), no tenant scope, no enums
  - BOTH point to SAME TABLE: 'kisiler'
  - KisiService creates Kisi: Modules/Crm/Services/KisiService.php:55 → Kisi::create($data)
  - BulkKisiService creates Kisi: with tenant_id auto-boot via BelongsToTenant trait

runtime_reachability:
  - KisiController uses BOTH services (lines 390, 575-586)
  - KisiService → Kisi::create() (line 55)
  - BulkKisiService → Kisi::create() with tenant enforcement

NEEDED FOR CONFIRMATION:
  1. Does KisiService receive a pre-set tenant context from its callers?
  2. Does the controller middleware set tenant context before calling KisiService?
  3. Does KisiService's caller (e.g. KisiController) wrap in a tenant-aware transaction?
  4. Or does KisiService genuinely write without tenant enforcement?

canonical_authority:
  - App\Models\Kisi is canonical (richer, with tenant scope)
  - Modules\Crm\Models\Kisi is a thinner model

bounded_fixability: MEDIUM (after confirmation)

NEXT STEP: Runtime reproduction test required before fix assignment.
  Write a test that creates a Kisi via KisiService in Tenant A context,
  then verify tenant_id = Tenant A (not null, not Tenant B).

Evidence_Type: RUNTIME_BEHAVIOR_CONFLICT (CANDIDATE)
Evidence_Level: REPO_VERIFIED (code) → TEST_VERIFIED (pending)
```

---

### FINDING_02 (RECLASSIFIED)
```
classification:    INTENTIONAL_BOUNDED_CONTEXT_SPLIT / STALE_FINDING_CANDIDATE
severity:          LOW
affected_domain:   Emlak/Proje

RECLASSIFICATION REASON:
  ADR #006 already established two bounded contexts:
    - Emlak Proje → emlak_projeleri (Modules/Emlak)
    - Team Proje → projeler (App/Models)
  These are DIFFERENT TABLES for different bounded contexts.
  Not a duplicate for the same concept.

root_evidence:
  - app/Modules/Emlak/Models/Proje.php → table='emlak_projeleri'
  - app/Models/Proje.php → table='projeler'
  - DIFFERENT TABLES confirmed
  - app/Models/Ilan.php:936 → $this->belongsTo(\App\Modules\Emlak\Models\Proje::class, 'proje_id')
  - ADR #006 canonical evidence: Emlak Proje vs Team Proje separation

runtime_reachability:
  - Modules version: Ilan FK, ProjeController, Feature translations (active)
  - App version: requires production verification before orphan declaration

DO NOT REOPEN ADR #006 based on this finding.
```

---

### FINDING_03 (RECLASSIFIED)
```
classification:    INTENTIONAL_LEGACY_TRANSITION
severity:          LOW
affected_domain:   Finance

This is not a REAL_FINDING.
app/Models/Finance/LedgerEntry.php is marked deprecated and is actively migrating.
No runtime defect. Intentional transition path.
```

---

### FINDING_04 (RECLASSIFIED)
```
classification:    DUPLICATE_AUTHORITY_CANDIDATE
severity:          LOW (pending runtime verification)
affected_domain:   Reservation

RECLASSIFICATION REASON:
  e8b9eff commit closed reservation mutation boundary with tenant-aware modify/cancel.
  Two Eloquent models pointing to same table is NOT automatic defect.
  One could be a read model / compatibility layer.
  Need to verify if active mutation authority is genuinely split.

root_evidence:
  - app/Models/IlanReservation.php → extends BaseModel, table='property_reservations'
  - app/Models/PropertyReservation.php → extends BaseModel, table='property_reservations'
  - SAME TABLE confirmed
  - Write paths:
    • IlanReservationService: IlanReservation::create() [calendar sync]
    • ReservationService: PropertyReservation::create() [general reservation]
    • BookingReservationIngestService: PropertyReservation::create() [channel manager]

NEXT STEP:
  Determine whether IlanReservationService mutations are truly independent authority
  or if they converge on the same canonical write path in production flow.
  Do NOT treat as confirmed defect without runtime verification.
```

---

### FINDING_05 (RECLASSIFIED)
```
classification:    LEGACY_BASE_CLASS_DIVERGENCE
severity:          LOW

This is not a REAL_FINDING in the business invariant sense.
Legacy base class used by only 2-3 models. Low risk.
Bounded cleanup candidate, not a production defect.
```

---

## 4. ARCHITECTURAL DEBT CANDIDATES (revised)

### DEBT_01: Modules/Emlak IlanFotografi — same table, different base
```
Both app/Modules/Emlak/Models/IlanFotografi.php and app/Models/IlanFotografi.php
point to 'ilan_fotograflari'. Only difference: BaseModule SoftDeletes vs HasCountryScope.
Currently no active conflict because Modules version is self-contained (only used by
Modules/Emlak controllers). Low risk, but duplicate model.
```

### DEBT_02: Two Tenant models
```
app/Models/Tenant → BelongsToTenant trait, used by domain models
app/Models/SaaS/Tenant → direct Model+SoftDeletes, used by TenantContextService
These serve different purposes (domain vs context service) but share the name.
No active conflict. Monitor only.
```

### DEBT_03: Modules/Finans as isolated bounded context
```
Modules/Finans/Komisyon uses BaseModule BaseModel (SoftDeletes), while Finance domain
uses App\Models\LedgerEntry. These are intentionally separate bounded contexts (Komisyon
vs Ledger). Not a defect. Intentional bounded context split.
```

### DEBT_04: Services/ directory proliferation
```
241 top-level directories/files under app/Services/. Flat structure without clear
sub-domain grouping. Discoverability concern only — no runtime defect.
```

---

## 5. ORPHAN CANDIDATES

⚠️ "No references found" is NOT sufficient to declare orphans.
Production verification required before removal.

```
1. app/Models/Proje.php (table='projeler') — no active references found (different table from emlak_projeleri)
2. app/Models/Finance/LedgerEntry.php — deprecated stub, no consumers
3. app/Modules/BaseModule/Models/BaseModel.php — effectively legacy, minimal usage
4. app/Models/TurizmDetail.php, app/Models/IlanArsaDetail.php — in Deprecated/ folder
5. app/Models/TkgmQuery.php — backward compat only
```

⚠️ NOTE: "No references found" is NOT sufficient to declare orphans. These need production verification before removal.

---

## 6. INTENTIONAL EXCEPTIONS

```
1. Modules/Finans/Komisyon → separate bounded context (Finans vs Finance/LedgerEntry)
2. Modules/Emlak/Models → self-contained module with its own Feature/Proje management
3. Domain/Ilan + Domain/Kisi → CQRS pattern while other domains don't use it
4. app/Modules/Auth → separate auth domain (intentional module boundary)
5. Modules/TakimYonetimi, Modules/Analitik → domain modules that are separate from core
6. Modules/Crm → separate bounded context for CRM operations (KisiService lives here)
```

---

## 7. DO-NOT-TOUCH LIST

```
1. IlanCrudService write path — established canonical, do not redirect without full regression
2. BelongsToTenant + TenantContextService → tenant isolation boundary, changes require human gate
3. FinancialLedgerService → Finance write authority, changes require finance regression
4. Modules/Emlak/Models/Proje + emlak_projeleri table → Ilan FK depends on this
5. Modules/Emlak routes → active routes for emlak-projeleri resource
6. app/Models/Ilan → canonical Ilan model, heavy usage across codebase
7. TenantScope global scope → changing scope behavior risks cross-tenant data leaks
```

---

## 8. REMEDIATION QUEUE (revised)

| Priority | Finding | Impact | Evidence | Reproducibility | Fixability | Score |
|----------|---------|--------|----------|-----------------|------------|-------|
| P1 | F01: Kisi tenant bypass (CANDIDATE) | HIGH | REPO_VERIFIED → needs TEST_VERIFIED | Runtime test pending | MEDIUM | 9/10 if confirmed |
| P2 | F04: Reservation duplicate authority (CANDIDATE) | MEDIUM | REPO_VERIFIED | e8b9eff closed this boundary | MEDIUM | 6/10 |
| P3 | DEBT_01: IlanFotografi duplicate model | LOW | REPO_VERIFIED | LOW | MEDIUM | 4/10 |
| P4 | F02: Proje two tables → INTENTIONAL BCT SPLIT | LOW | ADR#006 | N/A (not defect) | N/A | N/A |
| P5 | F03/F05: Legacy stubs → intentional transitions | LOW | DOCUMENTED | N/A | LOW | 2/10 |

---

## 9. NEW IDEAS

### NEW_IDEA: KISI_CANONICAL_WRITE_UNIFICATION
```
problem: Kisi write operations split between KisiService (no tenant scope) and BulkKisiService (with tenant scope)
proposed_improvement: Establish App\Models\Kisi as sole canonical model; redirect all writes through BulkKisiService or a new KisiWriteService
expected_benefit: Consistent tenant isolation enforcement for all Kisi mutations
affected_domain: CRM/Kisi
risk: MEDIUM (requires controller and service refactoring)
evidence: KisiController lines 390, 575-586 — both services in same controller
suggested_priority: P1
requires_separate_router_task: YES — _15 Kisi Canonical Write Path
```

### NEW_IDEA: RESERVATION_MODEL_CONSOLIDATION
```
problem: IlanReservation and PropertyReservation both write to property_reservations
proposed_improvement: Deprecate IlanReservation; redirect IlanReservationService to use PropertyReservation
expected_benefit: Single model, single write path for reservations
affected_domain: Reservation
risk: MEDIUM (calendar sync and channel manager consumers need verification)
evidence: IlanReservationService.php:112,304 vs ReservationService.php:149,360
suggested_priority: P2
requires_separate_router_task: YES
```

### NEW_IDEA: PROJECT_TABLE_ARCHIVE_DECISION
```
problem: Two Proje models point to two different tables (emlak_projeleri vs projeler)
proposed_improvement: Verify projeler table usage; if unused, archive with DB backup before drop
expected_benefit: Remove orphan table confusion
affected_domain: Emlak/Proje
risk: LOW (if projeler is truly orphaned)
evidence: Ilan belongsTo Modules\Emlak\Proje, app/Models/Proje has no references
suggested_priority: P3
requires_separate_router_task: YES
```

### NEW_IDEA: MODULES_BASEMODEL_DEPRECATION
```
problem: Modules/BaseModule/Models/BaseModel is effectively legacy with anomalous $routeMiddleware
proposed_improvement: Deprecate; migrate remaining 2-3 consumers to App\Models/BaseModel
expected_benefit: Single base model, clean architecture
affected_domain: Core
risk: LOW (minimal consumers)
evidence: Only FeatureTranslation, FeatureCategoryTranslation extend it
suggested_priority: P4
requires_separate_router_task: NO (bounded cleanup)
```

---

## 10. FINAL CLASSIFICATION (revised per Ayhan feedback)

```
REPOSITORY_ARCHITECTURE: PARTIALLY_FRAGMENTED
```

### Reasoning

YALIHAN OS has a **predominantly coherent canonical architecture** with two genuine fragmentation points:

**Coherent Core:**
- Ilan write path: IlanCrudService is the canonical authority (FINDING_01 aside for Kisi, Ilan itself is clean)
- Tenant isolation: BelongsToTenant + TenantContextService + TenantScope is consistent across core domains
- Finance write: FinancialLedgerService is canonical
- Domain/CQRS pattern established for Ilan and Kisi bounded contexts

**Fragmentation Points (2 real, not aesthetic):**
1. **Kisi: Two models, same table, different write paths with different tenant guarantees** → REAL BUSINESS INVARIANT RISK
2. **Reservation: Two models, same table, independent write services** → maintenance burden, not yet a runtime defect

**Intentional Exceptions (not fragmentation):**
- Modules/Finans → separate bounded context
- Modules/Emlak → self-contained module with its own models
- Two BaseModels → legacy (BaseModule) vs canonical (App\Models)
- Two Tenant models → different purposes (domain entity vs context service)
- Finance/LedgerEntry deprecated stub → intentional transition

**Architectural Debt (not fragmentation):**
- Proje duplicate table (may be orphaned)
- Services/ directory flat structure (discoverability only)
- 241 Services entries (maintenance, not defect)

---

## ANSWER TO FINAL QUESTION

```
Does YALIHAN OS currently have one coherent canonical architecture
with historical/intentional exceptions,
OR
are multiple competing architectural authorities actively governing
the same business domains?
```

**Answer: COHERENT WITH ISOLATED FRAGMENTATION**

The majority of the codebase operates under a single coherent architecture:
- `IlanCrudService` is the canonical Ilan write authority
- `FinancialLedgerService` is the canonical Finance write authority
- `BelongsToTenant + TenantContextService` is the canonical tenant boundary
- `TenantScope` is consistently applied across core models

The primary high-value candidate is **Kisi** domain (F01). Evidence shows two different service paths to the same table with different tenant guarantees. However, **runtime reproduction is required** before assigning a fix — alternative enforcement mechanisms may exist upstream.

**Reservation** (F04) is a **DUPLICATE_AUTHORITY_CANDIDATE**, not a confirmed defect. The e8b9eff commit addressed tenant-aware reservation mutation boundaries. Two models pointing to the same table is not automatically a defect.

The remaining "inconsistencies" (Modules/Emlak, Modules/Finans, two BaseModels, two Tenant models) are **intentional bounded-context separations** or **legacy artifacts in transition** — not competing authorities.

**The most actionable candidate is F01 (Kisi tenant isolation):** Evidence shows KisiService may create records without enforcing `tenant_id` at the model level. This is a HIGH security candidate — but runtime reproduction test is required before fix assignment.

---

## APPENDIX: EVIDENCE PACKAGE

```
BASE MODELS:
  app/Models/BaseModel.php → minimal (21 lines)
  app/Modules/BaseModule/Models/BaseModel.php → abstract, SoftDeletes, 65 lines (includes $routeMiddleware anomaly)

TWO MODELS SAME TABLE (confirmed):
  Kisi: Modules/Crm/Models/Kisi → 'kisiler' | App/Models/Kisi → 'kisiler'
  IlanFotografi: Modules/Emlak → 'ilan_fotograflari' | App/Models → 'ilan_fotograflari'
  Proje: Modules/Emlak → 'emlak_projeleri' | App/Models → 'projeler' (DIFFERENT TABLES)
  LedgerEntry: App/Models → canonical | App/Models/Finance → deprecated stub
  IlanReservation: App/Models → 'property_reservations'
  PropertyReservation: App/Models → 'property_reservations'

TWO TENANT MODELS:
  App/Models/Tenant → BelongsToTenant, HasCountryScope (domain entity)
  App/Models/SaaS/Tenant → SoftDeletes, belongsTo User (context service)

DOMAIN ROOTS:
  Domain/: Ilan, Kisi, Property, CRM, Workspace, AI, Hermes, PropertyHub, PropertyOwnership, ChannelManager
  Modules/: Admin, Auth, Crm, Emlak, Finans, Analitik, TakimYonetimi, Market, GovernanceCore, Talep, TalepAnaliz, BaseModule
  Services/: 241 entries in flat structure

CANONICAL WRITE PATHS:
  Ilan: IlanCrudService → IlanAggregate (CQRS)
  Kisi: BulkKisiService (safe) + KisiService (bypasses tenant scope)
  Reservation: ReservationService + IlanReservationService
  Finance: FinancialLedgerService + KomisyonService
  Tenant: TenantContextService
```

---

```
APPROVED BY: Ayhan (Human Decision Owner)
FORENSIC CONDUCTED: 2026-09-27
STATUS: COMPLETE
DO NOT WRITE TO: EVIDENCE_INDEX / PROJECT_STATE / DECISION_LOG
Next: Parent reviews findings, prioritizes remediation, assigns tasks
```
