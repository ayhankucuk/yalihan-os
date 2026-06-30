# SPRINT 4 — RELIABILITY FOUNDATION
## Yalıhan Emlak AI OS — Sprint 4 Planı
**Tarih:** 2026-06-29
**Hedef:** Transactional Outbox + Projector Resilience + AI Safety
**Süre:** 2 hafta (2026-06-29 — 2026-07-13)
**Toplam iş:** ~38 saat (P1: 1 item · P2: 7 item)
**Bloker:** Yok — Sprint 3.1 Extended başarıyla kapandı

---

## MEVCUT DURUM

Sprint 3.1 Extended kapanışından sonra:
- `after_commit: true` ✅ (tüm 4 bağlantı)
- `Sanctum expiration: 43200` ✅
- V2 IDOR fix ✅
- SAB integrity ✅
- 16 pre-existing test fail → Sprint 4 içinde izleniyor

---

## SPRINT 4 — PACKAGE ÖNCELİK SIRASI

### Wave A — Financial Integrity (En Kritik)
| ID | Package | Bulgu | Tahmini | Öncelik |
|----|---------|-------|--------|----------|
| S4-R01 | `implementation/S4-R01_TRANSACTIONAL_OUTBOX.md` | P1-G04: FinancialLedgerService outbox pattern | 8h | **P1** |

### Wave B — Projector Resilience (Queue Safety)
| ID | Package | Bulgu | Tahmini | Öncelik |
|----|---------|-------|--------|----------|
| S4-R06 | `implementation/S4-R06_SYNC_LISTING_PROJECTION_JOB.md` | P2-G08: $tries, $backoff, failed() | 2h | **P2** |
| S4-R05 | `implementation/S4-R05_LISTING_PROJECTOR_TRY_CATCH.md` | P1-G05: try/catch + $tries + $backoff | 2h | **P1** |
| S4-R07 | `implementation/S4-R07_LEAD_PROJECTOR_TRY_CATCH.md` | P1-G06: try/catch + $tries + $backoff | 2h | **P1** |
| S4-R10 | `implementation/S4-R10_PROCESS_PROJECTION_DLQ.md` | P2-G10: DLQ routing | 2h | **P2** |

### Wave C — AI Safety (Cascading Failure)
| ID | Package | Bulgu | Tahmini | Öncelik |
|----|---------|-------|--------|----------|
| S4-R08 | `implementation/S4-R08_FINANCE_PROCESSOR_AI_FAIL.md` | P2-G05: YalihanCortex routing + timeout | 4h | **P2** |
| S4-R11 | `implementation/S4-R11_DUAL_AI_BUDGET_GUARD.md` | P2-G01: Konsolide AiBudgetGuard | 3h | **P2** |

### Wave D — Circuit Breaker (Opsiyonel — Zaman varsa)
| ID | Package | Bulgu | Tahmini | Öncelik |
|----|---------|-------|--------|----------|
| S4-R09 | `implementation/S4-R09_CIRCUIT_BREAKER_DURABILITY.md` | P2-G07: Cache → DB-backed atomic | 4h | **P3** |

---

## DETAY PAKETLERİ

### Wave A — Financial Integrity

#### S4-R01: Transactional Outbox (P1-G04)
**Bulgu:** `FinancialLedgerService.php:131` — Event transaction içinde dispatch ediliyor
**Risk:** Rollback sonrası projektor phantom balance artışı
**Tahmini:** 8 saat
**Bağımlılık:** Yok (after_commit zaten true)
**Package:** `audits/implementation/S4-R01_TRANSACTIONAL_OUTBOX.md`

#### Neden 8 saat?
- Outbox tablo migration + model
- Event → outbox yazma refactor
- Outbox dispatcher (cron/command)
- İdentifikasyon anahtarı ile exactly-once processing
- LedgerTest yazma
- Staging doğrulama

---

### Wave B — Projector Resilience

#### S4-R06: SyncListingProjectionJob (2h)
**Bulgu:** `$tries` yok, `$backoff` yok, `failed()` yok
**Risk:** DB hatasında anında DLQ, forensic log yok
**Package:** `audits/implementation/S4-R06_SYNC_LISTING_PROJECTION_JOB.md`

#### S4-R05: ListingProjector try/catch (2h)
**Bulgu:** `ListingProjector.php:21` — try/catch yok
**Risk:** DLQ flood, olay kaybı
**Package:** `audits/implementation/S4-R05_LISTING_PROJECTOR_TRY_CATCH.md`

