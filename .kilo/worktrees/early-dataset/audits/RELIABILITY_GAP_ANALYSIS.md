# RELIABILITY GAP ANALYSIS — Phase 3
## Yalıhan Emlak AI OS — SAB v6.1
**Chief Enterprise Architect — Evidence-Only Audit**
**Tarih:** 2026-06-29
**Kapsam:** Outbox · Event Loss · CQRS Drift · Projector · Idempotency · File/DB Ordering · AI Fail · Circuit Breaker · Queue/DLQ
**Kural:** Kod yazılmadı. Her bulgu dosya yolu, sınıf, metot ve kanıt içerir.
**Status:** `VERIFIED` = doğrulandı | `PARTIALLY_VERIFIED` = kısmi | `NOT_VERIFIED` = kanıt yok

---

## CRITICAL

### REL-001 · OUTBOX
| Alan | Değer |
|------|-------|
| File | `app/Services/FinancialLedgerService.php` |
| Class | `FinancialLedgerService` |
| Method | `recordDoubleEntry()` |
| Line | 131 |
| Type | OUTBOX |
| Severity | **CRITICAL** |
| Status | **VERIFIED** |

**Kanıt:**
```php
// Line 67 — tüm işlem DB::transaction içinde
return DB::transaction(function () use (...) {
    // ... FX lock, debit/credit entries ...
    event(new \App\Events\LedgerDoubleEntryRecorded($debitEntry, $creditEntry)); // LINE 131
    return $transactionGroupId;
});
```

**Risk:** Veritabanı işlemi geri alındığında (bağlantı kaybı, deadlock, constraint ihlali), olay zaten dispatch edilmiş olur. `UpdateLedgerBalanceProjection` işlenmiş ama commit edilmemiş veriyi okumaya çalışır. Finansal tablo ile projeksiyon arasında kalıcı CQRS drift oluşur. Ledger Dengesi Phantom artış riski.

**Tavsiye:** Transactional Outbox pattern uygula — `outbox_events` tablosuna aynı transaction içinde yaz, ayrı bir scheduler prosesi okuyup dispatch etsin. İdentiklik anahtarları projektorlerde zaten mevcut (exactly-once garantisi verir).

---

### REL-002 · EVENT_LOSS
| Alan | Değer |
|------|-------|
| File | `config/queue.php` |
| Class | — |
| Method | — |
| Lines | 43, 52, 63, 72 |
| Type | EVENT_LOSS |
| Severity | **CRITICAL** |
| Status | **VERIFIED** |

**Kanıt:**
```php
'database'  => ['after_commit' => false], // LINE 43
'beanstalkd' => ['after_commit' => false], // LINE 52
'sqs'        => ['after_commit' => false], // LINE 63
'redis'      => ['after_commit' => false], // LINE 72
```

**Risk:** DB::transaction içinde dispatch edilen her job (ListingProjector, LeadProjector, EvaluateLeadWithCortex, UpdateLedgerBalanceProjection, SyncListingProjectionJob, ProcessProjectionJob) transaction commit *öncesi* kuyruğa yazılır. Transaction rollback olursa kuyruktaki job çalışır ama veri yoktur. Ledger projektorü commit edilmemiş kayıtları okumaya çalışır → phantom balance.

**Tavsiye:** Tüm bağlantılarda `after_commit: true` ayarla. Sadece transaction-dışı job'lar için false bırak. Veya REL-001.Transactional Outbox uygula.

---

## HIGH

### REL-003 · PROJECTOR
| Alan | Değer |
|------|-------|
| File | `app/Listeners/ListingProjector.php` |
| Class | `ListingProjector` |
| Method | `handleListingCreated()`, `handleListingUpdated()` |
| Lines | 21, 47, 92 |
| Type | PROJECTOR |
| Severity | **HIGH** |
| Status | **VERIFIED** |

