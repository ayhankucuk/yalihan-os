# 06-SAAB-PROPOSAL.md

## Sprint 12D — SAAB Proposal
## Property Ownership & Operations Foundation

**Board:** SAAB v11 (BR-20260715-SAABv11)
**Author:** Sprint 12D Discovery Agent
**Date:** 2026-07-17
**Status:** DISCOVERY — AWAITING SAAB APPROVAL
**Implementation:** NOT AUTHORIZED — awaiting GO decision

---

## 1. Executive Summary

Sprint 12D designs the **ownership and operational identity layer** for the canonical `Property` aggregate certified in Sprint 12C.

**Core insight:** Property currently has no owner, no representatives, no keys, no documents, no structural identity beyond TKGM fields, and no history. Every property operation in Yalıhan depends on answers to seven questions that today have no authoritative answer in the system:

1. Who is the legal/operational owner?
2. Who was the owner in the past?
3. What are the ownership shares?
4. Who is the authorized representative?
5. What is the property's structural identity (site, block, building, unit)?
6. Which keys/access credentials exist and who holds them?
7. Which documents exist, are missing, or have expired?

**Recommended approach:** Immutable `PropertyOwnership` records (Option D — hybrid) + `PropertyRepresentative` + `PropertyAccessAsset` + `PropertyKeyCustody` + `PropertyDocument` + structured `PropertyIdentity`. All new entities are tenant-isolated, replay-safe, and follow the canonical write chain: `Service → Model → Repository → DB`.

---

## 2. Recommended Domain Model

### Aggregate Map

```
Property (CANONICAL — Sprint 12C, unchanged)
  ├── owns → PropertyWorkspace (CANONICAL — Sprint 12C, unchanged)
  ├── owns → PropertyOwnership (NEW — immutable historical)
  ├── owns → PropertyRepresentative (NEW — immutable)
  ├── owns → PropertyAccessAsset (NEW — operational)
  ├── owns → PropertyDocument (NEW — classification only)
  └── owns → PropertyTimelineEvent (NEW — immutable log)

Kisi (EXISTING — REUSE, extended for legal entity)
  ├── natural person (tc_kimlik, meslek)
  └── legal entity (NEW: vergi_kimlik_no, kurum_unvani, mersis_no)
```

**Party model decision:** "Owner" is a **relationship** between `Kisi` (natural person or legal entity) and `Property`. Owner is NOT a standalone aggregate. We extend `Kisi` with nullable legal entity fields. We do NOT create a separate `Owner` aggregate.

**Party and Owner Decision:**
- `Kisi` = natural person + legal entity (extended)
- `Owner` = a `Kisi` with an active `PropertyOwnership` record
- `Representative` = a `Kisi` with an active `PropertyRepresentative` record
- No separate `Party`, `Owner`, or `LegalEntity` aggregate

---

## 3. Aggregate Boundaries

| Aggregate | Root Entity | Boundary | invariants |
|-----------|------------|----------|------------|
| PropertyOwnership | `PropertyOwnership` | Covers ownership assignment, share changes, transfers. Immutable records. | Shares sum to 1.0. No overlapping active periods. |
| PropertyRepresentative | `PropertyRepresentative` | Covers authorized representative assignment. Immutable records. | One active representative per `temsil_yetu_tipi` per property. |
| PropertyAccessAsset | `PropertyAccessAsset` | Covers physical asset registration and state (active/lost/deactivated). Immutable custody log. | Asset belongs to exactly one property. |
| PropertyDocument | `PropertyDocument` | Covers document classification, expiry, status. No file storage. | Document belongs to exactly one property. |

**What each aggregate does NOT own:**
- `PropertyOwnership` does NOT own representative assignments
- `PropertyAccessAsset` does NOT own document classification
- `Property` aggregate root does NOT own ownership — it is referenced by ownership records
- Accounting, commissions, valuation are NOT in any aggregate

---

## 4. Cardinalities

