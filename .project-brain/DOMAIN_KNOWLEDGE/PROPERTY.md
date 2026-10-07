# DOMAIN KNOWLEDGE: PROPERTY

**LAST_VERIFIED_HEAD:** a7aa6532
**Evidence Level:** REPO_VERIFIED

---

## Business Concept: Property

**Tanım:**
- Yönetilen gayrimenkul varlığı
- TKGM (Tapu Kadastro) kimliği
- İlan'dan bağımsız bir varlık

**SOURCE:** Property model

---

## Property vs İlan

| Aspect | Property | İlan |
|--------|----------|------|
| Definition | Gerçek gayrimenkul | İlan/platform sunumu |
| Public | ❌ Private | ✅ Public olabilir |
| Immutable | TKGM, Ada, Parsel | Mutable (durum değişir) |
| Purpose | Varlık yönetimi | Pazarlama/satış |
| Lifetime | Permanent | Can be archived |

---

## Property Özellikleri

| Feature | Field | Evidence |
|---------|-------|----------|
| TKGM ID | tkgm_id | Model (immutable) |
| Ada | ada | Model (immutable after creation) |
| Parsel | parsel | Model (immutable after creation) |
| Durum | aktiflik_durumu | Model (DRAFT, etc.) |
| UUID | uuid | Auto-generated |

---

## Tenant Isolation

| Check | Status | Evidence |
|-------|--------|----------|
| BelongsToTenant | ✅ YES | Model uses trait |
| Tenant isolation | REPO_VERIFIED | Trait confirmed |

---

## İmmutability Rules

```
Property Creation:
  ✓ tkgm_id can be set
  ✓ ada can be set
  ✓ parsel can be set

After Creation:
  ✗ tkgm_id CANNOT be changed
  ✗ ada CANNOT be changed
  ✗ parsel CANNOT be changed
```

**DomainException:** Thrown if immutable fields are dirty on update

---

## İlan İlişkisi

| Relation | Type | Evidence |
|---------|------|----------|
| Ilan | 1:N | Property hasMany(Ilan::class) |

**Note:** Bir Property birden fazla İlan'a sahip olabilir (farklı dönemler, farklı tipler)

---

## Property Durumları

| State | Meaning | Evidence |
|-------|---------|----------|
| DRAFT | Oluşturuldu, aktif değil | Model |
| (diğerleri) | - | Not fully documented |

---

## TKGM Integration

**tkgm_id:** Tapu Kadastro Genel Müdürlüğü kimlik numarası

**Purpose:** Türkiye tapu sicil numarası

**Immutability:** TKGM ID değiştirilemez (DomainException koruması)

---

## Related Domains

| Domain | Relationship |
|--------|--------------|
| Ilan | hasMany(Ilan::class) |
| Tenant | BelongsToTenant |
| TKGM | tkgm_id (external ID) |

---

## TESTED INVARIANTS

| Invariant | Test | Result |
|----------|------|--------|
| Tenant isolation | REPO_VERIFIED | Trait confirmed |
| TKGM immutability | REPO_VERIFIED | DomainException |

---

## BUSINESS_UNKNOWN

| Question | Why Unknown |
|----------|------------|
| Complete state lifecycle? | Partial |
| TKGM integration flow? | External API exists |
| Property → Reservation? | Not verified |
