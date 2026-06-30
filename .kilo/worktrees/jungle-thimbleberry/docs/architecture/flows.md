# Yalıhan Emlak — İş Akışları (Flow SSOT)

> ⚠️ **Authority Rule:** Bu doküman READ-ONLY sistem yansımasıdır.
> Source of truth = code + DB + config. Kod ile doküman çelişirse → kod kazanır.
>
> Son Güncelleme: 2026-04-11
> Kaynak: `app/Services/Wizard/`, `app/Services/Governance/`, `app/Services/Feature/`, routes

---

## Akış İndeksi

| # | Akış | Risk Seviyesi | Bağlı Domain |
|---|------|---------------|-------------|
| 1 | Wizard (İlan Oluşturma) | 🔴 HIGH | Property + Feature/Template |
| 2 | Template Resolution | 🔴 HIGH | Feature/Template |
| 3 | AI Suggestion | 🟡 MEDIUM | AI + Feature/Template |
| 4 | Governance Decision | 🟡 MEDIUM | Governance + AI |
| 5 | Listing Publish | 🟡 MEDIUM | Property |
| 6 | CRM Pipeline | 🟢 LOW | CRM |
| 7 | Matching (Eşleştirme) | 🟡 MEDIUM | CRM + AI |
| 8 | Autonomy Control | 🔴 HIGH | Governance |

---

## 1. Wizard Flow (İlan Oluşturma)

```
UI (create-wizard)
  → IlanCrudController::createWizard
    → WizardOrchestrator
      → Step 1: Lokasyon (Canonical SSOT — Il/Ilce/Mahalle)
      → Step 2: Kategori + Yayın Tipi seçimi
        → FeatureTemplateResolver (Bu akış #2'ye dallanır)
        → DB: feature_assignments, yayin_tipi_sablonlari
      → Step 3: Detay + Fiyat + Fotoğraf
        → WizardContextService (context build)
        → WizardGateService (validation gates)
        → AI: WizardAIAssistantService (opsiyonel)
        → AI: ListingSmartSuggestionService (Bu akış #3'e dallanır)
    → IlanCrudController::store
      → Draft → Persist → Redirect
```

### Bağlı Servisler
- `WizardOrchestrator` — Ana orkestratör (legacy, basit delegasyon)
- `WizardContextService` — Wizard context build (11K satır)
- `FeatureTemplateResolver` — Template çözümleme (12K satır)
- `WizardDraftService` — Draft kaydet/yükle
- `WizardGateService` — Validation gate'leri
- `WizardAIAssistantService` — AI asistan (opsiyonel)
- `EffectiveWizardSchemaResolver` — Wizard şema çözümleme
- `EffectiveListingTypeResolver` — Yayın tipi çözümleme
- `DynamicFieldValueMapper` / `DynamicFieldValueHydrator` — Dinamik alan işleme

### ⚠️ Riskler
1. **NULL scope → 0 feature:** FeatureTemplateResolver global (NULL) scope bulamazsa wizard boş döner
2. **Draft çakışma:** Aynı kullanıcı birden fazla taslak oluşturabilir — son yazılan kazanır
3. **Kategori değişikliği:** Step 2'de kategori değiştirilirse mevcut özellikler temizlenmeli

---

## 2. Template Resolution Flow

```
FeatureTemplateResolver::resolve(kategoriId, yayinTipiId)
  → 1. Exact Match: kategori_id + yayin_tipi_id TAM eşleşme
  → 2. Fallback A: alt_kategori → ana_kategori üzerinden arama
  → 3. Fallback B: sadece yayin_tipi_id ile arama (kategori NULL)
  → 4. Fallback C: global scope (kategori NULL + yayin_tipi NULL)
  → 5. Sonuç: Feature listesi + metadata
```

### Kurallar (CONTRIBUTING.md'den)
- **Junction-First:** Template çözümleme daima junction üzerinden yapılır, doğrudan slug lookup değil
- **Deterministic:** Her `first()` çağrısı `orderBy('id')` içermelidir
- **Slug primary selector olamaz:** `where('slug', $slug)` yerine `where('slug', $slug)->where('aktiflik_durumu', true)->orderBy('id')->first()`

### ⚠️ Riskler
1. **NULL scope unutulursa:** Wizard 0 feature döndürür — en yaygın bug kaynağı
2. **Orphan template:** Kategori silinir ama template kalırsa yetim veri oluşur
3. **Inheritance kırılması:** Alt kategori override uygulanır ama ana kategori template'i değişirse senkron bozulur

---

## 3. AI Suggestion Flow