```
Property (1) ←→ (N) PropertyOwnership
Property (1) ←→ (N) PropertyRepresentative
Property (1) ←→ (N) PropertyAccessAsset
Property (1) ←→ (N) PropertyDocument

Kisi (1) ←→ (N) PropertyOwnership  (kisi can own many properties)
Kisi (1) ←→ (N) PropertyRepresentative  (kisi can represent many properties)
Kisi (1) ←→ (N) PropertyKeyCustody  (kisi can hold many keys)

PropertyAccessAsset (1) ←→ (N) PropertyKeyCustody
PropertyOwnership (1) ←→ (1) Kisi (current owner)
PropertyOwnership (N) ←→ (1) Property (historical)
```

---

## 5. Proposed Schema

### 5.1 `property_ownerships` — Immutable ownership history

```sql
CREATE TABLE property_ownerships (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  property_id BIGINT UNSIGNED NOT NULL,
  kisi_id BIGINT UNSIGNED NOT NULL,
  pay_orani DECIMAL(6,4) NOT NULL DEFAULT 1.0000,
  sahiplik_tipi ENUM('OWNER','BENEFICIAL_OWNER','JOINT_OWNER','REPRESENTATIVE')
    NOT NULL DEFAULT 'OWNER',
  yetkili_temsilci_id BIGINT UNSIGNED NULL,
  baslangic_tarihi DATE NOT NULL,
  bitis_tarihi DATE NULL COMMENT 'null = currently active',
  atama_kaynagi ENUM('MANUAL','CONTRACT','INHERITANCE','COURT','TKGM')
    NOT NULL DEFAULT 'MANUAL',
  atama_notu TEXT NULL,
  idempotency_key VARCHAR(64) NOT NULL UNIQUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,

  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE RESTRICT,
  FOREIGN KEY (kisi_id) REFERENCES kisiler(id) ON DELETE RESTRICT,
  FOREIGN KEY (yetkili_temsilci_id) REFERENCES kisiler(id) ON DELETE SET NULL,

  INDEX idx_po_property_active (property_id, bitis_tarihi),
  INDEX idx_po_property_historical (property_id, baslangic_tarihi, bitis_tarihi),
  INDEX idx_po_tenant (tenant_id)
) ENGINE=InnoDB;
```

**Model-level invariants:**
```php
// No updates or deletes allowed
static::updating(fn() => throw new DomainException('Immutable record'));
static::deleting(fn() => throw new DomainException('Immutable record'));
```

### 5.2 `property_representatives`

```sql
CREATE TABLE property_representatives (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  property_id BIGINT UNSIGNED NOT NULL,
  kisi_id BIGINT UNSIGNED NOT NULL,
  temsil_yetu_tipi ENUM('FULL','FINANCIAL','OPERATIONAL','LEGAL') NOT NULL,
  baslangic_tarihi DATE NOT NULL,
  bitis_tarihi DATE NULL,
  notu TEXT NULL,
  idempotency_key VARCHAR(64) NOT NULL UNIQUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,

  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE RESTRICT,
  FOREIGN KEY (kisi_id) REFERENCES kisiler(id) ON DELETE RESTRICT,

  INDEX idx_pr_property_active (property_id, bitis_tarihi),
  INDEX idx_pr_tenant (tenant_id)
) ENGINE=InnoDB;
```

### 5.3 `property_access_assets`

```sql
CREATE TABLE property_access_assets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  property_id BIGINT UNSIGNED NOT NULL,
  varlik_tipi ENUM('KEY','SITE_CARD','GARAGE_REMOTE','SMART_LOCK','ALARM_CODE','STORAGE_KEY')
    NOT NULL,
  tanimlayici_no VARCHAR(255) NULL COMMENT 'key number, card UID — SENSITIVE, hidden by default',
  durum ENUM('AKTIF','KAYIP','DEAKTIVE','IPTAL') NOT NULL DEFAULT 'AKTIF',
  olusturan_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,

  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE RESTRICT,
  FOREIGN KEY (olusturan_id) REFERENCES users(id) ON DELETE RESTRICT,

  INDEX idx_paa_property (property_id),
  INDEX idx_paa_tenant (tenant_id)
) ENGINE=InnoDB;
```

### 5.4 `property_key_custodies` — Immutable custody log

