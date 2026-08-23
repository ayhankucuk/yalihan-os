# C — YDL Financial Closure Discovery
**Baseline:** `667c1b4`
**Mode:** READ ONLY
**Date:** 2026-08-22
**Agents:** Phase 1 (file map) + Phase 2 (event trace) + Phase 3 (components + state)

---

## 1. Executive Verdict

**Mevcut durum:** YALIHAN'ın double-entry ledger sistemi SAB Phase 12/15'te tamamlanmış durumda. Rezervasyon lifecycle event'leri ledger'a bağlı. Ancak **financial closure eksik** — sistem booking amount'u kaydediyor ama PAID/COMPLETED/RECONCILIATION/PAYOUT/OWNER_PAYABLE halkaları **manuel operator action gerektiriyor.

**En kritik bulgular:**
- `ReservationCompletedEvent`'in financial ledger action'ı yok — sadece Gorev yaratıyor
- Komisyon hesaplama iki ayrı modelde (`Finance/Commission` + `Finans/Models/Komisyon`) — parallel domain, reconcile yok
- `afterCommit()` yok — ledger event queue'a commit öncesi dispatch ediliyor — crash = ledger/projection inconsistency riski
- Payout/settlement manuel — YalihanTreasury API'si var ama otomatik tetikleyici yok
- Tax/KDV hesaplanıyor ama ledger'a yazılmıyor

**Küçük implementasyonla kapanabilecek en değerli halka:** `ReservationCompletedEvent` → payout-ready state + agent komisyon hesaplama + YalihanTreasury ledger write tetiklemesi.

---

## 2. Current Finance Architecture

```
PropertyPricingService (hardcoded rates: TR=8%, GR=5%, UK=6% — bypasses CountryFinancialRule)
        ↓
CountryFinancialService (DB rates: commission_rate, advisory_fee_rate, tax_rate)
        ↓
FinancialLedgerService (canonical double-entry ledger — SAB Phase 15)
  recordReservationInitialBooking()  → Misafir Alacakları DR / Konaklama Gelirleri CR
  recordReservationCancellation()    → reversal (accounts swapped)
  recordDepositTransaction()        → Kasa DR / Depozito Yükümlülüğü CR
  recordDepositRefund()              → Depozito Yükümlülüğü DR / Kasa CR
        ↓
  LedgerDoubleEntryRecorded event
        ↓
  UpdateLedgerBalanceProjection listener (sync, pessimistic lock)
        ↓
LedgerEntry (immutable, UUID, TRY base, FX locked)
LedgerBalance (CQRS read model, optimistic versioning)
YalihanTreasury (orchestrator — delegate to CommissionCalculator + TransactionService + BonusCalculator)
```

### 3. Reservation → Finance Event Map

| Event | Listener | Job | Ledger Action | finansal_durum |
|---|---|---|---|---|
| `ReservationCreatedEvent` | ListenReservationCreated | ProcessReservationCreated → `recordReservationInitialBooking()` | Misafir Alacakları DR / Gelir CR | → PENDING |
| `ReservationCancelledEvent` | ListenReservationCancelled | ProcessReservationCancelled → `recordReservationCancellation()` | Gelir DR / Alacak CR (reversal) | → CANCELLED |
| `ReservationCompletedEvent` | ListenReservationCompleted | Gorev yaratma only | **YOK** | **YOK** |
| `ReservationCheckedInEvent` | — | — | YOK | YOK |
| `ReservationCheckedOutEvent` | — | — | YOK | YOK |

**CRITICAL GAP:** `ReservationCompletedEvent` financial action TETİKLEMİYOR.

### 4. Ledger Integrity Assessment

