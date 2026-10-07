# YALIHAN OS — BUSINESS WORKFLOWS

**LAST_VERIFIED_HEAD:** e9d0d611
**Evidence Level:** REPO_VERIFIED

---

## Business Value Chain

```
MÜŞTERİ (Kisi)
     ↓
TALEP OLUŞTURMA (Talep)
     ↓
EŞLEŞTİRME (Matching/AI)
     ↓
İLAN ATAMASI (Ilan)
     ↓
GÖRÜŞME (KisiEtkilesim)
     ↓
REZERVASYON veya SATIŞ
     ↓
FİNANS (FinansalIslem)
```

---

## WORKFLOW 1: İlan Yönetimi

### 1.1 İlan Oluşturma (Wizard)

```
Step 1: Kategori Seçimi
  → Kategori seçimi
  → Yayın tipi belirleme

Step 2: Özellikler
  → Ozellikler atama
  → Feature assignments

Step 3: Medya
  → Fotoğraf yükleme (IlanFotografi)
  → Video yükleme (IlanVideo)

Step 4: Adres
  → Konum bilgileri (il, ilce, mahalle)
  → Ulke seçimi

Step 5: Önizleme
  → Durum: TASLAK veya YAYINDA
```

**Controllers:**
- IlanCrudController (create, store)
- IlanPhotoController (medya)
- IlanWizard* (wizard flow)

**Models:**
- Ilan
- IlanFotografi
- IlanVideo
- Ozellik (through feature_assignments)

---

### 1.2 İlan Durum Değişikliği

```
TASLAK → BEKLEMEDE → YAYINDA
                      ↘ PASIF
                      ↘ ARSIV
```

**Trigger:** IlanYayinDurumuManagement trait
**Authority:** IlanDurumu enum

**State Behaviors:**
| State | Public | Editable | Calendar |
|-------|--------|----------|----------|
| TASLAK | ❌ | ✅ | ❌ |
| BEKLEMEDE | ❌ | ✅ | ❌ |
| YAYINDA | ✅ | ✅ | ✅ |
| PASIF | ❌ | ✅ | ❌ |
| ARSIV | ❌ | ❌ | ❌ |

---

### 1.3 İlan Yayınlama

```
YAYINDA → IlanPolicy check → PublishGate check → Live
```

**Controllers:**
- IlanPublishController
- IlanPublishGateController

**Checks:**
- Content quality
- Required fields
- AI quality score (IlanAIQualityController)

---

## WORKFLOW 2: CRM / Müşteri Yönetimi

### 2.1 Müşteri Oluşturma

```
Kisi Kaydı → Durum Atama → Danışman Atama → Etiketleme
```

**Initial State:** SICAK (hot lead)

**Controllers:**
- KisiController (store)
- KisiNotController (notlar)

**Models:**
- Kisi
- KisiEtkilesim (görüşme kayıtları)

---

### 2.2 Talep Oluşturma

```
Talep Formu → Kisi seçimi → Kriterler → İlan bağlama
```

**Talep States:** BEKLEMEDE → AKTIF → KAPALI

**Controllers:**
- TalepController
- TalepStoreContractTest

**Relations:**
- Talep → Kisi (kisi_id)
- Talep → Ilan (ilan_id)
- Talep → User (danisman_id)

---

### 2.3 Eşleştirme (Matching)

```
Talep (kriterler)
     ↓
DemandMatchingEngine
     ↓
Scoring Algorithm
     ↓
Recommended Ilanlar
```

**Services:**
- DemandMatchingEngine (area, fiyat, özellik)
- MatchingAuthorityService

**Output:** Skor'a göre sıralanmış Ilan listesi

---

### 2.4 Görüşme Kaydı

```
Görüşme → KisiEtkilesim kaydı → Sonraki aksiyon
```

**Fields:**
- Görüşme tarihi
- Notlar
- Kullanıcı (danışman)

---

## WORKFLOW 3: Rezervasyon

### 3.1 Rezervasyon Oluşturma

```
Ilan Seçimi → Tarih Aralığı → Tarih Çakışma Kontrolü
     ↓
Guest Bilgileri → Total Amount
     ↓
Rezervasyon Kaydı → Calendar Block
     ↓
Admin Bildirimi
```

**Controllers:**
- IlanCalendarController (store)

**Services:**
- IlanReservationService (conflict check)
- AvailabilityService (calendar blocking)

