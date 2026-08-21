# GMAIL COMMUNICATIONS INTELLIGENCE — WAVE 1
## Implementation Charter

**Versiyon:** 1.0
**Tarih:** 2026-08-21
**SAB Onay:** 🟢 APPROVED (Session bağımsız)
**Sahip:** Kilo Code + Claude Sonnet 4.6
**Wave:** 1 — MVP

---

## 1. Giriş ve Hedef

### 1.1 Problem Tanımı

Ayhan'ın Gmail hesabına Airbnb ve Booking.com misafirlerinden gelen e-postalar geliyor. Bu mesajlar şu anda manuel okunuyor ve müdahale gerekenler sadece hafızada tutuluyor. Otomasyon olmadığı için:
- Gecikmeler veya kaçırılan mesajlar
- Operasyonel aciliyet belirsizliği
- Tenant dışı misafirlerle iletişim riski

### 1.2 Wave 1 Hedefi

```
Gmail mesajı
    ↓
Okunur / eşleştirilir / anlaşılır / önceliklendirilir
    ↓
P0 / P1 → Ayhan'a "Müdahale Gerekenler" ekranında gösterilir
P2 → Sessizce loglanır, alarm oluşturmaz
```

**Tamamen okuma odaklı:** Yanıt verme, otomatik mesaj, rezervasyon değiştirme, kapı şifresi üretme/gönderme YASAK.

---

## 2. Mimari Kararlar

### 2.1 İki Ayrı Deterministik Evaluator

```
PropertyReservation
        │
        ├── OperationalExceptionEvaluatorService (MEVCUT)
        │       → Rezervasyon state/dates/readiness anomalies
        │       → Zaten var, DEĞİŞTİRİLMESİN
        │
        └── CommunicationExceptionEvaluatorService (YENİ)
                → Misafir email mesajlarının önceliklendirmesi
                → OperationalExceptionEvaluator'a rakip DEĞİL, AYRI
                → İkisi birleşik Cockpit'te görünür
```

**Karar:** `OperationalExceptionEvaluatorService` büyütülmez. Email mesajları için ayrı bir `CommunicationExceptionEvaluatorService` oluşur.

### 2.2 LLM Sınırı

```
LLM (Gemini/Cortex) görevi:
  → Mesajdan SİNYAL ÇIKARIMI (intent, dil, konu, yapılandırılmış alanlar)
  → Mesajdan KAYNAK ÇIKARIMI (Airbnb? Booking? Doğrudan?)

LLM görevi DEĞİL:
  → Severity (P0/P1/P2) belirleme
  → Operasyon kararı
  → Aciliyet kararı

Severity/Operasyon kararı → PHP Policy (CommunicationSeverityPolicy)
```

### 2.3 Veri Modeli: Communication

`app/Models/Communication.php` mevcut — polymorphic, multi-channel, `channel = 'email'` desteği var. Yeni tablo yerine bu model genişletilir.

Ek alanlar Wave 1 için:
- `external_message_id` → Gmail message ID (idempotency)
- `ai_extracted_data` → AI extraction sonucu (JSON)
- `severity` → P0 | P1 | P2 (deterministic policy)
- `is_resolved` → bool (müdahale sonrası kapatılır)
- `resolved_at` → datetime
- `resolved_by` → user_id

---

## 3. Veri Akışı (Wave 1)

```
Gmail API / Webhook
    ↓
POST /api/v1/webhook/email/inbound
    ↓
IdempotencyGuard
    (external_message_id + tenant_id unique)
    ↓
TenantGuard
    (tenant_id doğrulama)
    ↓
DeterministicReservationResolver
    (email → GuestKisi → reservation匹配)
    ↓
EmailIntelligenceService (LLM)
    (Intent, dil, konu, kaynak, yapılandırılmış alanlar)
    ↓
CommunicationSeverityPolicy (PHP)
    (Sinyallerden → P0 | P1 | P2 kararı)
    ↓
Communication::create()
    ↓
Hermes: email.communication.received
    ↓
HermesService::dispatch()
    ├── P0/P1 → NotificationAgent → Ayhan bildirimi
    └── Cockpit "Müdahale Gerekenler" sorgusu
```

---

## 4. Kapsam

### 4.1 Yapılacaklar