| Invariant | Status | Kanıt |
|---|---|---|
| Double-entry (Debit=Credit) | ✅ TAMAM | `recordDoubleEntry()` satır 124 |
| Immutable LedgerEntry | ✅ TAMAM | `$timestamps = false`, `$fillable` geniş değil |
| Cancellation = reversal, not mutation | ✅ TAMAM | Ters kayıt yeni UUID üretiyor |
| Replay ≠ economic duplication | ✅ TAMAM | Idempotency key üç katlı: ledger_transactions + LedgerEntry semantic check + lockForUpdate |
| Currency preserved | ✅ TAMAM | `currency` + `booking_currency` + `locked_fx_rate` tüm akışlarda |
| Tenant isolation | ✅ TAMAM | `AuthorityLeakageException` FLS satır 121 |
| Atomic writes | ✅ TAMAM | Tüm mutating method'lar `DB::transaction()` içinde |
| **afterCommit on event dispatch** | ⚠️ **EKSİK** | Event `DB::transaction()` içinde dispatch ediliyor. Transaction rollback sonrası queue'a kayıt riski |
| LedgerEntry idempotency pre-check | ✅ TAMAM | LedgerEntry semantic LIKE check + ledger_transactions unique key |
| Reversal idempotency | ✅ TAMAM | Cancellation pre-checks for existing reversal |
| Append-only ledger balance | ✅ TAMAM | LedgerEntry immutable; LedgerBalance upsert |

### 5. Financial Component Matrix

| Bileşen | Durum | Not |
|---|---|---|
| Gross booking amount | ✅ TAMAM | `islem_tutari` / `total_amount` — B1 ile Channel Manager'dan geliyor |
| Channel fee | ❌ EKSİK | Booking.com/Channex komisyonu ayrı ledger entry olarak kaydedilmiyor |
| YALIHAN commission | ⚠️ KISMEN | `CommissionCalculator` var ama otomatik tetiklenmiyor |
| Owner payable | ❌ EKSİK | Manuel hesaplama + manuel ödeme |
| VAT / KDV | ⚠️ KISMEN | `CountryFinancialService.calculateTax()` hesaplıyor ama ledger'a YAZMIYOR |
| Refund | ✅ TAMAM | `recordDepositRefund()` + `recordReservationCancellation()` reversal |
| Payment received | ❌ EKSİK | `finansal_durum = PAID` geçişi manuel tetikleniyor |
| Owner payout | ❌ EKSİK | `YalihanTreasury::requestCommissionPayment()` var ama otomatik DEĞİL |
| Settlement | ❌ EKSİK | Reconciliation altyapısı yok |
| Reconciliation | ❌ EKSİK | Manuel banka mutabakatı gerekiyor |
| Channel service commission | ❌ EKSİK | Booking.com vs Airbnb farklı commission yapıları ayrıştırılmamış |
| Audit trail immutable | ✅ TAMAM | LedgerEntry timestamps=false |
| Idempotency | ✅ TAMAM | ledger_transactions idempotency key |
| Multi-currency | ✅ TAMAM | FX lock + base_amount TRY |
| Retry/safe replay | ✅ TAMAM | 3 katlı idempotency |

### 6. Financial State Machine

```
Rezervasyon Oluştu:
  finansal_durum = PENDING
  LedgerEntry: Alacak DR / Gelir CR (booking amount)
        ↓
Rezervasyon Onaylandı (operator action):
  finansal_durum = CONFIRMED (transitionToConfirmed())
        ↓
Ödeme Alındı (operator action):
  finansal_durum = PAID (operator manually transitions)
  + recordDepositTransaction() → Kasa DR / Depozito CR
        ↓
Check-in (sistem):
  reservation_state → CHECKED_IN
  Gorev yaratılıyor
        ↓
Check-out (sistem):
  reservation_state → CHECKED_OUT
        ↓
Rezervasyon Tamamlandı (ReservationCompletedEvent):
  Gorev tamamlanıyor
  FINANSiYEL HİÇBİR ŞEY OLMUYOR ⚠️
        ↓
Komisyon Hesaplanması (operator/tahmin):
  CommissionCalculator.calculateCommissionProjection()
        ↓
Komisyon Onayı (operator):
  finansal_durum = APPROVED (YalihanTreasury)
        ↓
Komisyon Ödemesi (operator):
  LedgerEntry: Sistem DR / Danışman CR
  finansal_durum = PAID
```