```sql
CREATE TABLE property_key_custodies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  asset_id BIGINT UNSIGNED NOT NULL,
  kisi_id BIGINT UNSIGNED NOT NULL COMMENT 'current holder',
  islem_tipi ENUM('TESLIM','IADE','KAYIP_BILDIRIM','YENILEME') NOT NULL,
  islem_tarihi TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  notu TEXT NULL,
  olusturan_id BIGINT UNSIGNED NOT NULL,
  idempotency_key VARCHAR(64) NOT NULL UNIQUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,

  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT,
  FOREIGN KEY (asset_id) REFERENCES property_access_assets(id) ON DELETE RESTRICT,
  FOREIGN KEY (kisi_id) REFERENCES kisiler(id) ON DELETE RESTRICT,
  FOREIGN KEY (olusturan_id) REFERENCES users(id) ON DELETE RESTRICT,

  INDEX idx_pkc_asset (asset_id),
  INDEX idx_pkc_tenant (tenant_id)
) ENGINE=InnoDB;
```

### 5.5 `property_documents`

```sql
CREATE TABLE property_documents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  property_id BIGINT UNSIGNED NOT NULL,
  dokuman_tipi ENUM(
    'TITLE_DEED','MANAGEMENT_AGREEMENT','OWNER_AUTHORIZATION',
    'ID_DOCUMENT','COMPANY_DOCUMENT','INSURANCE',
    'OCCUPANCY_PERMIT','ZONING','UTILITY_SUBSCRIPTION','KEY_RECEIPT'
  ) NOT NULL,
  dosya_yolu VARCHAR(500) NULL COMMENT 'points to existing media storage',
  referans_no VARCHAR(255) NULL COMMENT 'title deed number, policy number',
  yayin_tarihi DATE NULL,
  son_gecerlilik_tarihi DATE NULL,
  durum ENUM('AKTIF','SURESI_DOLMUS','IPTAL') NOT NULL DEFAULT 'AKTIF',
  notu TEXT NULL,
  olusturan_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,

  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE RESTRICT,
  FOREIGN KEY (olusturan_id) REFERENCES users(id) ON DELETE RESTRICT,

  INDEX idx_pd_property (property_id),
  INDEX idx_pd_expiry (son_gecerlilik_tarihi),
  INDEX idx_pd_tenant (tenant_id)
) ENGINE=InnoDB;
```

### 5.6 `property_identity_json` — Structured identity (JSON column on Property)

```sql
-- Added as JSON column to existing properties table
ALTER TABLE properties
  ADD COLUMN property_identity_json JSON NULL
  COMMENT 'Structured identity: { site: {name, block}, building: {name, entrance}, unit: {floor, no} }';
```

### 5.7 `kisiler` extension for legal entities

```sql
ALTER TABLE kisiler
  ADD COLUMN vergi_kimlik_no VARCHAR(20) NULL,
  ADD COLUMN kurum_unvani VARCHAR(255) NULL,
  ADD COLUMN mersis_no VARCHAR(20) NULL,
  ADD COLUMN sicil_no VARCHAR(20) NULL;
```

---

## 6. Invariants

### PropertyOwnership Invariants

1. **Share sum = 1.0:** Sum of `pay_orani` for all `bitis_tarihi IS NULL` records for a property MUST equal 1.0000.
2. **No duplicate active ownership:** A `kisi_id` cannot have more than one active ownership record for the same property.
3. **No overlapping periods:** A `kisi_id` cannot have overlapping `baslangic_tarihi`/`bitis_tarihi` ranges for the same property.
4. **Immutability:** No UPDATE or DELETE on `property_ownerships` records.
5. **Idempotency:** Duplicate commands with same `idempotency_key` return existing record (not error).
6. **Tenant isolation:** `property_id` and `kisi_id` must both belong to current tenant.

### PropertyRepresentative Invariants

1. **One per type:** Only one active representative per `temsil_yetu_tipi` per property.
2. **Representative ≠ Owner:** A representative does not need to be an owner (and vice versa).
3. **Immutability:** No UPDATE or DELETE — close with `bitis_tarihi` instead.
4. **Tenant isolation:** Both `property_id` and `kisi_id` tenant-verified.

### PropertyAccessAsset Invariants

1. **One current holder:** An asset has exactly one current holder — the latest `TESLIM` without a subsequent `IADE`.
2. **Lost ≠ Deactivated:** `KAYIP` sets `durum = KAYIP` but does NOT close the asset.
3. **Sensitive field protection:** `tanimlayici_no` never exposed in API responses without explicit policy check.
4. **Immutability:** Asset state changes are new records (custody) not updates.

