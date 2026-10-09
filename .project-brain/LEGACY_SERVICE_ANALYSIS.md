# Core Service Analiz Raporu

**Tarih:** 2026-10-09
**Konu:** 11 Core Service kullanim analizi

---

## 1. Kullanim Ozeti

| Service | Kullanan | Durum |
|---------|---------|-------|
| IlanService | 9 dosya | ✅ AKTIF |
| FinancialLedgerService | 9 dosya | ✅ AKTIF |
| TemplateResolver | 25 dosya | ✅ AKTIF |
| AdminSettingsCacheService | 6 dosya | ✅ AKTIF |
| LeadAuthorityService | 5 dosya | ✅ AKTIF |
| LeadService | 5 dosya | ✅ AKTIF |
| FieldResolver | 3 dosya | ✅ AKTIF |
| IlanBulkService | 3 dosya | ✅ AKTIF |
| PropertyTemplateGeneratorService | 2 dosya | ✅ AKTIF |
| WhatsAppNotificationManager | 2 dosya | ✅ AKTIF |
| IletisimService | 1 dosya | ✅ AKTIF |

---

## 2. Detayli Analiz

### IlanService (9 kullanim)
**Konum:** `app/Services/IlanService.php`
**Kullanim:** IlanRepository, OwnerIlanController, IlanCrudController, MyListingsController
**Durum:** AKTIF — Property Hub ile paralel kullaniliyor
**Yerine:** IlanCrudService (yeni) — tam gecis yapilmamis

### FinancialLedgerService (9 kullanim)
**Konum:** `app/Services/FinancialLedgerService.php`
**Kullanim:** LedgerController, ProcessFinancialCompletionJob, ProcessReservationCancelled, ProcessReservationCreated
**Durum:** AKTIF — Finansal islemler icin kritik
**Yerine:** Yok — hala gerekli

### TemplateResolver (25 kullanim)
**Konum:** `app/Contracts/TemplateResolverInterface.php`
**Kullanim:** AppServiceProvider, TemplateServiceProvider, YayinTipi, IlanPublishGateController
**Durum:** AKTIF — Core domain logic
**Yerine:** YOK — domain paylasiliyor

### AdminSettingsCacheService (6 kullanim)
**Konum:** `app/Services/AdminSettingsCacheService.php`
**Kullanim:** AdminController, UpsFeatureWhitelistController, BlogController, StoreFeatureWhitelistAction, UpdateFeatureWhitelistAction
**Durum:** AKTIF — Admin ayarlari icin kullaniliyor
**Yerine:** Cache/Redis alternatif olabilir ama gerekmez

### LeadAuthorityService (5 kullanim)
**Konum:** `app/Services/LeadAuthorityService.php`
**Kullanim:** LeadRepository, LeadController, LeadService, AgentAssignmentService, LeadScoringService
**Durum:** AKTIF — CRM lead yonetimi icin kritik
**Yerine:** CRM/LeadAuthorityService (yeni path var)

### LeadService (5 kullanim)
**Konum:** `app/Services/LeadService.php`
**Kullanim:** MobileLeadController, WhatsAppWebhookController, InstagramWebhookController, FacebookWebhookController, LeadAuthorityService
**Durum:** AKTIF — Lead toplama icin kritik
**Yerine:** Yok — webhooks hala bu service'i kullanmali

### FieldResolver (3 kullanim)
**Konum:** `app/Services/Wizard/FieldEngine/FieldDefinition.php`
**Kullanim:** WizardFeatureController, DomainFieldResolverAdapter
**Durum:** AKTIF — Wizard field resolvers
**Yerine:** DomainFieldResolverAdapter (wrapper var)

### IlanBulkService (3 kullanim)
**Konum:** `app/Services/IlanBulkService.php`
**Kullanim:** IlanBulkController, BulkManagementController
**Durum:** AKTIF — Bulk ilan islemleri
**Yerine:** Yok — bulk operations hala gerekli

### PropertyTemplateGeneratorService (2 kullanim)
**Konum:** `app/Services/PropertyType/PropertyTemplateGeneratorService.php`
**Kullanim:** LegacyGeneratorGuard, PropertyHubOrchestrator
**Durum:** AKTIF — Property type generation
**Yerine:** PropertyHubOrchestrator yeni alternatif

### WhatsAppNotificationManager (2 kullanim)
**Konum:** `app/Services/WhatsAppNotificationManager.php`
**Kullanim:** SendWhatsAppMessageJob, NotifyLeadsOnNewListing
**Durum:** AKTIF — WhatsApp bildirimleri
**Yerine:** Yok — bildirim sistemi hala gerekli

### IletisimService (1 kullanim)
**Konum:** `app/Services/IletisimService.php`
**Kullanim:** RaporuPaylas listener
**Durum:** AKTIF — Rapor paylasma
**Yerine:** RaporuPaylas listener disinda kullanilmiyor

---

## 3. Mimari Durum

### Hicbiri Gercekten DEPRECATED Degil
Bu service'ler "legacy" olarak adlandirilmis ama:
- Hicbiri @deprecated ile isaretlenmemis
- Hepsi aktif olarak kullaniliyor
- Hepsinin yerini alan yeni service mevcut degil

### Dogru Adlandirma
"Core Service" etiketi daha dogru:
- Bu service'ler aktif domain logic
- Farkli naming convention kullaniyorlar
- Gelecekte yeniden duzenlenebilir

---

## 4. Sonuc

| Service | Silinebilir | Neden |
|---------|-------------|-------|
| IlanService | ❌ Hayir | 9 dosya kullaniyor |
| FinancialLedgerService | ❌ Hayir | Finansal kritik |
| TemplateResolver | ❌ Hayir | 25 dosya kullaniyor |
| AdminSettingsCacheService | ❌ Hayir | Admin ayarlari |
| LeadAuthorityService | ❌ Hayir | CRM kritik |
| LeadService | ❌ Hayir | Webhook'lar |
| FieldResolver | ❌ Hayir | Wizard logic |
| IlanBulkService | ❌ Hayir | Bulk islemler |
| PropertyTemplateGeneratorService | ❌ Hayir | Property generation |
| WhatsAppNotificationManager | ❌ Hayir | Bildirimler |
| IletisimService | ❌ Hayir | Rapor paylasma |

**HICBIRI SILINMEMELI** — Tum service'ler aktif kullanimda.

---

## 5. Oneriler

1. **"Core Service" etiketi** — aktif domain logic
3. **Naming convention iyilestirmesi** — IlanService yerine IlanDomainService
4. **Tam gecis analizi** — IlanService -> IlanCrudService gecisi planlanabilir

---

## Rapor Durumu
**Analiz tamam — silme onermedi.**
