# İlan Oluşturma Wizard — Teknik Akış Belgeleri

> Kritik Zincir: WizardContextController → WizardOrchestrator → FeatureTemplateResolver → EffectiveWizardSchemaResolver
> Son güncelleme: 2026-06-16

---

## Wizard Zinciri (SSOT)

```
HTTP Request
    ↓
WizardContextController          (routes/api/v1/ilan-wizard.php)
    ↓
WizardOrchestrator               (app/Services/Wizard/WizardOrchestrator.php)
    ↓
FeatureTemplateResolver (SSOT)   (app/Domain/PropertyHub/FeatureTemplateResolver.php)
    ↓
EffectiveWizardSchemaResolver    (app/Services/Wizard/EffectiveWizardSchemaResolver.php)
    ↓
WizardDraftService               (app/Services/Wizard/WizardDraftService.php)
    ↓
IlanRepository (write)           (app/Repositories/IlanRepository.php)
```

---

## Wizard Adımları

| Adım | Endpoint | Açıklama |
|------|---------|----------|
| 1 | `POST /api/v1/wizard/context` | Kategori + yayın tipi seçimi, şema yükleme |
| 2 | `POST /api/v1/wizard/draft` | Taslak oluşturma / auto-save |
| 3 | `PUT /api/v1/wizard/draft/{id}` | Alanları güncelleme (3sn debounce) |
| 4 | `POST /api/v1/wizard/draft/{id}/ai-enrich` | AI başlık/açıklama üretimi |
| 5 | `POST /api/v1/wizard/draft/{id}/publish` | Yayınlama (ListingStateMachine tetiklenir) |

---

## Şema Yükleme Mantığı

```
FeatureTemplateResolver
    ├── Ana kategori (ana_kategori_id)
    ├── Alt kategori (alt_kategori_id)
    ├── Yayın tipi (yayin_tipi_id)
    └── kategori_yayin_tipi_field_dependencies tablosu
            ↓
    Alan tanımları: FieldDefinition[]
            ↓
    EffectiveWizardSchemaResolver → birleşik şema
            ↓
    Frontend Alpine.js schema-field-renderer.js
```

**SSOT:** Alan tanımları veritabanından (`kategori_yayin_tipi_field_dependencies`) yönetilir. Hardcoded alan yok.

---

## Taslak Sistemi

- **Tablo:** `ilan_taslaklar`
- **Servis:** [`WizardDraftService`](../../app/Services/Wizard/WizardDraftService.php)
- **Özellikler:**
  - Oturum bağımsız (kullanıcı oturumu kapansa da taslak korunur)
  - 3 saniye debounced auto-save
  - Tenant-isolated (cross-tenant erişim imkansız)

---

## AI Entegrasyonu

```
WizardOrchestrator
    ↓
YalihanCortex::enrichDraft()
    ├── OllamaService (birincil — yerel)
    ├── DeepSeekService (fallback — deepseek-chat)
    └── OpenAIService (son çare)
            ↓
    AiBudgetGuard (kredi kontrol)
            ↓
    cortexScore hesapla (0-100)
```

**Skor kuralı:**
- `cortexScore = 0` → Taslak kaydet (her zaman aktif)
- `cortexScore < 40` → Düşük skorla kaydet (sarı uyarı)
- `cortexScore >= 40` → Yayınla (yeşil)

---

## Kritik Kurallar

| Kural | Açıklama |
|-------|----------|
| `FeatureTemplateResolver` dokunma | SSOT — tüm şema buradan gelir |
| AI bloke etmez | `cortexScore=0` → taslak kayıt her zaman aktif |
| Cross-tenant yasak | Her wizard işlemi `tenant_id` ile scope'lu |
| Write: sadece `IlanRepository` | Controller/Orchestrator doğrudan model yazamaz |

---

## İlgili Dosyalar

| Dosya | Rol |
|-------|-----|
| [`app/Http/Controllers/Api/V1/WizardContextController.php`](../../app/Http/Controllers/Api/V1/WizardContextController.php) | HTTP giriş noktası |
| [`app/Services/Wizard/WizardOrchestrator.php`](../../app/Services/Wizard/WizardOrchestrator.php) | Orkestrasyon |
| [`app/Domain/PropertyHub/FeatureTemplateResolver.php`](../../app/Domain/PropertyHub/FeatureTemplateResolver.php) | Şema SSOT |
| [`app/Services/Wizard/EffectiveWizardSchemaResolver.php`](../../app/Services/Wizard/EffectiveWizardSchemaResolver.php) | Birleşik şema çözücü |
| [`app/Services/Wizard/WizardDraftService.php`](../../app/Services/Wizard/WizardDraftService.php) | Taslak persistence |
| [`routes/api/v1/ilan-wizard.php`](../../routes/api/v1/ilan-wizard.php) | Route tanımları |
| [`docs/features/LISTING_LIFECYCLE.md`](./LISTING_LIFECYCLE.md) | Durum makinesi |