```
ListingSmartSuggestionService::suggest(ilan)
  → 1. Normalize: Mevcut ilan verisi normalize edilir
  → 2. Rule Suggest: Kural bazlı öneriler üretilir
  → 3. AI Suggest: LLM API çağrısı (OpenAI / Ollama)
  → 4. Consistency Warn: Tutarsızlık uyarıları
  → 5. Filter & Package: Sonuçlar filtrelenir ve paketlenir
  → UI'a döner (Accept / Reject butonları)
```

### AI Field Suggestion (Governance Panel)
```
FieldSuggestionController
  → generate: AiFieldSuggestionEngine ile öneri üret
  → index: Önerileri listele
  → approve: Öneriyi onayla → FieldSuggestionScorer güncelle
  → reject: Öneriyi reddet
  → apply: Onaylı öneriyi ilana uygula
  → rollback: Uygulanmış öneriyi geri al
```

### Kurallar
- **Auto-save YASAK:** AI önerileri kesinlikle otomatik kaydedilmez
- **Kullanıcı onayı zorunlu:** Apply butonu ile açık onay gerekir
- **Telemetri:** Accept/Reject decision flywheel'a girdi üretir (`SmartSuggestionTelemetryService`)
- **Rate limiting:** AI endpoint'leri throttle'lıdır (10-20 req/min/user)

### ⚠️ Riskler
1. **Provider kesintisi:** AI provider (OpenAI) response vermezse graceful fallback gerekir
2. **Maliyet kontrolü:** Her AI çağrısı token harcaması yapar — telemetry ile izlenmeli
3. **Hallucination:** AI yanlış özellik önerebilir — field validation gerekli

---

## 4. Governance Decision Flow

```
Karar Üretimi:
  GovernanceService / DecisionEngineController::scan
    → AI veya kural tabanlı karar üretilir
    → GovernanceDecision::create (status: pending)

Karar İnceleme:
  DecisionEngineController::reviewQueue
    → Bekleyen kararlar listelenir
    → show: Tekil karar detayı

Karar Aksiyonu:
  → approve: Karar onaylanır → GovernanceAuditLog
  → reject: Karar reddedilir → GovernanceAuditLog
  → rollback: Uygulanan karar geri alınır → GovernanceRollback
  → suppress: Karar bastırılır → GovernanceSuppression
  → override: Karar override edilir

Feedback Loop:
  → record-result: Karar sonucu kaydedilir
  → feedback: Geribildirim eklenir
  → simulate: Karar simüle edilir (dry run)

Otonom Kontrol:
  → autonomyPanel: Otonom seviye görüntüleme
  → updateAutonomyLevel: Seviye güncelleme
  → pauseSystem / resumeSystem: Sistem durdurma/devam ettirme
  → toggleDryRun: Dry-run modu
  → updateActionBudget: Aksiyon bütçesi
```

### Kurallar
- **GovernanceTransitionGuard:** Geçiş kurallarını kontrol eder
- **EloquentGovernanceAuditLogger:** Her aksiyon audit log'lanır
- **Multi-Agent Intelligence Center:** Birden fazla AI ajanın önerilerini koordine eder

### ⚠️ Riskler
1. **Yetkisiz onay:** Permission kontrolü atlanırsa kritik kararlar onaysız uygulanabilir
2. **Rollback kaybı:** Rollback verisi yoksa geri alma imkansız
3. **Otonom aşırı yetki:** Autonomy level yüksek ayarlanırsa AI bağımsız aksiyon alabilir

---

## 5. Listing Publish Flow

```
Draft → Validate → PublishGate → Quality Score → Publish

Adımlar:
  IlanDraftController::save → Draft kaydedilir
  IlanCrudController::store → İlan oluşturulur (yayin_durumu: taslak)
  IlanPublishController → Yayın gate kontrolleri
  IlanPublishGateController → Quality gate:
    → Minimum veri kontrolü (fiyat, m2, lokasyon)
    → Duplicate guard (fingerprint kontrolü)
    → Feature completeness kontrolü
  IlanCrudController::update → yayin_durumu: yayinda
  ListingStateTransition → Geçiş kaydedilir
```

### Lifecycle State Machine
```
taslak → beklemede → yayinda → arsiv
                  → pasif
                  → taslak (geri dönüş)
```

### Kurallar (platform-architecture-consolidation.md'den)
- **Create akışı varsayılan `taslak`** destekler
- **Draft/incomplete listing full intelligence compute çalıştırmaz**
- **Yalnızca uygun lifecycle + minimum veri sağlandığında quality → market context → comparable → opportunity zinciri tam çalışır**
- **Duplicate Guard:** Hard block yerine suspicion-based sistem kullanılır

