# DOMAIN KNOWLEDGE: ILAN YÖNETİMİ

**LAST_VERIFIED_HEAD:** 32236d54
**Evidence Level:** REPO_VERIFIED

---

## Business Concept: İlan Yönetimi

**Tanım:**
- İlan oluşturma, düzenleme, yayınlama
- Wizard tabanlı ilan oluşturma akışı
- Çoklu medya yönetimi

**SOURCE:** IlanCrudController, IlanController, IlanWizard*

---

## İlan Controllers (19 adet)

| Controller | Purpose |
|------------|---------|
| IlanCrudController | CRUD operations |
| IlanDraftController | Taslak yönetimi |
| IlanPublishController | Yayınlama |
| IlanPublishGateController | Yayınlama kontrolü |
| IlanCalendarController | Rezervasyon takvimi |
| IlanCalendarFeedAdminController | Takvim feed |
| IlanPhotoController | Fotoğraf yönetimi |
| IlanFeatureController | Özellik yönetimi |
| IlanKategoriController | Kategori yönetimi |
| IlanBulkController | Toplu işlemler |
| IlanAPI* | API endpoints |
| IlanAI* | AI destekli işlemler |
| IlanAnalizController | Analiz/Raporlama |
| IlanQualityDashboardController | Kalite dashboard |

---

## Ilan CRUD Operations

```
index()     → Liste görüntüleme
create()    → İlan oluşturma formu
store()     → Yeni ilan kaydetme
show()      → İlan detay görüntüleme
edit()      → Düzenleme formu
update()    → İlan güncelleme
destroy()   → İlan silme
restore()   → Silinen ilanı geri alma
archive()   → İlan arşivleme
```

**EVIDENCE:** REPO_VERIFIED

---

## Wizard Workflow

```
Step 1: Kategori seçimi
Step 2: Özellikler/ nitelikler
Step 3: Medya/ fotoğraflar
Step 4: Adres/ konum
Step 5: Önizleme → TASLAK veya YAYINDA
```

**Controller:** IlanCrudController
**View:** admin.ilanlar.create-wizard

**EVIDENCE:** REPO_VERIFIED

---

## State Lifecycle (IlanDurumu)

| State | Public? | Editable? | Meaning |
|-------|---------|-----------|---------|
| TASLAK | ❌ | ✅ | Düzenleme aşamasında |
| BEKLEMEDE | ❌ | ✅ | Onay bekliyor |
| YAYINDA | ✅ | ✅ | Aktif/canlı |
| ARSIV | ❌ | ❌ | Arşivlenmiş |
| PASIF | ❌ | ✅ | Pasif/editing |

---

## İlan Özellikleri

| Feature | Field | Evidence |
|---------|-------|----------|
| Başlık | baslik | Model |
| Açıklama | aciklama | Model |
| Fiyat | fiyat | Model |
| Kategori | category_id | FK |
| Yayın tipi | yayin_tipi_id | FK |
| Danışman | danisman_id | FK |
| Mülk | property_id | FK |

---

## Medya Yönetimi

**Controller:** IlanPhotoController
**Model:** IlanFotografi

**Operations:**
- Fotoğraf yükleme
- Fotoğraf sıralama
- Ana fotoğraf seçimi
- Fotoğraf silme

---

## AI Desteği

| Controller | AI Service |
|------------|-----------|
| IlanAIQualityController | Kalite kontrol |
| IlanAITitleDescriptionController | Başlık/açıklama |

**EVIDENCE:** REPO_VERIFIED

---

## TENANT BOUNDARY

| Check | Required | Evidence |
|-------|----------|----------|
| BelongsToTenant | YES | Ilan model |
| Tenant isolation | TEST_VERIFIED | IlanFeaturePivotTest |

---

## Related Domains

| Domain | Relationship |
|--------|--------------|
| Reservation | Takvim/tarih çakışma kontrolü |
| Category | İlan kategorisi |
| Property | Mülk binding |
| Photo | Medya yönetimi |
| Danışman | Atama |

---

## TESTED INVARIANTS

| Invariant | Test | Result |
|----------|------|--------|
| Tenant isolation | IlanFeaturePivotTest | PASS |

---

## BUSINESS_UNKNOWN

| Question | Why Unknown |
|----------|------------|
| Bulk operations details? | Partial |
| Publish gate criteria? | Partial |
| Quality scoring algorithm? | AI service exists |