### PropertyDocument Invariants

1. **Storage delegation:** `dosya_yolu` points to existing media store — no duplicate file storage.
2. **Expiry tracking:** Document with `son_gecerlilik_tarihi < today()` automatically marked `SURESI_DOLMUS` by scheduled job.
3. **No delete:** Documents cannot be deleted, only marked `IPTAL`.

---

## 7. Commands

### Ownership Commands

```php
// Assign initial owner(s) to a Property
PropertyOwnershipService::assignOwnership(
    property: Property,
    kisi: Kisi,
    share: Decimal,
    ownershipType: SahiplikTipi,
    effectiveDate: Date,
    source: AtamaKaynagi,
    idempotencyKey: string,
    actor: User,
): PropertyOwnership

// Transfer ownership (close current + open new)
PropertyOwnershipService::transferOwnership(
    property: Property,
    fromKisi: Kisi,
    toKisi: Kisi,
    share: Decimal,
    effectiveDate: Date,
    source: AtamaKaynagi,
    idempotencyKey: string,
    actor: User,
): (PropertyOwnership $closed, PropertyOwnership $opened)

// Change share percentages
PropertyOwnershipService::changeShares(
    property: Property,
    changes: ShareChange[],  // [(kisiId, newShare)]
    effectiveDate: Date,
    idempotencyKey: string,
    actor: User,
): void  // closes all old, opens all new
```

### Representative Commands

```php
PropertyRepresentativeService::assignRepresentative(
    property: Property,
    kisi: Kisi,
    authorityType: TemsilYetkiTipi,
    effectiveDate: Date,
    note: string|null,
    idempotencyKey: string,
    actor: User,
): PropertyRepresentative

PropertyRepresentativeService::revokeRepresentative(
    representative: PropertyRepresentative,
    effectiveDate: Date,
    actor: User,
): void
```

### Access Asset Commands

```php
PropertyAccessAssetService::registerAsset(
    property: Property,
    assetType: VarlikTipi,
    identifier: string|null,
    actor: User,
): PropertyAccessAsset

PropertyAccessAssetService::transferCustody(
    asset: PropertyAccessAsset,
    newHolder: Kisi,
    note: string|null,
    idempotencyKey: string,
    actor: User,
): PropertyKeyCustody

PropertyAccessAssetService::returnCustody(
    asset: PropertyAccessAsset,
    actor: User,
    note: string|null,
    idempotencyKey: string,
): PropertyKeyCustody

PropertyAccessAssetService::reportLost(
    asset: PropertyAccessAsset,
    actor: User,
): PropertyAccessAsset

PropertyAccessAssetService::replace(
    asset: PropertyAccessAsset,
    newIdentifier: string,
    actor: User,
): PropertyAccessAsset

PropertyAccessAssetService::deactivateCredential(
    asset: PropertyAccessAsset,
    actor: User,
): PropertyAccessAsset
```

### Document Commands

```php
PropertyDocumentService::attachDocument(
    property: Property,
    documentType: DokumanTipi,
    filePath: string,
    referenceNumber: string|null,
    issueDate: Date|null,
    expiryDate: Date|null,
    actor: User,
): PropertyDocument

PropertyDocumentService::markExpired(
    document: PropertyDocument,
): PropertyDocument

PropertyDocumentService::invalidate(
    document: PropertyDocument,
    actor: User,
): PropertyDocument
```

---

## 8. Events

All events are immutable, tenant-scoped, and replay-safe with idempotency keys.