**Mevcut terminal state:** Gorev tamamlanması = `CHECKED_OUT` → Rezervasyon tamamlandı ama financial closure MANUEL.

### 7. Modification Behaviour

**Mevcut durum (ReservationModifiedEvent):**
`modifyReservation()` sadece tarih/guest bilgisi güncelliyor. `total_amount` / `islem_tutari` financial update yapılabilir (B1 ile altyapı hazır) ama otomatik tetiklenmiyor.

**Sorulan senaryo: 7 gece → 8 gece / €5000 → €5800:**
Repository'de mevcut modification → financial adjustment pipeline YOK. Operator'ın manuel intervention gerekiyor.

### 8. Cancellation/Reversal Behaviour

```
ReservationCancelledEvent
        ↓
ProcessReservationCancelled
        ↓
FinancialLedgerService.recordReservationCancellation()
  1. LedgerEntry kontrolü: "Rezervasyon Konaklama Kaydı" var mı? (satır 363)
     → Yoksa: sadece finansal_durum = CANCELLED (satır 364-365)
  2. Ters kayıt kontrolü: "Rezervasyon İptal İadesi" var mı? (satır 369-378)
     → Varsa: return null (idempotent)
  3. Yeni reversal entry: Gelir DR / Alacak CR
        sebep: "Rezervasyon İptal İadesi / Ters Kayıt #{$id}
        transaction_group_id: yeni UUID
        currency: orijinal ile aynı
        base_amount: orijinal ile aynı (farklı FX rate değil
```

**Audit korunur:** Orijinal booking entry silinmez — yeni reversal UUID'li entry oluşur. Ledger tamamen immutable.

**Duplicate cancellation:** İkinci iptal çağrısı `null` döner, ledger'a yazılmaz. Ekonomi etkisi yok.

### 9. Completion/Settlement Behaviour

`ReservationCompletedEvent`'in financial action'ı YOK. Sadece Gorev (operational task) yaratılıyor. Financial closure manuel:

1. Operator: `ReservationCompletedEvent` tetiklendi bilgisini görür
2. Operator: `finansal_durum = CONFIRMED` transition yapar
3. Operator: `KomisyonHesaplamaService`'e gider
4. Operator: `requestCommissionPayment()` çağırır
5. Operator: Banka transferini manuel yapar

### 10. Tenant Isolation Evidence

| Alan | Kanıt |
|---|---|
| LedgerEntry write | `tenant_id` zorunlu — AuthorityLeakageException cross-tenant yakalar |
| Commission write | `tenant_id` factory otomatik set ediyor |
| Payout write | `YalihanTreasury` tenant context'e bağlı |
| Balance read | `lockForUpdate` tenant-scoped |
| `Finance/Commission` model | Tenant-scoped scope mevcut |
| `Finans/Komisyon` legacy | `tenant_id` importunu controller'dan alıyor |
| **Eksik izolasyon:** `Commission` (Finance) modeli — tenant isolation TESTİ YOK (sadece legacy `Komisyon` testi var) |

### 11. Idempotency & Replay Evidence

```
recordReservationInitialBooking idempotency:
  1. ledger_transactions.idempotency_key = "reservation_booking_{id}_{tenantId}" (unique)
  2. LedgerEntry semantic check: sebep LIKE '%Rezervasyon Konaklama Kaydı%'
  3. lockForUpdate account lock

recordReservationCancellation idempotency:
  1. ledger_transactions idempotency key = "reservation_cancel_{id}_{tenantId}"
  2. LedgerEntry reversal check: sebep LIKE '%İptal İadesi%'
  3. Initial entry exists check (no initial = just state transition)

Economic duplication: KESİNLİKLE YOK
  → Her kayıt UUID-based yeni transaction_group_id üretiyor
  → Birden fazla debit/credit satırı oluşturulamıyor (unique key)
```

### 12. Transaction/Queue Safety