**Tables:**
- property_reservations (dates, guest info)
- IlanReservation (model)

**Events:**
- ReservationCreatedEvent → Hermes

---

### 3.2 Rezervasyon Durumları

```
pending → confirmed → checked_in → completed
         ↘ cancelled (herhangi bir aşamada)
```

**State Field:** reservation_state (enum)

**Cancellation:**
- cancelled_at timestamp atanır
- Takvim bloğu KALDIRILMAZ (geriye dönük raporlama)

---

### 3.3 Takvim Yönetimi

```
Rezervasyon → AvailabilityService → Calendar Block
     ↓
ICS Export / Feed
```

**Controllers:**
- IlanCalendarController (index)
- IlanCalendarFeedAdminController

---

## WORKFLOW 4: Finans

### 4.1 Finansal İşlem Oluşturma

```
Manual Trigger → FinansalIslem → Durum: BEKLIYOR
     ↓
Yönetici Onayı → Durum: ONAYLANDI
     ↓
İşlem Tamamlandı → Durum: TAMAMLANDI
```

**⚠️ ISSUE:** No automatic trigger from Reservation

**Models:**
- FinansalIslem (ilan_id, kisi_id, gorev_id)

**Services:**
- FinansalIslemManager (CRUD)
- FinansService (AI analiz)

---

### 4.2 AI Finansal Analiz

```
FinansService::analyzeFinancials()
     ↓
AI Prompt Construction
     ↓
AIProviderManager (DeepSeek/GPT)
     ↓
Analysis Result
```

**Functions:**
- predictFinancials()
- analyzeRisk()
- suggestInvoice()

---

## WORKFLOW 5: Bildirimler

### 5.1 Bildirim Gönderimi

```
NotificationContract
     ↓
NotificationDispatcher
     ↓
Channel Adapter (Email/WhatsApp/Telegram)
     ↓
OutboundNotification (audit log)
```

**Channels:** Email, WhatsApp, Telegram, Instagram, Webhook

**Models:**
- OutboundNotification (audit)
- NotificationTemplate

---

### 5.2 Rezervasyon Bildirimleri

```
Rezervasyon Oluşturuldu → GuestConfirmationNotification
     ↓
Rezervasyon İptal → GuestCancellationNotification
     ↓
Admin Bildirimi → AdminNotification
```

---

## WORKFLOW 6: n8n Entegrasyonu

### 6.1 n8n Workflow Tetikleme

```
Event Trigger → N8nIntegrationService
     ↓
Webhook Call → n8n
     ↓
n8n Workflow Execution
```

**Triggered Events:**
| Event | Job | Purpose |
|-------|-----|---------|
| Gorev deadline | NotifyN8nAboutGorevDeadlineYaklasiyor | Deadline uyarısı |
| Gorev durum değişikliği | NotifyN8nAboutGorevDurumChanged | Durum bildirimi |
| Gorev gecikti | NotifyN8nAboutGorevGecikti | Gecikme bildirimi |
| İlan fiyat değişikliği | NotifyN8nAboutIlanPriceChange | Fiyat bildirimi |
| Yeni görev | NotifyN8nAboutNewGorev | Görev bildirimi |

---

## WORKFLOW 7: AI Entegrasyonu

### 7.1 AI Provider Seçimi

```
Request → AIProviderManager
     ↓
getActiveProvider() → config
     ↓
Provider-specific call
```

**Providers:** DeepSeek, OpenAI, Claude, Gemini, Ollama

---

### 7.2 AI Cost Tracking

```
AI Call → AiLog kaydı
     ↓
AiCostCalculatorService
     ↓
Budget Guard (AiBudgetGuard)
```

---

## Workflow Dependencies

```
Hermes (Event Bus)
     ↓
Queue/Jobs (Async processing)
     ↓
Notifications (Channels)
     ↓
n8n (External automation)
```

---

## Missing/Partial Workflows

| Workflow | Status | Issue |
|---------|--------|-------|
| Otomatik Finans | ❌ Missing | No auto-create from Reservation |
| Otomatik Matching notification | ❌ Missing | No automatic notification |
| Lead scoring | ⚠️ Partial | Service exists, full flow unknown |
| Ödeme/Tahsilat | ⚠️ Partial | FinansalIslem manual |
| Kisi state transitions | ⚠️ Partial | Not automated |
