# DOMAIN KNOWLEDGE: CRM (KIŞI + TALEP)

**LAST_VERIFIED_HEAD:** e18b890e
**Evidence Level:** REPO_VERIFIED

---

## Business Concept: CRM

**Tanım:**
- Kişi ve Talep yönetimi
- Müşteri ilişkileri takibi
- Satış pipeline'ı

**SOURCE:** Kisi model + Talep model + KisiDurumu enum

---

## Sub-Domains

1. **Kişi** - Kişi/Contact yönetimi
2. **Talep** - Talep/Demand yönetimi
3. **Eşleşme** - Matching - Ilan ile eşleştirme

---

# KIŞI (CONTACT)

## Canonical Authority

| Concept | Authority | Evidence |
|---------|-----------|----------|
| State enum | KisiDurumu (7 states) | app/Enums/KisiDurumu.php |
| Tenant | tenant_id | BelongsToTenant |
| Location | il_id, ilce_id, mahalle_id | Model relations |

---

## State Lifecycle (KisiDurumu)

| State | isUrgent | Meaning | Icon |
|-------|----------|---------|------|
| SICAK | ✅ | Yüksek potansiyel, aktif ilgilenen | 🔥 |
| ILGILI | ✅ | İlgileniyor, takip edilmeli | 👀 |
| TAKIPTE | ❌ | Aktif görüşme sürecinde | 📞 |
| SOGUK | ❌ | Düşük ilgi | ❄️ |
| PASIF | ❌ | Aktif takip edilmiyor | 😴 |
| POTANSIYEL | ❌ | Gelecek vaat eden | 💡 |
| ISLEMYAPMIS | ❌ | Daha önce işlem yapmış | 🤝 |

**EVIDENCE:** KisiDurumu enum

---

## WHO Can Create?

**Business:** Admin/Danisman users
**Technical:** Tenant context required

**EVIDENCE:** KisiController auth + BelongsToTenant

---

## TENANT BOUNDARY

| Check | Required | Evidence |
|-------|----------|----------|
| BelongsToTenant trait | YES | Model |
| ScopeByTenant | YES | scopeByTenant() |

---

## Relations

| Relation | Target | Evidence |
|---------|--------|----------|
| Talep | 1:N | hasMany(Talep::class) |
| User | N:1 | danisman_id FK |
| Ilan | M:N | ilan_favorileri pivot |
| Kisi (referans) | Self-ref | referans_kisi_id FK |

---

# TALEP (DEMAND)

## Canonical Authority

| Concept | Authority | Evidence |
|---------|-----------|----------|
| Tenant | tenant_id | BelongsToTenant |
| Kisi binding | kisi_id FK | Model |
| Ilan binding | ilan_id FK | Model |

---

## WHO Can Create?

**Business:** Admin/Danisman users
**Technical:** Tenant context required

**EVIDENCE:** TalepController auth + BelongsToTenant

---

## State Lifecycle

| State | Meaning | Evidence |
|-------|---------|----------|
| BEKLEMEDE | Beklemede | Model |
| AKTIF | Aktif | Model |
| KAPALI | Kapalı/Tamamlandı | Model |

**UNKNOWN:** Complete state transition rules → UNKNOWN

---

## Relations

| Relation | Target | Evidence |
|---------|--------|----------|
| Kisi | N:1 | belongsTo(Kisi::class) |
| Ilan | N:1 | belongsTo(Ilan::class) |
| User (danisman) | N:1 | danisman_id FK |

---

# EŞLEŞTİRME (MATCHING)

## Canonical Authority

| Concept | Authority | Evidence |
|---------|-----------|----------|
| Matching service | DemandMatchingEngine | app/Services/CRM/Matching/ |
| Talep → Ilan | Core logic | MatchingAuthorityService |

---

## Matching Flow

```
Talep → Matching criteria
        ↓
DemandMatchingEngine → Ilan matching
        ↓
Score calculation
        ↓
Recommended Ilanlar
```

**EVIDENCE:** REPO_VERIFIED (service code)

---

## TESTED INVARIANTS

| Invariant | Test | Result |
|----------|------|--------|
| Kisi tenant isolation | KisiDurumuTest | PASS |
| Talep tenant isolation | TalepStoreContractTest | PASS |

---

## BUSINESS VALUE CHAIN

```
Kisi (lead/müşteri)
  → Talep (requirement)
      → Matching (ilan eşleştirme)
          → Ilan (property)
              → Rezervasyon/Satış
```

---

## BUSINESS_UNKNOWN

| Question | Why Unknown |
|----------|------------|
| Talep state transition rules? | Partial code observed |
| Automatic Kisi state updates? | Not verified |
| Matching algorithm details? | Not fully documented |
| Lead scoring formula? | Scoring service exists |

---

## Related Domains

| Domain | Relationship |
|--------|--------------|
| Ilan | talep.ilan_id FK, matching |
| Reservation | guest info (guest_name, guest_phone) |
| Tenant | Kisi + Talep tenant isolation |
| Notification | KisiEtkilesim tracking |

**EVIDENCE:** ARCHITECTURE_MAP