| Kontrol | Durum | Satır |
|---|---|---|
| LedgerEntry write inside transaction | ✅ | `DB::transaction()` satır 283 |
| LedgerBalance read with lockForUpdate | ✅ | satır 468 |
| Event dispatched inside transaction | ⚠️ **EKSİK | Event payload hazır ama `afterCommit()` yok |
| Listener queued after commit | ⚠️ **EKSİK | Event listener immediate dispatch |
| Job retry safe (event replay) | ✅ | 3 katlı idempotency |
| Cache invalidation on update | ✅ | `Cache::forget()` |
| LedgerDoubleEntryRecorded listener sync | ✅ | UpdateLedgerBalanceProjection sync |

**Gap (CRITICAL):** `LedgerDoubleEntryRecorded` event'i `DB::transaction()` içinde dispatch ediliyor. Transaction rollback olursa event listener queue'a ulaşmış olabilir ve projection yanlış state'de kalır. `ShouldBroadcast` / `afterCommit` pattern eksik.

### 13. Test Coverage Matrix

| Senaryo | Test Dosyası | Durum |
|---|---|---|
| Double-entry debit=credit invariant | `FinancialLedgerServiceTest` | ✅ |
| Cancellation reversal (append-only) | `ReservationEndToEndLifecycleTest` | ✅ |
| Idempotency (booking + cancellation) | `ReservationEndToEndLifecycleTest` + `FinancialLedgerServiceTest` | ✅ |
| Tenant isolation ledger | `ReservationEndToEndLifecycleTest` | ✅ |
| Channel Manager financial persistence | `AirbnbInboundLifecycleTest::B4` | ✅ |
| Legacy Finansal işlem CRUD | `FinanceIntegrityTest` | ✅ |
| `ReservationCompletedEvent financial action | YOK | ❌ EKSİK |
| PAID/CONAYLANDI/ÖDENDI state transitions | `YalihanTreasury` smoke test | ⚠️ Manuel action gerekiyor |
| Multi-currency FX locking | Yok | ❌ EKSİK |
| Audit trail integrity | Yok | ❌ EKSİK |
| `Finansal_durum = PAID` ledger write | YOK | ❌ EKSİK |
| Payout + owner payable | YOK | ❌ EKSİK |
| Reconciliation | YOK | ❌ EKSİK |
| `Commission` (Finance) tenant isolation | YOK | ❌ EKSİK |
| Tax/KDV ledger write | YOK | ❌ EKSİK |

---

## 14. Manual Financial Operations Remaining

Operator (Ayhan/danışman) bugün hangi finansal adımları MANUEL yapıyor?

| Adım | Nerede | Risk |
|---|---|---|
| `finansal_durum = CONFIRMED` geçişi | Admin panel / Controller | Operator unutabilir |
| `finansal_durum = PAID` geçişi | Admin panel / Controller | Operator unutabilir |
| Komisyon hesaplama | Admin panel → Finance | Operator hesaplıyor |
| Komisyon onaylama | Admin panel → Finance | Operator tetikliyor |
| Banka transfer bildirimi | Manuel WhatsApp/e-posta | Operator izliyor |
| `ReservationCompletedEvent financial action | Manuel | Tamamen operator action |
| Payout reconciliation | Manuel banka ekstresi | Operator mutabakat yapıyor |

## 15. True Gaps

| # | Gap | Öncelik | Tür |
|---|---|---|---|
| G1 | `ReservationCompletedEvent` → financial closure yok | **CRITICAL** | Architecture |
| G2 | `afterCommit()` eksikliği — event queue ordering | **HIGH** | Concurrency Safety |
| G3 | Payout/settlement halkası manuel | **HIGH** | UX gap |
| G4 | Tax/KDV ledger'e yazılmıyor | MEDIUM | Data gap |
| G5 | `Finance/Commission` tenant isolation testi yok | MEDIUM | Test gap |
| G6 | Dual komisyon modeli (`Finans/Komisyon` + `Finance/Commission`) | MEDIUM | Debt |
| G7 | Hardcoded `PropertyPricingService` rates bypasses DB config | LOW | Technical debt |
| G8 | `REJECTED` komisyon state — recovery path yok | LOW | Business logic gap |
| G9 | Bank transfer entity yok | LOW | Data gap |
| G10 | Channel-specific commission (Airbnb vs Booking) ayrıştırması | LOW | Scope |

