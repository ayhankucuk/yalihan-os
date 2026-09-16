# Bounded Context Map — Yalıhan OS

**Commit:** `d592f404`  
**Tarih:** 2026-09-12  
**Yazar:** Agent (Salt-Okunur Haritalama)  
**Durum:** IN_PROGRESS — Q1-Q5 cevaplandı; eksik doğrulama: Hermes replay safety, iki ReadRepository sahiplik

> Bu belge, Anayasa Madde 3'te tanımlı 10 Bounded Context arasındaki gerçek event/çağrı bağlantılarını kaynak koddan doğrulayarak haritalar. "Yok" iddiası kanıt değildir — her bağlantı kaynak dosya:satır ile belgelenir.

---

## Genel Bakış: Bağlantı Matrisi

| Kaynak Event | Üretici | Tüketici (Listener/Job) | Veri Yazma | TX Sınırı | Tekrar/Hata | Test |
|---|---|---|---|---|---|---|
| `IlanCreated` | `StoreIlanAction` | 5 listener | Cache, analytics, Gorev, notifications | Ayrı job (async) | Retry 3x + backoff | `WizardDomainRefactorTest` |
| `WizardSubmitted` | `WizardStepExecutor` | `HandleWizardSubmission` | Cache, analytics, Gorev, IlanCreated proxy | Ayrı job (async) | Retry 3x + backoff | REPO_VERIFIED (kod) |
| `IlanYayinlandiEvent` | `IlanYonetici::publish()` | 3 listener | Gorev, WhatsApp, lead notification | Ayrı job (async) | Retry 3x + backoff | REPO_VERIFIED |
| `ReservationCompletedEvent` | `ReservationStateMachine` | `ListenReservationCompleted` | Financial completion, turnover task | `ShouldQueueAfterCommit` | Retry 3x + backoff | REPO_VERIFIED |
| `WizardStepCompleted` | `WizardStepExecutor` | `HandleWizardStepCompleted` | Cache, analytics | Ayrı job (async) | Retry 3x + backoff | REPO_VERIFIED |

---

## Bağlantı 1: IlanCreated Event Zinciri

### Kanıt Zinciri

```
StoreIlanAction::handle()
  → dispatchSingle([new IlanCreated($ilan)])
  → EventServiceProvider:25
  → 5 listener'a dağılır
```

### Listener Detayı

| Listener | İş | Queue | Retry | Veri Yazma |
|---|---|---|---|---|
| `BC001/IlanCreatedListener` | BC-001 bootstrap log | ✅ | 3x | Log |
| `SendEmailOnIlanCreated` | Admin email | ✅ | 3x | Mail |
| `ActionCenter/IlanCreatedActionListener` | 3 Gorev (foto, açıklama, fiyatlandırma) | ✅ | 3x | `gorevler` tablosu |
| `InvalidateIlanCache` | Cache invalidation | ❌ (sync) | — | Cache |
| `FindMatchingDemands` | Reverse match + n8n | ✅ | 3x | Notification, n8n webhook |

### Transaction Sınırı

- Listener'ların çoğu `ShouldQueue` — ayrı job olarak çalışır
- `InvalidateIlanCache` sync — aynı transaction içinde
- **Not:** `StoreIlanAction` bir DB transaction içinde miydi? Kaynak doğrulanmadı (`UNKNOWN`)

### Tekrar / Hata Davranışı

- Tüm async listener'lar: `$tries = 3`, `$backoff = [30, 60, 120]`
- `FindMatchingDemands`: idempotent değil — aynı ilan tekrar `IlanCreated` tetiklenirse duplicate match + notification riski
- `failed()` method: tüm listener'larda mevcut (log + rethrow)

### Bilinen Risk

> ⚠️ **REPO_VERIFIED:** `FindMatchingDemands` — idempotency yok. Aynı `IlanCreated` event replay edilirse duplicate notification gider. `HandleWizardSubmission` içinde aynı guard var ama `IlanCreated` doğrudan dispatch edilirse korumasız.

---

## Bağlantı 2: WizardSubmitted Event Zinciri

### Kanıt Zinciri

```
WizardStepExecutor::executeStep() [satır 92]
  → event(new WizardSubmitted($ilan, $finalOptions))
  → EventServiceProvider:213
  → HandleWizardSubmission::handle()
```

### HandleWizardSubmission Detayı