| # | Bileşen | Tip | Dosya |
|---|---------|-----|-------|
| 1 | Gmail webhook endpoint | Controller | `app/Http/Controllers/Api/V1/EmailWebhookController.php` |
| 2 | GCP Pub/Sub webhook receiver | Service | `app/Services/Email/GmailWebhookReceiver.php` |
| 3 | IdempotencyGuard | Service | `app/Services/Email/IdempotencyGuard.php` |
| 4 | DeterministicReservationResolver | Service | `app/Services/Email/ReservationResolver.php` |
| 5 | EmailIntelligenceService | Service | `app/Services/AI/EmailIntelligenceService.php` |
| 6 | CommunicationSeverityPolicy | Policy | `app/Policies/CommunicationSeverityPolicy.php` |
| 7 | CommunicationExceptionEvaluatorService | Service | `app/Services/Communication/CommunicationExceptionEvaluatorService.php` |
| 8 | HermesCapability case | Enum | `HermesCapability::COMMUNICATION_EMAIL_RECEIVED` |
| 9 | HermesServiceProvider kayıt | SP | HermesServiceProvider |
| 10 | Cockpit API sorgusu | Controller | `admin/communications.php` |
| 11 | Gmail API credentials | Config | `app/Services/Email/GmailApiConfig.php` |
| 12 | Migration (Communication genişletme) | Migration | Yeni migration |

### 4.2 Kesinlikle Yapılmayacaklar

| Yasak |
|-------|
| Otomatik e-posta yanıtı gönderme |
| Misafire otomatik mesaj atma |
| Rezervasyon değiştirme / iptal etme |
| Kapı şifresi üretme veya gönderme |
| AI'ın kendi başına görev yürütmesi |
| Tenant dışı misafir verisi işleme |
| GuestMessage modelini email için kullanma |
| OperationalExceptionEvaluatorService'i değiştirme |
| LLM'e severity kararı bırakma |

---

## 5. Detaylı Bileşen Spesifikasyonu

### 5.1 EmailIntelligenceService

LLM'den beklenen çıktı (sadece signal extraction):

```php
struct EmailExtractionResult {
    intent:           'checkin_issue'|'checkout_question'|'complaint'|
                      'request_extend'|'damage_report'|'refund_request'|
                      'general'|'unknown',
    language:         'tr'|'en'|...,
    source_platform:  'airbnb'|'booking.com'|'direct'|'unknown',
    guest_name:       string|null,
    guest_email:      string|null,
    reservation_ref:  string|null,      // Airbnb/Booking ref
    property_name:   string|null,
    message_summary:  string,           // Kısa özet
    sentiment:        'positive'|'neutral'|'negative',
    is_urgent:        bool,             // Signal flag — policy karar vermez
    extracted_fields: array<string,mixed>,
}
```

### 5.2 CommunicationSeverityPolicy

Deterministik PHP kararı — LLM'e bırakılmaz:

```php
class CommunicationSeverityPolicy {

    // P0 — Aynı gün müdahale ZORUNLU
    public const SEVERITY_P0 = [
        'checkin_lockout',     // Kapı açılmıyor
        'safety_incident',     // Güvenlik sorunu
        'health_emergency',   // Sağlık acili
        'critical_complaint',  // Çok ciddi şikayet
    ];

    // P1 — 24 saat içinde müdahale
    public const SEVERITY_P1 = [
        'checkin_question',    // Giriş bilgisi sorunu
        'checkout_confusion',  // Çıkış karışıklığı
        'early_checkin_req',   // Erken giriş talebi
        'late_checkout_req',   // Geç çıkış talebi
        'maintenance_issue',    // Teknik arıza (ısıtma, su, vs.)
        'pool_issue',          // Havuz sorunu
    ];

    // P2 — İş günü içinde halledilebilir
    public const SEVERITY_P2 = [
        'general_question',    // Genel soru
        'house_rules',         // Ev kuralları
        'wifi_info',           // WiFi bilgisi
        'parking_info',        // Otopark bilgisi
        'area_question',       // Bölge sorusu
    ];

    public static function determineSeverity(EmailExtractionResult $extraction): string
    {
        // is_urgent=true ise P0 zorla
        if ($extraction->is_urgent) return self::SEVERITY_P0;

        // intent → severity mapping
        if (in_array($extraction->intent, self::SEVERITY_P0)) return 'P0';
        if (in_array($extraction->intent, self::SEVERITY_P1)) return 'P1';
        return 'P2';
    }
}
```

### 5.3 DeterministicReservationResolver

```
Email guest_email + reservation_ref
    ↓
1. ReservationRef ile ara (reservation_reference alanı)
2. GuestKisi.email ile ara
3. Tenant içinde en güncel aktif rezervasyonu bul
4. Tenant dışıysa → null, alarm yok
```

### 5.4 IdempotencyGuard