### Critical 5 Data Fields
Her yayınlanacak ilanda zorunlu minimum:
1. `fiyat`
2. `effective_m2` (net_m2 > brut_m2 fallback)
3. `room_group` (normalize: studio, 1+1, 2+1, 3+1, 4+1+, unknown)
4. `il_id` / `ilce_id` / `mahalle_id`
5. `yayin_durumu`

### ⚠️ Riskler
1. **Sparse draft merge:** Taslak verisi mevcut ilan ile agresif merge edilmemeli
2. **Duplicate false positive:** Fingerprint çakışması → review_required flag'i
3. **Downstream noise:** Draft güncelleme gereksiz intelligence refresh tetiklememeli

---

## 6. CRM Pipeline Flow

```
Lead Gelişi:
  → Web form / Import / Manuel giriş
    → Lead::create veya Kisi::create
    → CRM pipeline stage: prospect

Pipeline İlerlemesi:
  PipelineController::updateStage
    → Kişi pipeline stage güncellenir (sürükle-bırak Kanban)
    → Activity kaydı oluşturulur
    → Quick note eklenebilir

Aktivite Takibi:
  ActivityController
    → storeActivity: Aktivite kaydı (çağrı, görüşme, not vb.)
    → getActivities: Aktivite timeline
    → getActivityStats: İstatistikler

Segment Yönetimi:
  CRMDashboardController
    → updateSegment: Müşteri segmenti güncelleme
    → recalculateScores: Scoring yeniden hesaplama
```

### ⚠️ Riskler
1. **Orphan lead:** Pipeline'dan düşen lead takibi yapılmazsa kayıp
2. **Score staleness:** Scoring yeniden hesaplanmazsa eski veriye göre karar

---

## 7. Matching Flow (Eşleştirme)

```
Talep-İlan Eşleştirme:
  EslesmeController
    → autoMatch: Otomatik eşleştirme
      → SmartPropertyMatcherAI::match(talep)
        → Lokasyon kontrolü (il/ilçe)
        → Fiyat aralığı kontrolü
        → Özellik eşleşme skoru
        → AI scoring (>80: Yüksek, >60: Orta, <60: Düşük)
      → Eslesme::create (sonuç kaydı)

Geri Bildirim:
  MatchingFeedbackController
    → store: Geri bildirim (accepted/rejected)
    → markResult: Sonuç işaretleme
    → Decision Flywheel'a girdi

Danışman AI Eşleştirme:
  DanismanAIController
    → smartDemandSearch: Akıllı talep arama
    → batchAnalysis: Toplu analiz
    → quickAISuggestions: Hızlı öneri
```

### ⚠️ Riskler
1. **False positive eşleşme:** AI skoru yüksek ama gerçekte uyumsuz → feedback loop ile düzeltilir
2. **Rate limiting:** AI eşleştirme throttle'lı (10 req/min) — toplu analiz kuyruğa alınır

---

## 8. Autonomy Control Flow

```
Seviye Hierarchy:
  Level 0: MANUAL — Tüm kararlar onay bekler
  Level 1: SUPERVISED — Düşük riskli kararlar otomatik
  Level 2: SEMI-AUTONOMOUS — Orta riskli kararlar otomatik, yüksek risk onay
  Level 3: AUTONOMOUS — Tüm kararlar bütçe dahilinde otomatik

Kontrol Mekanizmaları:
  → Safe Mode: Tüm otomatik aksiyonlar durdurulur
  → Dry Run: Aksiyonlar simüle edilir, uygulanmaz
  → Action Budget: Günlük aksiyon limiti
  → Pause/Resume: Sistem durdurma/devam ettirme

Aksiyon Döngüsü:
  Decision → (Autonomy Check) → Execute / Queue → Result → Feedback → Learning
```

### ⚠️ Riskler
1. **Budget aşımı:** Otonom modda bütçe kontrolü atlanırsa aşırı aksiyon
2. **Cascade failure:** Bir otonom karar diğerlerini tetikleyebilir — circuit breaker gerekli
3. **Rollback ihtiyacı:** Otonom aksiyonların geri alınabilir olması zorunlu

---

## Akış İlişki Haritası

```
Wizard Flow (#1)
  ├── Template Resolution (#2) ← ⚠️ NULL scope riski
  ├── AI Suggestion (#3) ← Opsiyonel, kullanıcı onaylı
  └── Listing Publish (#5) ← Son adım

AI Suggestion (#3)
  └── Governance Decision (#4) ← Field suggestions governance panel

Governance Decision (#4)
  └── Autonomy Control (#8) ← Otonom seviye kontrolü

CRM Pipeline (#6)
  └── Matching (#7) ← Talep oluşturulunca eşleştirme
```
