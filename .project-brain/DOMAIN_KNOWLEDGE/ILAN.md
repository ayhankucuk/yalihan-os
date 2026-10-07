# DOMAIN KNOWLEDGE: ILAN (LISTING)

**LAST_VERIFIED_HEAD:** 863a1018
**Evidence Level:** REPO_VERIFIED + TEST_VERIFIED

---

## Business Concept: İlan / Listing

**Tanım:**
- Bir mülkün public veya draft sunumu
- Yalıhan Emlak'ın satış/kiralama ilanları
- Tenant context'inde yönetilir

**SOURCE:** Ilan model + IlanDurumu enum

---

## Canonical Authority

| Concept | Authority | Evidence |
|---------|-----------|----------|
| State enum | IlanDurumu (5 states) | app/Enums/IlanDurumu.php |
| Date fields | yayin_tarihi, gizleme_tarihi | Model |
| Tenant | tenant_id | BelongsToTenant |

**STATE_LOCKDOWN:** SAB Lockdown annotation (direct mutation blocked)

---

## State Lifecycle

```
TASLAK → BEKLEMEDE → YAYINDA
                     ↘ ARSIV
                     ↘ PASIF
```

**STATES:**

| State | isActive() | isPublic() | isEditable() | Meaning |
|-------|-----------|-----------|-------------|---------|
| TASLAK | ❌ | ❌ | ✅ | Draft/editing |
| BEKLEMEDE | ❌ | ❌ | ✅ | Awaiting approval |
| YAYINDA | ✅ | ✅ | ✅ | Live/public |
| ARSIV | ❌ | ❌ | ❌ | Archived/completed |
| PASIF | ❌ | ❌ | ✅ | Inactive/paused |

**EVIDENCE:** IlanDurumu enum predicates

---

## WHO Can Create?

**Business:** Admin/Danisman users
**Technical:** Tenant context required

**EVIDENCE:** IlanController auth + BelongsToTenant

**UNKNOWN:** Owner self-service? → UNKNOWN

---

## State Transition Rules

| From | To | Precondition | Evidence |
|------|-----|-------------|----------|
| TASLAK | BEKLEMEDE | Wizard complete | Controller flow |
| BEKLEMEDE | YAYINDA | Admin approval | IlanYayinDurumuManagement |
| YAYINDA | ARSIV | Sale/rental complete | Lifecycle service |
| YAYINDA | PASIF | Manual | Lifecycle service |

**CANONICAL_TRANSITION:** IlanYayinDurumuManagement trait

**EVIDENCE:** REPO_VERIFIED (trait code)

---

## Public Visibility

| State | Public API | Search | Calendar |
|-------|-----------|--------|----------|
| TASLAK | ❌ | ❌ | ❌ |
| BEKLEMEDE | ❌ | ❌ | ❌ |
| YAYINDA | ✅ | ✅ | ✅ |
| ARSIV | ❌ | ❌ | ❌ |
| PASIF | ❌ | ❌ | ❌ |

**EVIDENCE:** isPublic() predicate

---

## TENANT BOUNDARY

| Check | Required | Evidence |
|-------|----------|----------|
| BelongsToTenant trait | YES | Model |
| Tenant isolation | TEST_VERIFIED | IlanFeaturePivotTest |

**CROSS_TENANT_ACCESS:** BLOCKED

---

## AUTHORIZATION

| Role | Can Create | Can Edit | Can Publish |
|------|-----------|---------|------------|
| admin | YES | YES | YES |
| danisman | YES | YES | Limited |
| viewer | ❌ | ❌ | ❌ |

**EVIDENCE:** IlanPolicy + Controller auth

---

## Related Domains

| Domain | Relationship |
|--------|--------------|
| Property | property_id binding |
| Reservation | Ilan through property_id |
| Photo | IlanFotografi (1:N) |
| Category | category_id FK |
| CRM/Talep | talep.ilan_id FK |

**EVIDENCE:** ARCHITECTURE_MAP dependencies

---

## Wizard Workflow

```
Step 1: Category selection
Step 2: Features/attributes
Step 3: Media/photos
Step 4: Address/location
Step 5: Preview → BEKLEMEDE
```

**EVIDENCE:** IlanController wizard flow

---

## TESTED INVARIANTS

| Invariant | Test | Result |
|----------|------|--------|
| Tenant isolation | IlanFeaturePivotTest | PASS |
| State transitions | Lifecycle management tests | REPO_VERIFIED |

---

## BUSINESS_UNKNOWN

| Question | Why Unknown |
|----------|------------|
| Owner self-service listing? | Not found in current code |
| Approval workflow details? | Partial (BEKLEMEDE exists) |
| ARSIV meaning (sold/rented)? | State exists, business meaning unclear |
| Automatic state transitions? | Manual transitions observed |

---

## Business Value Chain

```
Yalıhan Emlak → İlan → Rezervasyon
                    ↘ Satış/Kiralama
                    
Müşteri → Talep → Eşleşme → İlan → Rezervasyon
```

**EVIDENCE:** ARCHITECTURE_MAP