```
Yeni email:
  Message-ID header → Communication::where(external_message_id = $id)
  → Varsa: atla, logla "duplicate skipped"
  → Yoksa: devam et
```

### 5.5 HermesCapability

```php
// app/Domain/Hermes/Enums/HermesCapability.php
case COMMUNICATION_EMAIL_RECEIVED = 'communication.email_received';
```

Yeni Hermes event: `email.communication.received`
Handler: `CommunicationEmailHandler` (bildirim + log)

---

## 6. Definition of Done

| # | Kriter | Doğrulama |
|---|---------|-----------|
| D1 | Aynı Gmail message ID ikinci kez işlenmez | Idempotency test: 2x aynı webhook payload → 1 DB satır |
| D2 | Tenant dışı misafir verisi işlenmez | Tenant scope dışında email → DB'ye gitmez |
| D3 | Misafir doğru PropertyReservation'a bağlanır | Test: bilinen rezervasyon ref'li email → `reservation_id` dolu |
| D4 | P0/P1/P2 deterministik belirlenir | Unit test: her intent → beklenen severity |
| D5 | GuestMessage audit trail oluşur | `Communication::create()` → `ai_analysis` JSON dolu |
| D6 | P0/P1 Cockpit'te görünür | GET `/admin/api/communications?severity=P0` → 200 |
| D7 | P2 alarm oluşturmaz | P2 mesaj → Hermes notification tetiklemez |
| D8 | Unresolved mesajlar kaybolmaz | Tüm email → Communication tablosunda |
| D9 | Tüm execution loglanır | `HermesEventLog` veya ` WorkforceExecutionLog` satırı |
| D10 | Severity LLM yerine PHP policy'den gelir | Code review: `CommunicationSeverityPolicy` dışında severity atanmaz |

---

## 7. Cockpit — "Müdahale Gerekenler" Entegrasyonu

### 7.1 Mevcut Durum

Cockpit sayfası: `admin/ai.php` → `/admin/ai`
Yeni route: `/admin/communications` veya mevcut AI dashboard'a eklenir.

### 7.2 Wave 1 Kapsamı

Yeni bir sayfa DEĞİL — mevcut AI dashboard'a:
- "İletişim Zekası" sekmesi veya widget'ı
- P0/P1 Communications listesi
- Her satırda: tarih, misafir, kaynak (Airbnb/Booking), konu, severity badge, rezervasyon linki

### 7.3 Admin API

```
GET /admin/api/communications
  ?tenant_id=X
  &severity=P0,P1
  &is_resolved=false
  &page=1

Response:
{
  "data": [
    {
      "id": 1,
      "severity": "P0",
      "channel": "email",
      "message": "...",
      "ai_extracted_data": {...},
      "reservation_id": 42,
      "reservation_ref": "AIRBNB-XXXX",
      "is_resolved": false,
      "created_at": "..."
    }
  ]
}

PATCH /admin/api/communications/{id}/resolve
  Body: { "is_resolved": true }
  → Ayhan müdahale sonrası kapatır
```

---

## 8. Gmail Entegrasyon Yöntemi

### 8.1 Seçenek: GCP Pub/Sub Push (Önerilen)

```
Gmail API → Push Notification
    ↓
GCP Pub/Sub Topic (yalihan-email)
    ↓
Cloud Function (Node.js/Python)
    → POST our Laravel webhook
    ↓
Laravel /api/v1/webhook/email/inbound
```

**Avantaj:** Gerçek zamanlı, sunucu portu açmaya gerek yok
**Gereksinimler:**
- Google Cloud Platform projesi
- Pub/Sub API enable
- Gmail API enable + watch setup
- Cloud Function deployment

### 8.2 Seçenek: Laravel Webhook Endpoint (Fallback)

```
Ayhan'ın Gmail → Zapier/Make automation
    → POST /api/v1/webhook/email/inbound
```

**Avantaj:** Hızlı kurulum, GCP yok
**Dezavantaj:** 3. parti bağımlılığı

### 8.3 Wave 1 Kararı: Webhook Endpoint öncelikli

Gmail API + Pub/Sub tam kurulum dokümanı charter'a dahil; fakat ilk MVP için **Laravel webhook endpoint** ile başlanır. GCP Pub/Sub Wave 2'ye规划.

**Webhook URL:** `https://api.yalihanemlak.com.tr/api/v1/webhook/email/inbound`

---

## 9. Gereksinimler ve Bağımlılıklar

### 9.1 Gerekli Olanlar (Ayhan'dan)