| Event | Trigger | Payload | Replay Behavior |
|-------|---------|---------|----------------|
| `PropertyOwnerAssigned` | `assignOwnership` | `{propertyId, kisiId, share, type, date, actor}` | Idempotent — duplicate key returns existing |
| `PropertyOwnershipTransferred` | `transferOwnership` | `{propertyId, fromKisiId, toKisiId, share, date, actor}` | Idempotent |
| `PropertyOwnershipShareChanged` | `changeShares` | `{propertyId, changes[], date, actor}` | Idempotent |
| `PropertyRepresentativeAssigned` | `assignRepresentative` | `{propertyId, kisiId, authorityType, date}` | Idempotent |
| `PropertyRepresentativeRevoked` | `revokeRepresentative` | `{propertyId, kisiId, date}` | Idempotent |
| `PropertyAccessAssetRegistered` | `registerAsset` | `{assetId, propertyId, type, identifier, actor}` | Idempotent |
| `PropertyKeyTransferred` | `transferCustody` | `{assetId, fromKisiId, toKisiId, date, actor}` | Idempotent |
| `PropertyKeyReturned` | `returnCustody` | `{assetId, kisiId, date, actor}` | Idempotent |
| `PropertyKeyReportedLost` | `reportLost` | `{assetId, kisiId, date, actor}` | Idempotent |
| `PropertyCredentialDeactivated` | `deactivateCredential` | `{assetId, date, actor}` | Idempotent |
| `PropertyDocumentAttached` | `attachDocument` | `{documentId, propertyId, type, expiryDate}` | Idempotent |
| `PropertyDocumentExpired` | `markExpired` (scheduled) | `{documentId, expiryDate}` | Idempotent |

**All events include:** `event_id` (UUID), `tenant_id`, `occurred_at`, `idempotency_key`, `actor_id`.

---

## 9. Service Boundaries

```
Controller (thin)
  └── PropertyOwnershipService      (assignOwnership, transferOwnership, changeShares, getCurrent, getHistory)
  └── PropertyRepresentativeService (assign, revoke, getCurrent, getHistory)
  └── PropertyAccessAssetService   (register, transfer, return, reportLost, replace, deactivate, getCurrent)
  └── PropertyKeyCustodyService    (log only — called by PropertyAccessAssetService)
  └── PropertyDocumentService      (attach, markExpired, invalidate, getForProperty)
  └── PropertyTimelineService      (query: getEvents, getOwnershipTimeline, getKeyTimeline)
```

**What services do NOT do:**
- Accounting calculations (commissions, fees) — OUT OF SCOPE
- Valuation — OUT OF SCOPE
- Listing publication — OUT OF SCOPE
- Drive operations — OUT OF SCOPE (PortfolioDriveWorkspace)

---

## 10. Authorization Rules

| Action | Required Role | Additional Check |
|--------|--------------|-----------------|
| `assignOwnership` | TenantAdmin | Property must belong to tenant |
| `transferOwnership` | TenantAdmin | All current owners must consent (policy) |
| `changeShares` | TenantAdmin | All current owners must consent (policy) |
| `assignRepresentative` | TenantAdmin | Kisi must belong to tenant |
| `revokeRepresentative` | TenantAdmin | Self-revoke allowed if representative |
| `registerAsset` | TenantAdmin, PropertyManager | Property tenant check |
| `transferCustody` | PropertyManager | Current owner or representative |
| `returnCustody` | PropertyManager | Same as transfer |
| `reportLost` | PropertyManager | Same as transfer |
| `deactivateCredential` | TenantAdmin | Same as transfer |
| `attachDocument` | TenantAdmin | Property tenant check |

---

## 11. Migration Impact

**5 new migrations:**

1. `add_kisi_company_fields` — nullable company fields on `kisiler`
2. `add_property_identity_json` — JSON column on `properties`
3. `create_property_ownerships` — immutable ownership table
4. `create_property_representatives` — immutable representative table
5. `create_property_access_assets` — asset and custody tables

**No Sprint 12C schema modification.** All new tables are additive.

**Migration strategy:** Zero-downtime additive migrations. No data migration required at this stage.

**Legacy bridge:** `Ilan.ilan_sahibi_id` → `Kisi` remains for backward compatibility. During Sprint 12E, a bridge service will sync Ilan owner → PropertyOwnership. Until then, Ilan owner is a separate concern.

---

## 12. Replay and Idempotency Rules

1. Every write command accepts `idempotency_key` (UUID, client-generated)
2. Service checks `WHERE idempotency_key = X` before processing
3. If found: return existing record (HTTP 200, not error)
4. If not found: process and persist with the same key
5. Events include idempotency key for downstream consumers
6. Timeline projection rebuilds from event log (idempotent — same key = same event)

---

## 13. OBS-1 Classification

### PortfolioDriveWorkspace