#### S4-R07: LeadProjector try/catch (2h)
**Bulgu:** `LeadProjector.php:20` — try/catch yok, $tries yok
**Risk:** DLQ flood
**Package:** `audits/implementation/S4-R07_LEAD_PROJECTOR_TRY_CATCH.md`

#### S4-R10: ProcessProjectionJob DLQ Routing (2h)
**Bulgu:** `ProcessProjectionJob.php:128` — failed() comment'te DLQ'tan bahsediyor, kod yok
**Risk:** DLQ routing disconnected
**Package:** `audits/implementation/S4-R10_PROCESS_PROJECTION_DLQ.md`

---

### Wave C — AI Safety

#### S4-R08: FinanceProcessor AI Fail Safety (4h)
**Bulgu:** `FinanceProcessor.php:~144` — GPT-4o doğrudan çağrılıyor, timeout yok
**Risk:** GPT-4o down → Telegram webhook timeout → re-delivery storm
**Package:** `audits/implementation/S4-R08_FINANCE_PROCESSOR_AI_FAIL.md`

#### S4-R11: Dual AiBudgetGuard Konsolidasyon (3h)
**Bulgu:** İki ayrı `AiBudgetGuard` impl. — double-spend riski
**Risk:** Aynı tenant kredileri iki kat harcayabilir
**Package:** `audits/implementation/S4-R11_DUAL_AI_BUDGET_GUARD.md`

---

### Wave D — Circuit Breaker (Zaman varsa)

#### S4-R09: CircuitBreaker Durability (4h)
**Bulgu:** `CircuitBreaker.php` — Cache-backed, Redis flush = tüm devreler reset
**Risk:** Redis restart sonrası başarısız provider "kurtarılmış" görünür
**Package:** `audits/implementation/S4-R09_CIRCUIT_BREAKER_DURABILITY.md`

---

## BAŞARI KRİTERLERİ

| Metrik | Başlangıç | Sprint 4 Hedef |
|--------|-----------|----------------|
| after_commit | ✅ PASS | ✅ PASS (kalıcı) |
| Transactional Outbox | ❌ Yok | ✅ Var |
| ListingProjector try/catch | ❌ Yok | ✅ Var |
| LeadProjector try/catch | ❌ Yok | ✅ Var |
| SyncListingProjectionJob $tries | ❌ Yok | ✅ Var |
| FinanceProcessor resilience | ❌ Yok | ✅ YalihanCortex routing |
| Dual AiBudgetGuard | ❌ 2 impl. | ✅ 1 konsolide |
| SAB integrity | ✅ PASS | ✅ PASS |
| DLQ routing | ⚠️ Disconnected | ✅ Connected |

---

## GATE PROTOKOLÜ (Her Package Sonrası)

```bash
php artisan config:clear
php artisan test --filter=LedgerTest
php artisan test --filter=ProjectionTest
php artisan sab:integrity-scan
```

---

## Chief AI Karar Noktaları

> **D-12**: Sprint 4 öncelik sırası: S4-R06 (SyncListingProjectionJob, 2h) → S4-R05 (ListingProjector, 2h) → S4-R07 (LeadProjector, 2h) → S4-R01 (Transactional Outbox, 8h) → S4-R08 (FinanceProcessor, 4h) → S4-R11 (AiBudgetGuard, 3h)
>
> **D-13**: Transactional Outbox (S4-R01) en kritik ama en uzun iş. 8 saat ayrılmalı. Parçalar halinde: (1) Outbox tablo + model, (2) Event → outbox refactor, (3) Dispatcher command, (4) Test. Her parça ayrı commit.
>
> **D-14**: S4-R09 (CircuitBreaker) 4 saat — zaman varsa ekle, yoksa Sprint 5'e taşı.
>
> **D-15**: Pre-existing 16 test fail → sprint içinde izleniyor, kapatma kriteri değil. Ayrı backlog item olarak taşındı.

---

## SPRINT 4 CLOSURE GATE

```bash
# Tüm wave'ler tamamlandıktan sonra:
php artisan sab:integrity-scan        # PASS
php artisan queue:failed             # 0 yeni hata
php artisan projection:health         # HEALTHY
php artisan test --filter=LedgerTest # PASS
php artisan test --filter=ProjectionTest # PASS
```

*Sonraki güncelleme: Her package kapanışında*