**Kanıt:**
```php
// No try/catch — unhandled exception propagates to queue worker
public function handleListingCreated(ListingCreated $event): void
{
    if ($this->hasBeenProcessed($event->eventId)) { return; }
    DB::transaction(function () use ($event) {
        DB::table('proj_listings')->updateOrInsert(...);
        $this->markAsProcessed($event->eventId);
    }); // LINE 45 — DB exception rethrown, no catch
}
```

**Risk:** `proj_listings` veya `proj_event_offsets` geçici olarak erişilemez olduğunda (bağlantı tükenmesi, table lock, I/O spike), yakalanmayan `\Illuminate\Database\QueryException` queue worker'a propagates olur. Job retry edilir, sonra `failed_jobs`'a gider. Olay kalıcı olarak kaybolur. Yük altında cascading DB hatası → mass DLQ flood, otomatik kurtarma yok.

**Tavsiye:** Her projektor handler'ın DB::transaction gövdesini `try/catch(\Throwable)` ile sar. `Log::critical()` + rethrow. İdentiklik guard (`hasBeenProcessed`) replay'i güvenli kılar.

---

### REL-004 · PROJECTOR
| Alan | Değer |
|------|-------|
| File | `app/Listeners/LeadProjector.php` |
| Class | `LeadProjector` |
| Method | `handle()` |
| Line | 20–73 |
| Type | PROJECTOR |
| Severity | **HIGH** |
| Status | **VERIFIED** |

**Kanıt:** REL-003 ile aynı pattern — `hasBeenProcessed` guard var ama try/catch yok. Ek olarak `$tries` ve `$backoff` property'leri tamamen yok (EvaluateLeadWithCortex'te `$tries=3`, `$backoff=[30,60,120]` var). Laravel default'ları transient DB hataları için yetersiz.

**Tavsiye:** `public $tries = 3; public $backoff = [10, 30, 60];` ekle. DB::transaction'ı try/catch ile sar.

---

### REL-005 · FILE_TX_AI
| Alan | Değer |
|------|-------|
| File | `app/Services/Photo/PhotoService.php` |
| Class | `PhotoService` |
| Method | `deletePhoto()` |
| Lines | 91–99 |
| Type | FILE_TX_AI |
| Severity | **HIGH** |
| Status | **VERIFIED** |

**Kanıt:**
```php
public function deletePhoto(int $photoId): void
{
    $this->blockAgentWrite('deletePhoto');
    $photo = Photo::findOrFail($photoId);

    // DOSYALAR ÖNCE SİLİNİYOR
    if ($photo->path) {
        Storage::disk('public')->delete($photo->path);  // LINE 93 — file GONE
    }
    if ($photo->thumbnail) {
        Storage::disk('public')->delete($photo->thumbnail); // LINE 96 — file GONE
    }

    $photo->delete(); // LINE 99 — DB delete BAŞARISIZ OLABİLİR
}
```

**Risk:** `$photo->delete()` başarısız olursa (DB constraint violation, FK lock, connection loss) fiziksel dosyalar kalıcı olarak silinmiş ama `photos` tablo kaydı hala duruyor. Geri alınamaz veri tutarsızlığı. Telafi edici işlem yok. Storage::delete non-existent dosyada hata vermez → ek problem.

**Tavsiye:** Sırayı tersine çevir — önce DB::transaction içinde DB kaydını sil, sonra queued job ile dosyaları async sil. Transaction rollback olursa queued job zaten idempotent (Storage::delete non-existent dosyada güvenli).

---

### REL-006 · FILE_TX_AI
| Alan | Değer |
|------|-------|
| File | `app/Services/Photo/PhotoService.php` |
| Class | `PhotoService` |
| Method | `bulkDelete()` |
| Lines | 105–126 |
| Type | FILE_TX_AI |
| Severity: **HIGH** |
| Status: **VERIFIED** |