**Classification: VALID SEPARATE MODEL** ✅

**Rationale:**
- Owns Google Drive workspace metadata (folder ID, webhook channel, AI completion flags)
- Owns publishing lifecycle state machine (WorkspaceState enum)
- Does NOT own operational workspace execution (PropertyWorkspace)
- Does NOT own ownership or key records

**Relationship to Sprint 12D:** None. It operates on `ilan_id` → `Property` via the Ilan bridge. The two Workspace models serve different purposes and should NOT be merged.

**Action:** Do not refactor in Sprint 12D or Sprint 12E.

### WorkspaceDashboardController

**Classification: VALID SEPARATE MODEL** ✅

**Rationale:**
- Read layer for PortfolioDriveWorkspace
- Delivers cockpit UI data (summary, events, health, next actions)
- Not a competing aggregate — a projection service for Drive workspace

**Action:** Keep as-is.

---

## 14. Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Kisi lacks TenantScope — cross-tenant access possible | HIGH | Service-layer enforcement in Sprint 12D ownership service. Add TenantScope to Kisi as micro-task. |
| Share validation performance — summing all owners per query | MEDIUM | Index on `(property_id, bitis_tarihi)`. Background job validates daily. |
| Alarm code exposure in API | HIGH | Hidden fields + policy-gated accessor. No direct serialization. |
| Idempotency key collision | LOW | UUID v4 — collision probability negligible |
| Document file path orphaned on storage deletion | LOW | Only marks IPTAL, never deletes record |
| Legacy Ilan owner drift from PropertyOwnership | MEDIUM | Bridge service in Sprint 12E keeps them synchronized |

---

## 15. Rejected Alternatives

| Alternative | Reason for Rejection |
|------------|---------------------|
| Option A: `properties.owner_id` mutable FK | Destroys history on overwrite — cannot answer "who owned in 2023" |
| Option B: `property_owner` pivot with mutable flags | Mutation risk on `aktiflik_durumu` and `bitis_tarihi` |
| Separate `LegalEntity` model | Over-engineering for Phase 1 — extend Kisi is sufficient |
| New document file storage | Existing media system exists — reuse, don't duplicate |
| Separate Site/Block/Building aggregates | No behavior owned — value objects on Property are sufficient |
| PropertyEventApiController integration | Irrelevant — deals with reservations, not ownership events |

---

## 16. Implementation Estimate

**Estimated total:** 3 sprints

| Sprint | Focus | Deliverables |
|--------|-------|-------------|
| **12D** (this sprint) | Core ownership + identity | 5 migrations, PropertyOwnership, PropertyRepresentative, PropertyIdentity, services, timeline events |
| **12E** | Key custody + documents | PropertyAccessAsset, PropertyKeyCustody, PropertyDocument, expiry scheduler |
| **12F** | Legacy bridge + validation | Ilan owner → PropertyOwnership sync, share validation job, Kisi TenantScope |

**Sprint 12D breakdown:**
- Schema design + migrations: 0.5 days
- PropertyOwnership model + service + tests: 1 day
- PropertyRepresentative model + service + tests: 0.5 days
- PropertyIdentity JSON + VO: 0.5 days
- Timeline projection service: 0.5 days
- Authorization policies: 0.5 days
- Integration tests: 1 day

**Total Sprint 12D: ~5 days**

---

## 17. Test Strategy

### Unit Tests

| Test | Coverage |
|------|----------|
| `PropertyOwnership__no_update_throws` | Model-level write protection |
| `PropertyOwnership__share_sum_validation` | Shares must sum to 1.0 |
| `PropertyOwnership__idempotency_returns_existing` | Duplicate key → return existing |
| `PropertyOwnership__current_owner_query` | `bitis_tarihi IS NULL` query |
| `PropertyRepresentative__one_per_type` | Only one active per type |
| `PropertyAccessAsset__current_holder_query` | Latest non-returned custody |
| `PropertyDocument__expiry_scope` | Expired document query |

### Integration Tests

| Test | Scenario |
|------|----------|
| `assign_ownership__tenant_isolation__rejects_cross_tenant` | Cross-tenant assignment throws |
| `transfer_ownership__closes_old__opens_new` | Ownership transfer preserves history |
| `change_shares__closes_all__opens_all` | Share change atomic |
| `key_transfer__custody_log_created` | Immutable custody record |
| `key_report_lost__asset_state_updated` | Lost status set |
| `document_attach__no_duplicate_storage` | File path reused |

