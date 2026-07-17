# 03-DOMAIN-ALTERNATIVES.md

## Ownership Model Alternatives

### The Core Question
Should Property own a direct `owner_id` FK, or should ownership be a separate historical record?

---

## Option A: `properties.owner_id` (Mutable FK)

**Design:** Add `owner_id` FK to `properties` table.

```
properties.owner_id → kisiler.id
```

### Evaluation

| Criterion | Score | Notes |
|-----------|-------|-------|
| Single owner | ✅ | Simple |
| Company owner | ✅ | FK to Kisi handles both |
| Multiple owners | ❌ | Only one FK |
| Percentage ownership | ❌ | Not possible |
| Historical queries | ❌ | Overwrite destroys history |
| Ownership transfer | ⚠️ | Overwrites — no audit trail |
| Effective dates | ❌ | Not supported |
| Authorized representative | ❌ | Separate concern |
| Tenant isolation | ⚠️ | FK check required |
| Reporting simplicity | ✅ | Single JOIN |
| Accounting compatibility | ⚠️ | No historical basis |

**Verdict: REJECTED** — Overwriting `owner_id` destroys ownership history. Cannot answer "who owned this in 2023."

---

## Option B: `property_owner` Pivot Table

**Design:**
```
property_owners (property_id, kisi_id, pay_orani, aktiflik_durumu, baslangic_tarihi, bitis_tarihi)
```

### Evaluation

| Criterion | Score | Notes |
|-----------|-------|-------|
| Single owner | ✅ | First record |
| Company owner | ✅ | Kisi record |
| Multiple owners | ✅ | Multiple rows |
| Percentage ownership | ✅ | pay_orani column |
| Historical queries | ⚠️ | `bitis_tarihi` enables historical |
| Ownership transfer | ✅ | Close old, open new |
| Effective dates | ✅ | baslangic/bitis_tarihi |
| Authorized representative | ❌ | Separate table needed |
| Tenant isolation | ✅ | tenant_id + scope |
| Reporting simplicity | ⚠️ | JOIN + active filter |
| Accounting compatibility | ⚠️ | Shares not fully historical |

**Verdict: REJECTED** — Mutable `aktiflik_durumu` flag is a mutation risk. Overwriting `bitis_tarihi` destroys history. No immutable event layer.

---

## Option C: Immutable `PropertyOwnership` Records

**Design:** Append-only ownership records. No "current" flag — query by date.

```
property_ownerships
  id
  tenant_id
  property_id
  kisi_id
  pay_orani (decimal, 0.0001–1.0000)
  sahiplik_tipi (OWNER, BENEFICIAL_OWNER, JOINT_OWNER)
  yetkili_temsilci_id → kisiler.id (nullable)
  baslangic_tarihi
  bitis_tarihi (null = current)
  atama_kaynagi (MANUAL, CONTRACT, INHERITANCE, COURT)
  atama_notu
  idempotency_key
  created_at
```

**Current owner query:** `WHERE property_id = X AND bitis_tarihi IS NULL`
**Historical owner query:** `WHERE property_id = X AND baslangic_tarihi <= DATE AND (bitis_tarihi IS NULL OR bitis_tarihi >= DATE)`

### Evaluation

| Criterion | Score | Notes |
|-----------|-------|-------|
| Single owner | ✅ | One active record |
| Company owner | ✅ | Kisi with kisi_tipi = company |
| Multiple owners | ✅ | Multiple active records |
| Percentage ownership | ✅ | pay_orani decimal |
| Historical queries | ✅ | Date-range queries |
| Ownership transfer | ✅ | Close + open new |
| Effective dates | ✅ | baslangic/bitis_tarihi |
| Authorized representative | ✅ | yetkili_temsilci FK |
| Tenant isolation | ✅ | tenant_id on every record |
| Reporting simplicity | ⚠️ | More JOINs but correct |
| Accounting compatibility | ✅ | Full historical basis |
| Immutability | ✅ | No UPDATE on pay_orani or dates |

**Verdict: RECOMMENDED** — Append-only with `bitis_tarihi` closure. No mutation of ownership percentages. Full audit history.

---

## Option D: Hybrid — Immutable History + Current Projection

**Design:** Option C + a lightweight `property_current_ownership` view or cached projection.

```
property_ownerships (immutable history — same as Option C)
```

Plus a service-layer read model:
```php
PropertyOwnershipService::getCurrentOwnership(propertyId): Collection
PropertyOwnershipService::getOwnershipAt(propertyId, DateTime $date): Collection
```

### Evaluation

| Criterion | Score | Notes |
|-----------|-------|-------|
| All Option C benefits | ✅ | Same |
| Reporting simplicity | ✅ | Projected current view |
| Performance | ✅ | No date range scan per query |
| Consistency | ⚠️ | Projection must be rebuildable |
| Accounting compatibility | ✅ | Source of truth is immutable |

**Verdict: RECOMMENDED** — Option C with service-layer projections. Most robust design.

---

## Preferred Design: Option D (Hybrid Immutable)

### Schema