**Kanıt:**
```php
public function bulkDelete(array $photoIds): int
{
    $photos = Photo::whereIn('id', $photoIds)->get();
    $pathsToDelete = [];
    foreach ($photos as $photo) {
        if ($photo->path) { $pathsToDelete[] = $photo->path; }
        if ($photo->thumbnail) { $pathsToDelete[] = $photo->thumbnail; }
    }

    if (! empty($pathsToDelete)) {
        Storage::disk('public')->delete($pathsToDelete); // LINE 122 — TÜM DOSYALAR SİLİNDİ
    }

    return Photo::whereIn('id', $photoIds)->delete(); // LINE 125 — kısmi hata mümkün
}
```

**Risk:** REL-005'in amplify edilmişi: Tüm fiziksel dosyalar herhangi bir DB işleminden önce silinir. Bulk delete kısmi başarısız olursa (3/10 kayıt silindikten sonra constraint violation) 7 dosya gitmiş ama DB kayıtları duruyor. Partial failure dönüş değerinden anlaşılmaz.

**Tavsiye:** DB::transaction içinde önce DB kayıtlarını sil. Storage::delete son operation olsun veya queued job olarak commit sonrası çalışsın.

---

## MEDIUM

### REL-007 · AI_FAIL
| Alan | Değer |
|------|-------|
| File | `app/Services/Telegram/Processors/FinanceProcessor.php` |
| Class | `FinanceProcessor` |
| Method | `extractFinancialDataWithAI()` |
| Line | ~144 |
| Type | AI_FAIL |
| Severity | **MEDIUM** |
| Status | **PARTIALLY_VERIFIED** |

**Kanıt:**
```php
// LINE 144 — FinanceProcessor doğrudan GPT-4o çağrısı yapıyor
// YalihanCortex/AIOrchestrator üzerinden DEĞİL
Log::error('FinanceProcessor: GPT-4o API hatası', [...]);
Log::warning('FinanceProcessor: GPT-4o geçersiz JSON döndürdü', [...]);
// config/services.php:87 — DEEPSEEK_TIMEOUT = 30s
// FinanceProcessor'a özel timeout görünmüyor
```

**Risk:** FinanceProcessor GPT-4o'yu doğrudan çağırıyor (YalihanCortex yok). Circuit breaker, budget guard, timeout yok. GPT-4o down veya network partition altında Telegram handler senkron olarak block edilir → webhook timeout → message re-delivery storm. Fallback sadece missing API key için var (timeout/error yanıtları için değil).

**Tavsiye:** AI çağrısını try/catch ile sar, `Http::timeout(10)` kullan, YalihanCortex üzerinden yönlendir (circuit breaker + budget guard + failover mevcut). AI kullanılamaz olduğunda rule-based fallback impl.

---

### REL-008 · CIRCUIT_BREAKER
| Alan | Değer |
|------|-------|
| File | `app/Services/AI/AiBudgetGuard.php` + `app/Services/AI/Monetization/AiBudgetGuard.php` |
| Class | `AiBudgetGuard` (2 ayrı dosya, farklı namespace) |
| Method | — |
| Line | — |
| Type | CIRCUIT_BREAKER |
| Severity | **MEDIUM** |
| Status | **VERIFIED** |

**Kanıt:**
```php
// İKİ AYRI dosya, AYNI sınıf ismi, FARKLI namespace:
// 1. app/Services/AI/AiBudgetGuard.php → OpenAIService (line 12) kullanıyor
// 2. app/Services/AI/Monetization/AiBudgetGuard.php → AIOrchestrator (line 27) kullanıyor
```

**Risk:** İki bağımsız AiBudgetGuard implementasyonu var. Bir kiracı OpenAIService yoluyla bütçe tüketirse AIOrchestrator yolu bundan habersiz — ve tersi. Eşzamanlı isteklerde aynı kiracının kredileri iki enforcement path üzerinden double-spent olabilir. Budget override risk.

**Tavsiye:** Tek bir AiBudgetGuard impl. konsolide et. `app/Services/AI/` içindeki non-Monetary versiyonu deprecated et ve kaldır.

---