```
HandleWizardSubmission::handle()
  ├── Yayınladıysa (yayinda/yayinda_bekleyen/yayinlandi):
  │     → event(new IlanCreated($ilan))   ← proxy
  │     → FindMatchingDemands tetiklenir
  │     → n8n bildirimi gider
  ├── Yayınlamadıysa (taslak):
  │     → sadece log (lead matching atlanır)
  └── Her durumda:
        → cache.forget(ilan)
        → SyncListingProjectionJob::dispatch()
        → ActionCenterService::generateActions()   ← ayrı IlanCreated fırlatır
```

### Transaction Sınırı

- `ShouldQueue` — async job
- `SyncListingProjectionJob` ayrı dispatch — parent TX'den bağımsız

### Tekrar / Hata Davranışı

- `$tries = 3`, `$backoff = [30, 60, 120]`
- **Idempotency:** Sadece yayınlanmış ilanlar için `IlanCreated` proxy — idempotent
- **Ancak:** `ActionCenterService::generateActionsFromEvent()` her durumda çağrılır — duplicate Gorev riski

### ⚠️ CRITIK BULGU: EventServiceProvider Duplicate Kayıt

```
Satır 53-58 (ESKİ/KULLANILMAYAN):
\WizardSubmitted::class => [
    InvalidateIlanCache,
    UpdateAnalyticsProjections,
    FindMatchingDemands,           ← HATALI: doğrudan tetiklenir (idempotency yok)
    IlanCreatedActionListener,
]

Satır 213-215 (DOĞRU):
\WizardSubmitted::class => [
    HandleWizardSubmission::class,  ← TEK KAYIT: proxy ile çağrır
]
```

**Teşhis:** İki `WizardSubmitted` kaydı var. Eski kayıt (`satır 53-58`) `FindMatchingDemands`'ı doğrudan tetikler — idempotency olmadan. Yeni kayıt (`satır 213-215`) `HandleWizardSubmission` proxy kullanır.

**Eğer her iki kayıt da aktifse:** Aynı `WizardSubmitted` event iki kez işlenir → duplicate matching + duplicate n8n bildirimi.

**Karar:** `Satır 53-58` kaldırılmalı. Kod değişikliği gerekir — ayrı görev.

---

## Bağlantı 3: IlanYayinlandiEvent

### Kanıt Zinciri

```
IlanYonetici::publish()
  → EventServiceProvider:48
  → 3 listener'a dağılır
```

### Listener Detayı

| Listener | İş | Queue | Veri Yazma |
|---|---|---|---|
| `ActionCenter/IlanPublishedActionListener` | 1 Gorev (lead matching check, +1h) | ✅ | `gorevler` tablosu |
| `NotifyLeadsOnNewListing` | Lead notification | ✅ | Notification |
| `SendWhatsAppNotificationListener` | WhatsApp bildirimi | ✅ | WhatsApp API |

### Transaction Sınırı

- Tamamı `ShouldQueue` — async

### Rezervasyon Bağlantısı: YOK

> **Q2 Cevabı:** `IlanYayinlandiEvent` → Rezervasyon domain arasında **doğrudan event/çağrı bağlantısı bulunamadı.** İlan yayınlanması tek başına takvim açılmasını tetiklemiyor. Rezervasyon oluşturma ayrı iş akışı — muhtemelen Operation context içinde.

---

## Bağlantı 4: ReservationCompletedEvent → Finance

### Kanıt Zinciri

```
ReservationStateMachine (checkout)
  → ReservationCompletedEvent
  → ListenReservationCompleted::handle() [satır 31, ShouldQueueAfterCommit]
      ├── ProcessFinancialCompletionJob::dispatch() → Finance
      └── ProcessReservationCompletedJob::dispatch() → Temizlik/turnover
```

### ProcessFinancialCompletionJob Detayı

```
handle()
  ├── Tenant kontrolü (satır 110)
  ├── C7: CANCELLED guard (satır 126) — iptal edilmiş rezervasyonu atlar
  ├── Idempotency: CONFIRMED/PAID durumunda no-op (satır 142, 154)
  └── Financial transition
```

### Transaction Sınırı

- `ShouldQueueAfterCommit` — parent transaction commit olduktan sonra çalışır
- `$uniqueFor = 300` (5 dakika lock) — crash recovery

### Tekrar / Hata Davranışı

- `ShouldBeUnique` — aynı job tekrar dispatch edilirse atlanır
- `CANCELLED` durumunda silent no-op
- `CONFIRMED`/`PAID` durumunda idempotent no-op
- Tenant kontrolü: mismatch olursa log + return (silent)