## 16. Existing Debt

| Debt | Dosya | Öncelik |
|---|---|---|
| `property_availabilities` unique constraint — external block insert fails | `YdlReservationCancellationTest::r2-t9` | MEDIUM |
| `ReservationCompletedEvent` financial action | Yukarı G1 | CRITICAL |
| Dual komisyon model | Yukarı G6 | MEDIUM |
| `afterCommit()` eksikliği | Yukarı G2 | HIGH |

## 17. Minimum Implementation Scope

**Tek bir küçük implementation ile kapanabilecek en değerli halka:**

```
ReservationCompletedEvent
        ↓
ProcessReservationCompleted (yeni Job)
        ↓
YalihanTreasury.requestCommissionPayment() tetiklemesi
        ↓
Veya:
ReservationCompletedEvent
        ↓
CommissionCalculator.calculateCommissionProjection() otomatik çağrı
        ↓
Komisyon kaydı oluştur — operator onay bekliyor (insan onayı korunuyor)
```

**Bunu yapmadan önce:** `ReservationCompletedEvent`'in ne zaman tetiklendiğini doğrulamak gerekiyor (cron/job/manual).

## 18. Recommended Waves

### C1: Financial Closure Completion Trigger
`ReservationCompletedEvent` → financial chain completion
- **Mevcut:** Sadece Gorev yaratılıyor
- **Sonra:** `finansal_durum = CONFIRMED` otomatik tetiklenir
- **Risk:** Düşük — sadece state transition
- **Test:** Mevcut chain üzerine tek job eklemek

### C2: Payout Readiness Notification
Operator'a ödeme zamanı geldi bildirimi
- **Mevcut:** Operator takip ediyor
- **Sonra:** `finansal_durum = CONFIRMED` → bildirim
- **Risk:** Çok düşük — sadece notification
- **Değer:** Operator manuel kontrolü azaltır

### C3: `afterCommit` Queue Safety
Ledger event'lerinin transaction commit sonrası queue'lanması
- **Mevcut:** Event transaction içinde dispatch
- **Sonra:** `::dispatch()->afterCommit()` — LedgerDoubleEntryRecorded
- **Risk:** Orta — concurrency fix ama mevcut kod zaten çalışıyor
- **Alternatif:** SAAB onayı ile beklet

### C4: Channel Fee Separation
Booking.com komisyonu ayrı ledger satırı
- **Mevcut:** Toplam tutar tek entry
- **Sonra:** Channel fee ayrı ledger entry + commission model
- **Risk:** Orta — Yeni ledger entry tipi

### C5: Settlement Reconciliation
Bank transfer + ledger mutabakatı
- **Mevcut:** Manuel
- **Sonra:** Banka ekstresi import + mismatch detection
- **Risk:** Düşük — yeni modül
- **Önkoşul:** Bank transfer kaydı gerektirir

## 19. GO / HOLD

**SAAB kararı:** GO — ama kapsam sıkı KİLİTLİ

**Minimum kanıt standardı:**
Her financial chain genişlemesi için:
1. Event trigger kanıtı (event aslında dispatch ediliyor mu?)
2. Ledger invariant testi (debit=credit)
3. Idempotency testi
4. Tenant isolation testi
5. `afterCommit` veya documented gap

**C1 — Minimum ilk adım:**
`ReservationCompletedEvent` financial tetikleyicisini yazmak yerine önce mevcut event chain'i doğrulamak — event aslında fırlatılıyor mu? Kanıt yoksa önce pipeline'ı kanıtla.

---

**Sonuç:**
Ledger tamam. Closure eksik. Küçük ama net bir C1 implementasyonu ile başlayalım.