### REL-009 · CIRCUIT_BREAKER
| Alan | Değer |
|------|-------|
| File | `app/Services/Resilience/CircuitBreaker.php` |
| Class | `CircuitBreaker` |
| Method | `success()`, `failure()` |
| Lines | 71–98 |
| Type | CIRCUIT_BREAKER |
| Severity | **MEDIUM** |
| Status | **PARTIALLY_VERIFIED** |

**Kanıt:**
```php
// Lines 73-76 — success() failure sayacını temizliyor
public function success(string $serviceName): void
{
    Cache::forget($this->failureKey($serviceName));
    Cache::forget($this->openKey($serviceName));
}
// config/ai-runtime.php:50-55 — env ile config'li
'circuit_breaker' => [
    'failure_threshold' => (int) env('AI_CB_FAILURE_THRESHOLD', 5),
    'window_seconds'   => (int) env('AI_CB_WINDOW', 60),
    'cooldown_seconds' => (int) env('AI_CB_OPEN_SECONDS', 120),
    'half_open_trial_count' => 1,
],
```

**Risk:** Circuit breaker state'i Cache üzerinde tutuluyor. Cache operasyonları atomic değil — iki eşzamanlı istek aynı failure count'u okuyup artırabilir, gerçek failure count düşük kalır, threshold açılma için yeterli olmaz. Daha kritik: Redis restart veya cache flush'ta tüm circuit breaker state'i kaybolur, tüm devreler CLOSED'a resetlenir — halihazırda başarısız olan provider "kurtarılmış" gibi görünür.

**Tavsiye:** Cache yerine DB-backed atomic counter (`DB::table('circuit_breaker_state')->lockForUpdate()`) veya Redis atomic commands (INCR + EXPIRE) kullan.

---

### REL-010 · PROJECTOR
| Alan | Değer |
|------|-------|
| File | `app/Jobs/SyncListingProjectionJob.php` |
| Class | `SyncListingProjectionJob` |
| Method | `handle()` |
| Lines | 28–54 |
| Type | PROJECTOR |
| Severity | **MEDIUM** |
| Status | **VERIFIED** |

**Kanıt:**
```php
class SyncListingProjectionJob implements ShouldQueue
{
    // $tries property YOK
    // $backoff property YOK
    // failed() method YOK

    public function handle(): void
    {
        $ilan = \App\Models\Ilan::withTrashed()->find($this->ilanId);
        if (!$ilan || $ilan->trashed()) {
            \Illuminate\Support\Facades\DB::table('proj_listings')
                ->where('ilan_id', $this->ilanId)->delete();
            return;
        }
        \Illuminate\Support\Facades\DB::table('proj_listings')->updateOrInsert(...);
    }
}
```

**Risk:** Retry config yok → Laravel defaults ($tries=1, no backoff) → DB hatasında anında failure + DLQ. Forensic log yok. `updateOrInsert` race condition altında duplicate projection oluşturabilir.

**Tavsiye:** `public $tries = 3; public $backoff = [5, 15, 30];` ekle. `failed(\Throwable $exception)` forensic log ile ekle. `proj_listings.ilan_id` üzerinde unique constraint ekle.

---

### REL-011 · CQRS_DRIFT
| Alan | Değer |
|------|-------|
| File | `app/Domain/CQRS/Projections/IlanProjectionHandler.php` |
| Class | `IlanProjectionHandler` |
| Method | `handle()` |
| Lines | 34–125 |
| Type | CQRS_DRIFT |
| Severity | **MEDIUM** |
| Status | **PARTIALLY_VERIFIED** |

**Kanıt:**
```php
DB::transaction(function () use (...) {
    $mevcutOkumaDurumu = DB::table('ilanlar_read_model')
        ->where('tenant_id', $tenantId)
        ->where('ilan_id', $kaynakKimligi)
        ->first();

    if ($mevcutOkumaDurumu && $mevcutOkumaDurumu->son_islenen_sira_numarasi >= $siraNumarasi) {
        return; // LINE 45 — sequence idempotency guard
    }
    switch ($olayTuru) {
        case 'IlanOlusturuldu':
            DB::table('ilanlar_read_model')->insert([...]); // LINE 50 — INSERT
            break;
        // ... diğer case'ler sadece update() yapıyor, insert yok
    }
});
```

