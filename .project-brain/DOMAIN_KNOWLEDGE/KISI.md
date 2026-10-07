# DOMAIN KNOWLEDGE: KISI (CONTACT)

**LAST_VERIFIED_HEAD:** 32236d54
**Evidence Level:** REPO_VERIFIED + TEST_VERIFIED

---

## Business Concept: Kişi (Contact)

**Tanım:**
- Yalıhan Emlak müşterileri ve potansiyel müşteriler
- CRM süreç takibi
- Danışman ataması

**SOURCE:** Kisi model, KisiController, KisiDurumu enum

---

## Canonical Authority

| Concept | Authority | Evidence |
|---------|-----------|----------|
| State enum | KisiDurumu (7 states) | app/Enums/KisiDurumu.php |
| CRM süreç | crm_surec_asamasi | Model |
| Tenant | tenant_id | BelongsToTenant |

---

## State Lifecycle (KisiDurumu)

| State | isUrgent | Label | Description |
|-------|----------|-------|-------------|
| SICAK | ✅ | Sıcak | Yüksek potansiyel, aktif ilgilenen |
| ILGILI | ✅ | İlgili | İlgileniyor, takip edilmeli |
| TAKIPTE | ❌ | Takipte | Aktif görüşme sürecinde |
| SOGUK | ❌ | Soğuk | Düşük ilgi |
| PASIF | ❌ | Pasif | Aktif takip edilmiyor |
| POTANSIYEL | ❌ | Potansiyel | Gelecek vaat eden |
| ISLEMYAPMIS | ❌ | İşlem Yapmış | Daha önce işlem yapmış |

**EVIDENCE:** KisiDurumu enum

---

## Kişi Özellikleri

| Feature | Field | Evidence |
|---------|-------|----------|
| Ad/ Soyad | ad, soyad | Model |
| Telefon | telefon | Model |
| E-posta | eposta | Model |
| Adres | il_id, ilce_id, mahalle_id | Relations |
| Referans | referans_kisi_id | Self-ref |
| Ülke | ulke_id | Relation |

---

## Relations

| Relation | Target | Type | Evidence |
|---------|--------|------|----------|
| Talep | 1:N | hasMany | Kisi hasMany(Talep::class) |
| Danışman | N:1 | belongsTo | danisman_id FK |
| Ilan Favori | M:N | belongsToMany | ilan_favorileri pivot |
| Etkileşimler | 1:N | hasMany | Kisi hasMany(KisiEtkilesim::class) |
| Referans | Self | N:1 | referans_kisi_id FK |
| İlan (ilgilenen) | 1:N | hasMany | user_id, ilgili_kisi_id |
| Etiketler | M:N | belongsToMany | etiket_kisi pivot |

---

## Etkileşim (KisiEtkilesim)

**Table:** kisi_etkilesimler

**Purpose:** Kişi ile yapılan görüşme/temas kayıtları

**Relations:**
- kisi_id → Kişi
- kullanici_id → User (danışman)

**EVIDENCE:** REPO_VERIFIED

---

## Etiket Sistemi

**Pivot:** etiket_kisi

**Purpose:** Kişi segmentasyonu

**EVIDENCE:** belongsToMany(Etiket::class)

---

## TENANT BOUNDARY

| Check | Required | Evidence |
|-------|----------|----------|
| BelongsToTenant | YES | Model |
| Tenant isolation | TEST_VERIFIED | KisiDurumuIndependentVerificationTest |

---

## Related Domains

| Domain | Relationship |
|--------|--------------|
| Danışman | Kişi atanır, takip eder |
| Talep | Kişi talep oluşturur |
| Ilan | Kişi favorilere ekler |
| Reservation | guest_name/guest_phone (guest info) |

---

## TESTED INVARIANTS

| Invariant | Test | Result |
|----------|------|--------|
| Tenant isolation | KisiDurumuIndependentVerificationTest | PASS |

---

## BUSINESS_UNKNOWN

| Question | Why Unknown |
|----------|------------|
| Automatic state transitions? | Not verified |
| Lead scoring calculation? | KisiScoringService exists |
| Interaction templates? | Not documented |