| # | Gereksinim | Kanal |
|---|-----------|-------|
| G1 | Gmail API credentials (OAuth 2.0 client) | Google Cloud Console |
| G2 | Webhook URL'yi Gmail'e bağlayan mekanizma (Zapier/Make veya GCP Pub/Sub) | Ayhan tercihine göre |
| G3 | Airbnb + Booking misafir email adresi domain'i (whitelist için) | |
| G4 | `api.yalihanemlak.com.tr/api/v1/webhook/email/inbound` endpoint'in açık olması | Firewall check |

### 9.2 Bağımlılık Haritası

```
Gmail credentials (G1) + Webhook setup (G2)
    ↓
Wave 1 kod yazılabilir
    ↓
Test: Gerçek email ile end-to-end
    ↓
Ayhan webhook'u Gmail'e bağlar
    ↓
Production başlar
```

---

## 10. Dosya Yapısı

```
app/
  Http/
    Controllers/
      Api/
        V1/
          EmailWebhookController.php      # Route: /api/v1/webhook/email/inbound
  Services/
    Email/
      GmailWebhookReceiver.php            # Pub/Sub / webhook payload parse
      IdempotencyGuard.php               # Duplicate check
      ReservationResolver.php             # Email → reservation matching
    AI/
      EmailIntelligenceService.php        # LLM signal extraction
    Communication/
      CommunicationExceptionEvaluatorService.php  # Devam eden exception
  Policies/
    CommunicationSeverityPolicy.php       # P0/P1/P2 deterministic karar
  Domain/
    Hermes/
      Enums/
        HermesCapability.php              # +COMMUNICATION_EMAIL_RECEIVED
      Handlers/
        CommunicationEmailHandler.php     # Hermes handler
  Models/
    Communication.php                     # Genişletilecek alanlar
database/
  migrations/
    2026_08_21_xxxxx_add_email_intelligence_to_communications.php
routes/
  api.php                                 # +Route::post('/webhook/email/inbound', ...)
```

---

## 11. Test Stratejisi

### 11.1 Unit Testler

| Test | Beklenen |
|------|----------|
| `CommunicationSeverityPolicyTest` | Her intent → doğru severity |
| `IdempotencyGuardTest` | 2x aynı message ID → 1 kez insert |
| `ReservationResolverTest` | Bilinen email → doğru reservation |
| `TenantGuardTest` | Tenant dışı → exception atlar |

### 11.2 Feature Testler

| Test | Beklenen |
|------|----------|
| `EmailWebhookControllerTest` | Valid payload → Communication oluşur |
| `EmailWebhookControllerTest` | Duplicate payload → 2. kez DB yazılmaz |
| `EmailWebhookControllerTest` | Tenant dışı email → 403 |

### 11.3 Manual E2E

1. Gerçek Airbnb/Booking email'ini Ayhan'dan al
2. Webhook URL'ye POST et (curl veya Postman)
3. Cockpit'te görünürlüğünü doğrula
4. Resolve butonunun çalıştığını doğrula

---

## 12. Riskler

| Risk | Olasılık | Etki | Mitigasyon |
|------|---------|------|-----------|
| Gmail API credential alınamaz | Orta | Yüksek | Webhook fallback (Zapier) |
| Misafir email'i tenant dışına çıkar | Düşük | Kritik | TenantGuard + whitelist |
| LLM extraction başarısız | Düşük | Orta | Fail-open: `intent=unknown`, severity=P2 |
| Yüksek email volume | Orta | Orta | Rate limit + queue |

---

## 13. Sonraki Wave'ler (Out of Scope)

- **Wave 2:** GCP Pub/Sub Push entegrasyonu (gerçek zamanlı)
- **Wave 3:** Otomatik email taslak yanıtı (Ayhan onaylı)
- **Wave 4:** WhatsApp channel desteği (Communication model zaten destekliyor)
- **Wave 5:** AI'ın Gorev oluşturması (P0/P1 için)

---

## 14. Timeline Ön Tahmin

| Faz | İş | Tahmin |
|-----|-----|--------|
| W1-1 | Veri modeli genişletme + migration | 1 saat |
| W1-2 | EmailWebhookController + IdempotencyGuard | 2 saat |
| W1-3 | ReservationResolver + TenantGuard | 2 saat |
| W1-4 | EmailIntelligenceService (LLM) | 2 saat |
| W1-5 | CommunicationSeverityPolicy | 1 saat |
| W1-6 | Hermes kaydı + CommunicationExceptionEvaluator | 2 saat |
| W1-7 | Cockpit API + admin sayfası | 3 saat |
| W1-8 | Testler | 2 saat |
| **Toplam** | | **~15 saat** |

---

_Charter Versiyon 1.0 — 2026-08-21_