### Replay Tests

| Test | Scenario |
|------|----------|
| `ownership_command__replay_same_idempotency_key__returns_same_record` | Replay safety |
| `ownership_command__different_key__creates_new_record` | Unique command |
| `timeline_rebuild__from_events__matches_projection` | Event sourcing consistency |

---

## 18. GO / NO-GO Criteria

| # | Criterion | Status |
|---|-----------|--------|
| 1 | No duplicate Party/Owner model without justification | ✅ SATISFIED — Owner is a relationship, not an aggregate |
| 2 | Ownership history preserved | ✅ SATISFIED — Immutable `bitis_tarihi` closure, no updates |
| 3 | Tenant isolation demonstrably enforceable | ✅ SATISFIED — Service-layer dual verification on property + kisi |
| 4 | Multi-owner scenario addressed | ✅ SATISFIED — Multiple rows, share sum validation |
| 5 | Key custody auditable | ✅ SATISFIED — Immutable custody log with `idempotency_key` |
| 6 | Document duplication avoided | ✅ SATISFIED — `dosya_yolu` reuses existing storage |
| 7 | Replay-safe event model | ✅ SATISFIED — Idempotency keys on every command |
| 8 | Scope excludes accounting | ✅ SATISFIED — No ledger, commission, payout entities |
| 9 | Concrete business-value mapping | ✅ SATISFIED — Every entity maps to real operation |
| 10 | No conflict with Sprint 12C canonical rules | ✅ SATISFIED — All new tables are additive |

**Outstanding concern:** Kisi lacks TenantScope. **Condition:** Service-layer enforcement is mandatory in Sprint 12D implementation. TenantScope addition to Kisi is a required micro-task in the sprint.

---

## 19. Sprint 12E Compatibility

Sprint 12E (Accounting foundation) will build on Sprint 12D:

- `PropertyOwnership.pay_orani` → Commission distribution basis
- `PropertyOwnership.baslangic_tarihi` → Commission period start
- `PropertyRepresentative` → Invoice recipient identification
- `PropertyDocument` (INSURANCE) → Policy verification for rental accounting

**Sprint 12D is backward-compatible with Sprint 12E design.**

---

## 20. Explicit Non-Goals

The following are explicitly OUT OF SCOPE for Sprint 12D and Sprint 12E:

- Accounting ledger or journal entries
- Owner financial statements
- Commission rate calculation
- Invoice generation
- Bank account or payout processing
- Reservation-based revenue accounting
- External listing intelligence (Sahibinden, DreamIn)
- Valuation provider integration
- Market intelligence or pricing
- UI redesign or frontend changes
- PortfolioDriveWorkspace refactoring
- PropertyWorkspace modification
- Any changes to `docs/SAB.md` or `.sab/authority.json`

---

## 21. SAAB Decision

### Recommendation: **GO WITH CONDITIONS**

**Conditions for GO:**

1. **Kisi TenantScope micro-task:** Add `TenantScope` to `Kisi` model as part of Sprint 12D implementation. This is not a discovery item — it is a required implementation safety measure.

2. **Share validation job:** Implement daily scheduled job to validate share sum = 1.0 and raise alerts for violations. This is a business-critical invariant that must be monitored.

3. **Document expiry scheduler:** Implement daily scheduled job to mark expired documents as `SURESI_DOLMUS`. This prevents compliance blind spots.

4. **Timeline projection:** `PropertyTimelineService` must be implemented in Sprint 12D (not deferred), as it answers the fundamental success question: "Who owned this property and when?"

### Sprint 12D Success Questions (Must Answer)

After Sprint 12D, the system MUST answer:
- "Bu mülkün şu anki sahibi kim?" ✅
- "2024'te kimindi?" ✅
- "Payları nedir?" ✅
- "Yetkili temsilci kim?" ✅
- "Mülkün yapısal kimliği (site, blok, daire) nedir?" ✅

---

*SAAB Proposal prepared: 2026-07-17*
*Next: SAAB Board Resolution required before implementation*