### Finance Bağlantısı: VAR

> **Q3 Cevabı:** `ReservationCompletedEvent` → Finance tek tüketici. Sadece `ProcessFinancialCompletionJob`. Başka tüketici yok.

### Bilinen Risk

- Finance provizyon ayırma işlemi **Rezervasyon tamamlandıktan sonra** tetiklenir — ilan yayınlanmasında değil. İş kuralı mı, eksiklik mi — iş analisti ile doğrulanmalı.

---

## Bağlantı 5: n8n Webhook Bildirimleri

### Kanıt Zinciri

```
IlanCreated
  → FindMatchingDemands::handle()
      → NotifyN8nAboutNewIlan::dispatch($ilan->id)   [satır 140]
          → HTTP POST → config('services.n8n.new_ilan_webhook_url')
```

### n8n Job Detayı

| Alan | Değer |
|---|---|
| URL | `config('services.n8n.new_ilan_webhook_url')` |
| Auth | `X-N8N-SECRET` header |
| Timeout | 30s (config) |
| Retry | 3x (job retry) |
| Fail-open | ❌ — `empty($webhookUrl)` → silent return |

### Q4 Cevabı: n8n Workflow

> **BULUNAMADI.** `NotifyN8nAboutNewIlan` webhook URL'si `config('services.n8n.new_ilan_webhook_url')` — değeri `.env`'de. n8n workflow dosyası repo'da yok. Dokümantasyon `docs/` içinde araştırılmalı.

---

## Bağlantı 6: Gorev (Action Center) Üretimi

### Kanıt Zinciri

```
IlanCreated     → 3 Gorev (foto, açıklama, fiyatlandırma)
IlanYayinlandi  → 1 Gorev (lead matching check, +1h)
TalepReceived   → 1 Gorev (match demand, +4h)
IlanPriceChanged→ 1 Gorev (re-evaluate matching, +4h)
```

### Tüm Gorev Kaynakları

| Event | Gorev Sayısı | Deadline |
|---|---|---|
| `IlanCreated` | 3 | — |
| `IlanYayinlandiEvent` | 1 | +1h |
| `TalepReceived` | 1 | +4h |
| `IlanPriceChanged` | 1 | +4h |

### Transaction Sınırı

- Tamamı `ShouldQueue` — async

---

## Bilinmeyen / Doğrulanmayan Alanlar

| # | Alan | Durum | Nasıl Doğrulanır |
|---|---|---|---|
| U1 | HermesReplayService idempotency garantileri | `UNKNOWN` | `HermesReplayService.php` replay method + test |
| U2 | HermesReplayService tenant isolation | `UNKNOWN` | `HermesReplayService.php` tenant filtre + test |
| U3 | İki `IlanReadRepository` sahiplik | `UNKNOWN` | Ekran routing + controller DI |
| U4 | n8n workflow tanımı | `UNKNOWN` | `docs/n8n/` veya `.env` webhook URL'si |
| U5 | IlanCreated → StoreIlanAction transaction boundary | `UNKNOWN` | StoreIlanAction source code |
| U6 | Ilan → Rezervasyon (listing activates reservation calendar) | `UNKNOWN` | İş kuralı doğrulaması gerekir |
| U7 | ACL: FinancialLedgerService çağrı zinciri | `UNKNOWN` | `app/Services/Financial/` dosya + çağrı |

---

## Boşluk: Action Center Tekrarlı Gorev Üretimi

`HandleWizardSubmission` (satır 70) `ActionCenterService::generateActionsFromEvent()` her durumda çağırır — yayınlanmış veya değil. `IlanCreatedActionListener` zaten aynı event'i işliyor. **İki kez aynı Gorev seti üretilebilir.**

---

## Sonraki Adımlar

| Öncelik | Görev | Kim |
|---|---|---|
| 1 | EventServiceProvider duplicate `WizardSubmitted` kaydını temizle (satır 53-58) | Ayrı görev |
| 2 | `HermesReplayService` replay safety doğrulaması | Sprint 1 |
| 3 | İki `IlanReadRepository` tüketici haritalaması | Sprint 1 |
| 4 | `docs/n8n/` workflow dokümantasyonu | Dokümantasyon |
| 5 | ACL: FinancialLedgerService çağrı zinciri | Sprint 2 |

---

*Bu harita salt-okunur inceleme sonucudur. Kod değişikliği ayrı görev olarak planlanmalıdır.*