```php
// property_ownerships — immutable append-only
Schema::create('property_ownerships', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('restrict');
    $table->foreignId('property_id')->constrained()->onDelete('restrict');
    $table->foreignId('kisi_id')->constrained()->onDelete('restrict');
    $table->decimal('pay_orani', 6, 4)->comment('0.0001–1.0000, nullable for representative');
    $table->enum('sahiplik_tipi', ['OWNER','BENEFICIAL_OWNER','JOINT_OWNER','REPRESENTATIVE']);
    $table->foreignId('yetkili_temsilci_id')->nullable()->constrained('kisiler');
    $table->date('baslangic_tarihi');
    $table->date('bitis_tarihi')->nullable()->comment('null = currently active');
    $table->enum('atama_kaynagi', ['MANUAL','CONTRACT','INHERITANCE','COURT','TKGM']);
    $table->text('atama_notu')->nullable();
    $table->string('idempotency_key', 64)->unique();
    $table->timestamps();

    $table->index(['property_id', 'bitis_tarihi']); // current owner query
    $table->index(['property_id', 'baslangic_tarihi', 'bitis_tarihi']); // historical
    $table->unique(['property_id', 'idempotency_key']);
});

// property_representatives — immutable representative assignments
Schema::create('property_representatives', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('restrict');
    $table->foreignId('property_id')->constrained()->onDelete('restrict');
    $table->foreignId('kisi_id')->constrained()->onDelete('restrict');
    $table->enum('temsil_yetu_tipi', ['FULL','FINANCIAL','OPERATIONAL','LEGAL']);
    $table->date('baslangic_tarihi');
    $table->date('bitis_tarihi')->nullable();
    $table->text('notu')->nullable();
    $table->string('idempotency_key', 64)->unique();
    $table->timestamps();

    $table->index(['property_id', 'bitis_tarihi']);
});
```

---

## Party Model Alternatives

### Alternative 1: Extend Kisi for Legal Entities

**Design:** Add `kisi_tipi = LEGAL_ENTITY` to Kisi. Add fields:
- `vergi_kimlik_no` (tax ID)
- `mersis_no` (trade registry)
- `sicil_no` (registration number)
- `kurum_unvani` (company name)

**Pros:** Single table, existing relationships work
**Cons:** Kisi was designed for natural persons; mixing legal entities is a domain smell
**Verdict:** ACCEPTABLE for Sprint 12D — add nullable company fields to Kisi. Distinguish via `kisi_tipi` enum.

### Alternative 2: Separate `LegalEntity` Model

**Design:** New `legal_entities` table, FK from `property_ownerships.kisi_id`
**Pros:** Clean separation
**Cons:** Two tables for "party", more JOIN complexity
**Verdict:** DEFER — only if company representation becomes complex

---

## Property Identity Structure

### Current State

`Property` has: `ada`, `parsel`, `kat_sayisi`, `bulundugu_kat`, `oda_sayisi`, `banyo_sayisi`, `alan_m2`, `bina_yasi`

### Missing Structure

| Concept | Status | Decision |
|---------|--------|----------|
| Site / Compound | MISSING | VALUE OBJECT — `PropertySiteId` VO on Property |
| Block | MISSING | VALUE OBJECT — `PropertyBlockId` VO |
| Building | MISSING | VALUE OBJECT — `PropertyBuildingId` VO |
| Entrance | MISSING | ATTRIBUTE — nullable string on Property |
| Floor (unit level) | REUSE | Already exists: `bulundugu_kat` |
| Apartment number | MISSING | ATTRIBUTE — `daire_no` on Property |
| Independent unit | MISSING | VALUE OBJECT — for land/parcel units |
| Ada / Parcel | REUSE | Already exists: `ada`, `parsel` |
| Full address | PARTIAL | Reuse Ilan address fields, attach to Property |

**Design Decision:** Create structured Value Objects, not new aggregates.

```php
// Value Object — not an Entity
class PropertySiteId
{
    public function __construct(
        public readonly ?string $siteAdi,
        public readonly ?string $blokAdi,
    ) {}
}

// Stored as JSON column on Property
// property_identity_json: { site: {...}, building: {...}, entrance: 'A', unit: '12' }
```

---

## Key and Access Model Alternatives

### Design: PropertyAccessAsset + KeyCustody

```
property_access_assets
  id, tenant_id, property_id, varlik_tipi (KEY, CARD, REMOTE, SMART_LOCK, ALARM_CODE, STORAGE_KEY)
  tanimlayici_no (key number, card UID)
  durum (AKTIF, KAYIP, DEAKTIVE, IPTAL)
  olusturan_id, created_at

property_key_custodies — immutable custody transfer log
  id, tenant_id, asset_id, kisi_id, islem_tipi (TESLIM, IADE, KAYIP_BILDIRIM, YENILEME)
  islem_tarihi, notu, idempotency_key, olusturan_id
```

**Command model:**
```php
PropertyAccessAsset::register(assetType, identifier, property, actor)
PropertyKeyCustody::transfer(assetId, newHolderId, actor, note)
PropertyKeyCustody::return(assetId, actor, note)
PropertyAccessAsset::reportLost(assetId, actor)
PropertyAccessAsset::replace(assetId, newIdentifier, actor)
PropertyAccessAsset::deactivate(assetId, actor)
```

**Verdict: RECOMMENDED** — Minimum viable custody model. Immutable custody log. Current holder = last open custody record.

---

## Document Model Alternatives

### Design: PropertyDocument (classification only, no file storage)

```
property_documents
  id, tenant_id, property_id
  dokuman_tipi (TITLE_DEED, MANAGEMENT_AGREEMENT, OWNER_AUTH, ID_DOCUMENT, COMPANY_DOC, INSURANCE, OCCUPANCY_PERMIT, ZONING, UTILITY, KEY_RECEIPT)
  dosya_yolu (existing media storage path — no duplicate)
  referans_no (title deed number, policy number)
  yayin_tarihi, son_gecerlilik_tarihi
  durum (AKTIF, SURESI_DOLMUS, IPTAL)
  notu, olusturan_id, created_at
```

**Storage decision:** Reuse existing media system. `property_documents.dosya_yolu` points to existing file storage. Do NOT create a new document store.

**Verdict: RECOMMENDED** — Classification and expiry tracking only. File storage is an existing system concern.

---

*Alternatives evaluated: 2026-07-17*
