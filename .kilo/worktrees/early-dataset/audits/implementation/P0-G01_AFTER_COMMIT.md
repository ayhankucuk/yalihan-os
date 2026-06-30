# IMPLEMENTATION PACKAGE — P0-G01
## after_commit: false → true
### config/queue.php — Queue Event Dispatching

**Bulgu:** `config/queue.php` — Tüm 4 bağlantıda `after_commit: false`
**Severity:** CRITICAL
**Status:** VERIFIED
**Audit Ref:** `audits/SECURITY_GAP_ANALYSIS.md` | `audits/RELIABILITY_GAP_ANALYSIS.md`

---

## 1. DOĞRULAMA

### Dosya
`/Users/macbookpro/dev/yalihan2026/config/queue.php`

### Etkilenen Satırlar
| Satır | Bağlantı | Mevcut Değer |
|-------|---------|--------------|
| 43 | `database` | `'after_commit' => false` |
| 52 | `beanstalkd` | `'after_commit' => false` |
| 63 | `sqs` | `'after_commit' => false` |
| 72 | `redis` | `'after_commit' => false` |

### Mevcut Kod
```php
'database' => [
    'driver' => 'database',
    'connection' => env('DB_QUEUE_CONNECTION'),
    'table' => env('DB_QUEUE_TABLE', 'jobs'),
    'queue' => env('DB_QUEUE', 'default'),
    'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
    'after_commit' => false,   // ← SATIR 43
],

'beanstalkd' => [
    'driver' => 'beanstalkd',
    'host' => env('BEANSTALKD_HOST', 'localhost'),
    'queue' => env('BEANSTALKD_QUEUE', 'default'),
    'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
    'block_for' => 0,
    'after_commit' => false,  // ← SATIR 52
],

'sqs' => [
    'driver' => 'sqs',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'queue' => env('SQS_QUEUE', 'default'),
    'suffix' => env('SQS_SUFFIX'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'after_commit' => false,  // ← SATIR 63
],

'redis' => [
    'driver' => 'redis',
    'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
    'block_for' => null,
    'after_commit' => false,  // ← SATIR 72
],
```

---

## 2. RİSK SENARYOSU

```
TRIGGER: DB::transaction içinde event dispatch ediliyor
    ↓
Transaction başarıyla commit ediliyor
    ✓ Queue job yazılıyor
    → Beklenen davranış ✓

ALTERNATIF SENARYO:
Trigger: DB::transaction rollback (deadlock, constraint, bağlantı kaybı)
    ↓
Transaction ROLLBACK
    ✗ DB değişiklikleri geri alınıyor
    ✗ Ama queue job zaten yazıldı (after_commit=false)
    → Job çalışıyor — veri YOK
    → Projection phantom state
    → Finansal ledger phantom artış
    → CQRS drift kalıcı
```

**Etkilenen kod yolları:**
- `FinancialLedgerService::recordDoubleEntry()` — line 131: event dispatch inside `DB::transaction`
- `ListingProjector` listener — `handleListingCreated/Updated`
- `LeadProjector` listener — `handle()`
- `UpdateLedgerBalanceProjection` listener

---

## 3. ÖNERİLEN DEĞİŞİKLİK

### Tek Değişiklik
Her 4 bağlantıda `after_commit: false` → `after_commit: true`

```php
'database' => [
    // ...
    'after_commit' => true,  // ← SATIR 43
],

'beanstalkd' => [
    // ...
    'after_commit' => true,  // ← SATIR 52
],

'sqs' => [
    // ...
    'after_commit' => true,  // ← SATIR 63
],

'redis' => [
    // ...
    'after_commit' => true,  // ← SATIR 72
],
```

### Alternatif: Ortam Değişkeni ile Yapılandırma
```php
'after_commit' => (bool) env('QUEUE_AFTER_COMMIT', true),
```

---

## 4. GERİYE DÖNÜK UYUMLULUK ETKİSİ

| Alan | Etki | Açıklama |
|------|------|----------|
| Mimari bozulması | **YOK** | Sadece dispatch zamanlaması değişir |
| API breaking change | **YOK** | Public contract değişmiyor |
| Migration gerekli | **YOK** | Sadece config değişikliği |
| Cache etkisi | **YOK** | Config cache yeterli |
| Geri alma (rollback) | **OLUMLU** | Daha güvenli — job sadece commit sonrası çalışır |
| Transaction latency | **Minimal** | Job dispatch commit sonrası — ~1-5ms ek gecikme |

**Bilinen risk:** Eğer bir kod yolu `DB::transaction` içinde değil ama `after_commit: true` bekliyorsa, job beklenmedik şekilde geç çalışır. Mevcut kod tabanında tüm job dispatch'leri transaction içinde — risk düşük.

---

## 5. MIGRATION ETKİSİ

```bash
# Değişiklik sonrası config cache yenilenmeli
php artisan config:cache

# Queue worker restart gerekiyor (deployment sonrası)
php artisan queue:restart
```

**Etkilenen worker'lar:**
- `php artisan queue:work` (tüm bağlantılar)
- Supervisor/PM2 konfigürasyonunda değişiklik gerekmez

---

## 6. ROLLBACK PLANI

```bash
# 1. Anında rollback
git checkout HEAD -- config/queue.php

# 2. Config cache temizle
php artisan config:clear
php artisan cache:clear

# 3. Queue worker restart
php artisan queue:restart

# 4. DLQ kontrol et
php artisan queue:failed
```

---

## 7. TEST PLANI

### Değişiklik Öncesi Test (Baseline)
```bash
# 1. Mevcut test suite çalıştır
php artisan test --filter=Queue

# 2. DLQ boş olduğunu doğrula
php artisan queue:failed
# Beklenen: 0 failed jobs

# 3. Ledger test
php artisan test --filter=LedgerTest
```

### Değişiklik Sonrası Test (Regression)
```bash
# 1. Config cache yenile
php artisan config:cache

# 2. Queue worker restart
php artisan queue:restart

# 3. Tüm test suite
php artisan test

# 4. Manuel senaryo: DB transaction rollback → job çalışmamalı
# (Integration test — otomatik)

# 5. LedgerTest tekrar
php artisan test --filter=LedgerTest

# 6. ProjectionTest
php artisan test --filter=ProjectionTest
```

### Başarı Kriterleri
- [ ] Tüm test suite PASS
- [ ] `php artisan queue:failed` → 0 yeni hata
- [ ] `php artisan test --filter=LedgerTest` → PASS
- [ ] `php artisan test --filter=ProjectionTest` → PASS
- [ ] API response time değişmemiş (baseline ile karşılaştır)

---

## 8. BAŞARI KRİTERİ

| Kriter | Hedef | Doğrulama |
|--------|-------|-----------|
| after_commit | 4/4 `true` | `grep -n "after_commit" config/queue.php` |
| Mimari bozulma | 0 | `php artisan test` → PASS |
| DLQ flood | 0 yeni hata | `php artisan queue:failed` |
| Ledger consistency | PASS | `php artisan test --filter=LedgerTest` |
| Projection health | PASS | `php artisan test --filter=ProjectionTest` |

---

## 9. ÖNCELİK SIRASI: 1 / 3

> **Not:** En düşük riskli, en yüksek kazançlı P0 düzeltmesi.
> Mimari bozulma yapmaz. Sadece dispatch zamanlamasını düzeltir.
> `config:cache` + `queue:restart` ile anında deploy edilir.

**Tahmini emek:** 1 saat (doğrulama + uygulama + test)