**Risk:** `IlanOlusturuldu` handler `insert()` ile yeni kayıt oluşturuyor. Diğer olaylar (`IlanFiyatiDegistirildi`, `IlanDurumuDegistirildi`) sadece `update()` yapıyor. REL-002 (after_commit=false) nedeniyle `IlanOlusturuldu` olayı kaybolursa, read model hiç oluşmaz. Sonraki tüm olaylar sequence idempotency guard nedeniyle `>=` kontrolünden dolayı atlanır — ancak kayıt olmadığından `mevcutOkumaDurumu` null, if atlanır, `update()` 0 satır etkiler, olay kaybolur.

**Tavsiye:** Her switch case'in başına `firstOrCreate` veya explicit upsert ekle. `update()` 0 satır etkilediğinde `Log::warning()` at.

---

### REL-012 · PROJECTOR
| Alan | Değer |
|------|-------|
| File | `app/Jobs/CQRS/ProcessProjectionJob.php` |
| Class | `ProcessProjectionJob` |
| Method | `handle()`, `failed()` |
| Lines | 67–99, 128–137 |
| Type | PROJECTOR |
| Severity | **LOW** |
| Status: **VERIFIED** |

**Kanıt:**
```php
// handle() — try/catch + rethrow: İYİ
public function handle(): void
{
    try {
        $this->routeToProjection(...);
    } catch (\Throwable $exception) {
        Log::critical("PROJECTION PROCESSING FAILURE: {$exception->getMessage()}", [...]);
        throw $exception; // LINE 97
    }
}

// failed() — forensic log VAR ama DLQ routing YOK
public function failed(\Throwable $exception): void
{
    Log::critical('Projection job failed after all retries', [...]);
    // "Dead letter queue (etki_alani_olaylari_hatali)" — sadece COMMENT, kod yok
}
```

**Risk:** ProcessProjectionJob doğru şekilde try/catch + rethrow yapıyor (iyi). Ancak DLQ routing eksik — yorum `proj_dlq` tablosundan bahsediyor ama ReplayProjectionDlq komutu bu tabloyu okumuyor. Failed job'lar Laravel'in `failed_jobs` tablosuna gider, ayrı `proj_dlq` yapılandırılmamış.

**Tavsiye:** `failed()` içinde `proj_dlq` tablosuna manuel yaz veya queue worker failed_jobs connection'ını yapılandır.

---

## ÖZET TABLOLARI

### Dağılım
| Tip | Sayı | CRITICAL | HIGH | MEDIUM | LOW |
|-----|------|----------|------|--------|-----|
| OUTBOX | 1 | 1 | 0 | 0 | 0 |
| EVENT_LOSS | 1 | 1 | 0 | 0 | 0 |
| PROJECTOR | 4 | 0 | 2 | 1 | 1 |
| FILE_TX_AI | 2 | 0 | 2 | 0 | 0 |
| AI_FAIL | 1 | 0 | 0 | 1 | 0 |
| CIRCUIT_BREAKER | 2 | 0 | 0 | 2 | 0 |
| CQRS_DRIFT | 1 | 0 | 0 | 1 | 0 |

**Toplam: 12 bulgu — 2 CRITICAL · 4 HIGH · 5 MEDIUM · 1 LOW**

### Tam Liste
| # | Tip | Şiddet | Dosya | Durum | Düzeltme Süresi |
|---|-----|--------|-------|-------|------------------|
| REL-001 | OUTBOX | CRITICAL | `app/Services/FinancialLedgerService.php:131` | VERIFIED | 8h |
| REL-002 | EVENT_LOSS | CRITICAL | `config/queue.php:43,52,63,72` | VERIFIED | 2h |
| REL-003 | PROJECTOR | HIGH | `app/Listeners/ListingProjector.php:21,47` | VERIFIED | 2h |
| REL-004 | PROJECTOR | HIGH | `app/Listeners/LeadProjector.php:20` | VERIFIED | 2h |
| REL-005 | FILE_TX_AI | HIGH | `app/Services/Photo/PhotoService.php:91-99` | VERIFIED | 3h |
| REL-006 | FILE_TX_AI | HIGH | `app/Services/Photo/PhotoService.php:105-126` | VERIFIED | 2h |
| REL-007 | AI_FAIL | MEDIUM | `app/Services/Telegram/Processors/FinanceProcessor.php:~144` | PARTIALLY_VERIFIED | 4h |
| REL-008 | CIRCUIT_BREAKER | MEDIUM | `app/Services/AI/AiBudgetGuard.php` + `...Monetization/` | VERIFIED | 3h |
| REL-009 | CIRCUIT_BREAKER | MEDIUM | `app/Services/Resilience/CircuitBreaker.php:71-98` | PARTIALLY_VERIFIED | 4h |
| REL-010 | PROJECTOR | MEDIUM | `app/Jobs/SyncListingProjectionJob.php:28-54` | VERIFIED | 2h |
| REL-011 | CQRS_DRIFT | MEDIUM | `app/Domain/CQRS/Projections/IlanProjectionHandler.php:34-125` | PARTIALLY_VERIFIED | 4h |
| REL-012 | PROJECTOR | LOW | `app/Jobs/CQRS/ProcessProjectionJob.php:128-137` | VERIFIED | 2h |

**Toplam düzeltme süresi: ~38 saat**

---

## KRİTİK ÖNCELİK EMİRLERİ

```
 DERHAL (2 saat)
 └── REL-002: config/queue.php → tüm bağlantılarda 'after_commit' => true
     (Event loss riskini anında azaltır, REL-001 + REL-011 için önkoşul)

 1-3 GÜN İÇİNDE (10 saat)
 ├── REL-001: FinancialLedgerService → Transactional Outbox pattern
 ├── REL-003: ListingProjector → try/catch + $tries + $backoff
 ├── REL-004: LeadProjector → try/catch + $tries + $backoff
 └── REL-005+REL-006: PhotoService → DB/Storage sırasını tersine çevir

 1 HAFTA İÇİNDE (14 saat)
 ├── REL-007: FinanceProcessor → YalihanCortex üzerinden AI çağrısı + timeout
 ├── REL-008: İki AiBudgetGuard → tek impl.'a konsolide et
 ├── REL-009: CircuitBreaker → DB-backed atomic counter veya Redis INCR+EXPIRE
 ├── REL-010: SyncListingProjectionJob → $tries + $backoff + failed() + unique constraint
 └── REL-011: IlanProjectionHandler → firstOrCreate + drift detection log

 SONRA (12 saat)
 └── REL-012: ProcessProjectionJob failed() → proj_dlq routing
```

---

## MİMARİ DESEN ÖZETİ

| Desen | Durum | Risk |
|-------|-------|------|
| Transactional Outbox | ❌ YOK | CRITICAL |
| after_commit config | ❌ Tümü false | CRITICAL |
| Projector try/catch | ❌ ListingProjector, LeadProjector | HIGH |
| File/DB ordering | ❌ Dosya önce siliniyor | HIGH |
| Dual AiBudgetGuard | ❌ 2 bağımsız impl. | MEDIUM |
| CircuitBreaker on Cache | ⚠️ Non-atomic, non-durable | MEDIUM |
| CQRS drift detection | ⚠️ Silent swallow riski | MEDIUM |
| Idempotent billing | ⚠️ Dogrulanmadı | MEDIUM |
| DLQ routing | ⚠️ Disconnected | LOW |

---

*Bu analiz kanıta dayalıdır. Kod yazılmadı. Chief Enterprise Architect — 2026-06-29*
