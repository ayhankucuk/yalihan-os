# CHANNEL_MANAGER_PROVIDER Wave 3 — Reservation Lifecycle Ingest Discovery Charter

**Status:** 🟡 DISCOVERY
**Charter Date:** 2026-08-10
**SAAB Authorization:** Wave 2 Certification Closure (9ede781)
**Prerequisite:** ✅ CHANNEL_MANAGER_PROVIDER Wave 2 CERTIFIED
**Scope:** Architecture only — no implementation

---

## Mission

Channex üzerinden gelen **rezervasyon modifikasyon** ve **rezervasyon iptal** webhook'larını
YALIHAN'ın canonical reservation state machine'ine güvenli, idempotent ve tenant-isolated biçimde
bağlamak için mimari sınırları netleştirmek.

**SAAB Constraint:** Channel Manager rezervasyon state'inin sahibi değildir. Modification ve
cancellation kararları canonical `ReservationService` zincirine aittir. Wave 3 yalnızca
dış payload'ı normalize edip doğru canonical komutu çağıran ince bir layer tanımlar.

---

## SAAB Başarı Sorusu

> "YALIHAN, Channex'ten gelen rezervasyon modifikasyonu veya iptali webhook'unu canonical
> reservation state machine'i üzerinden tenant-safe, idempotent ve replay-aware biçimde
> işleyebiliyor mu — Channel Manager'ı rezervasyon sahibi yapmadan?"

---

## Kapsam İçi

1. **Modification webhook ingest**
   - Channex `reservation_updated` action → canonical date/guest update
   - Mevcut rezervasyonu bul (`external_reservation_id`), güncelle
   - Conflict detection integration (tarih değişikliğinde)

2. **Cancellation webhook ingest**
   - Channex `reservation_cancelled` action → `ReservationService.cancelReservation()`
   - Idempotent: zaten cancelled ise no-op
   - Availability release via canonical chain

3. **Idempotency — Duplicate delivery**
   - Aynı modification webhook iki kez gelirse no-op
   - Aynı cancellation webhook iki kez gelirse no-op

4. **Out-of-order webhook handling**
   - Modification sonra create gelirse → conflict davranışı
   - Cancellation sonra modification gelirse → ignore (terminal state)

5. **Tenant isolation**
   - Tenant A cancellation webhook'u Tenant B rezervasyonunu etkileyemez

6. **Retry / replay safety**
   - Webhook yeniden gönderilebilir; idempotency garantisi

7. **ADR-008 taslağı**
   - Lifecycle ingest boundary kararları

## Kapsam Dışı

- Rezervasyon creation (Wave 2)
- Pricing sync
- Guest messaging (EX-001)
- Booking.com lifecycle webhooks
- Partial modification (guest count only) — bu wave full date+guest normalize

---

## Mevcut Durum Analizi

### Wave 2'den Gelen Altyapı (Var Olan)

| Bileşen | Dosya | Wave 3 Relevansı |
|---------|-------|-----------------|
| `ChannexWebhookController` | `handle()` | Genişletilecek: `reservation_updated` + `reservation_cancelled` action'ları |
| `ChannexReservationPayload` | DTO | Modification payload için extend edilmeli |
| `ChannexReservationIngestService` | `ingest()` | Yeni metodlar: `ingestModification()`, `ingestCancellation()` |
| `ChannexReservationIngestJob` | Queue job | Yeni job: `ChannexReservationModifyJob`, `ChannexReservationCancelJob` |
| `ReservationService.cancelReservation()` | L144 | ✅ Mevcut, idempotent |
| `external_reservation_id` kolonu | Migration | ✅ Mevcut |

### Kritik Bulgu 1: `ReservationService.cancelReservation()` Zaten Var ve Idempotent

```php
// ReservationService.php:144-167
public function cancelReservation(int $reservationId): void {
    DB::transaction(function () use ($reservationId) {
        $reservation = PropertyReservation::lockForUpdate()->findOrFail($reservationId);
        if ($reservation->reservation_state === 'cancelled') {
            return; // Idempotent behaviour ✅
        }
        $reservation->update(['reservation_state' => 'cancelled', ...]);
        // Availability release ✅
    });
}
```

Wave 3 cancellation ingest: `external_reservation_id` → `reservationId` → `cancelReservation()`.

### Kritik Bulgu 2: `ReservationService.createReservation()` Modification'ı Desteklemiyor

Mevcut `ReservationService` modification endpoint'i yok. Wave 3 seçenekelri:

**Seçenek A:** `ChannexReservationIngestService.ingestModification()` doğrudan DB güncelleme yapar
(ADR-007 canonical chain prensibi ihlali — NOT PREFERRED)

**Seçenek B:** `ReservationService`'e `modifyReservation()` metodu eklenir
(canonical chain genişler — PREFERRED)

**ADR-008 Kararı:** Seçenek B. `modifyReservation(reservationId, newStartDate, newEndDate, guestData)`
ReservationService'e eklenir. Conflict detection bu metod içinde çalışır.

### Kritik Bulgu 3: Out-of-Order Webhook Davranışı

| Senaryo | Davranış |
|---------|---------|
| Modification → cancelled reservation | Ignore (log warning, return existing) |
| Cancellation → pending/confirmed | `cancelReservation()` |
| Duplicate modification | Idempotency key ile no-op |
| Unknown `external_reservation_id` | Log warning + 200 OK (Channex retries otherwise) |

### Kritik Bulgu 4: Channex Webhook `action` Alanı

Wave 2'de sadece `action=new` işlendi. Wave 3'te:
```json
{
  "event": "reservation",
  "action": "new" | "modified" | "cancelled",
  "reservation": { ... }
}
```

`ChannexWebhookController.handle()` action routing eklenecek.

---

## Önerilen Mimari (Wave 3)

```
POST /api/v1/webhook/channex
    │
    ├─ action='new'       → ChannexReservationIngestJob (Wave 2)
    ├─ action='modified'  → ChannexReservationModifyJob  ← NEW
    └─ action='cancelled' → ChannexReservationCancelJob  ← NEW

ChannexReservationModifyJob
    └─ ChannexReservationIngestService.ingestModification()
            └─ find by external_reservation_id
            └─ ReservationService.modifyReservation()  ← NEW canonical method
                    └─ ConflictDetectionContract (if date change)
                    └─ AvailabilityProjectionService (release old, block new)

ChannexReservationCancelJob
    └─ ChannexReservationIngestService.ingestCancellation()
            └─ find by external_reservation_id
            └─ ReservationService.cancelReservation()  ← existing
```

### Yeni Dosyalar (Wave 3 Implementation)

```
app/Jobs/ChannelManager/
  ChannexReservationModifyJob.php        ← async queue job
  ChannexReservationCancelJob.php        ← async queue job

app/Events/ChannelManager/
  ChannexReservationModifiedEvent.php    ← domain event
  ChannexReservationCancelledViaChanEvent.php  ← domain event

app/Services/ReservationService.php     ← MODIFY: add modifyReservation()

app/Services/ChannelManager/ChannexReservationIngestService.php
                                         ← MODIFY: add ingestModification(), ingestCancellation()

app/Http/Controllers/Api/ChannexWebhookController.php
                                         ← MODIFY: action routing

docs/adrs/ADR-008-Channex-Reservation-Lifecycle.md
```

---

## ADR-008 Taslağı

**Başlık:** Channex Reservation Lifecycle Ingest — Modification & Cancellation

**Kararlar:**
1. `action='new'` → Wave 2 path (unchanged)
2. `action='modified'` → `ChannexReservationModifyJob` → `ReservationService.modifyReservation()`
3. `action='cancelled'` → `ChannexReservationCancelJob` → `ReservationService.cancelReservation()`
4. `ReservationService.modifyReservation()` canonical — conflict detection içinde çalışır
5. Channel Manager state sahibi değildir — sadece dış payload'ı normalize eder
6. Out-of-order: terminal state'teki reservation modification → ignore + log (no exception)
7. Unknown `external_reservation_id` → 200 OK + warning log (Channex'e 4xx vermemek)
8. Idempotency: modification için `(external_reservation_id, channel, modification_hash)` tuple

---

## SAAB Başarı Kriterleri (Discovery Kapanışı)

| # | Kriter | Durum |
|---|--------|-------|
| W3-D1 | Channex `action` routing analizi tamamlandı | ✅ |
| W3-D2 | `ReservationService.cancelReservation()` canonical path doğrulandı | ✅ |
| W3-D3 | `modifyReservation()` gap tespit edildi → Wave 3 scope | ✅ |
| W3-D4 | Out-of-order webhook davranışı netleşti | ✅ |
| W3-D5 | Idempotency scheme for modification belirlendi | ✅ |
| W3-D6 | ADR-008 taslağı oluşturuldu | ✅ |
| W3-D7 | Yeni dosya listesi netleşti | ✅ |

---

## Referanslar

- `app/Http/Controllers/Api/ChannexWebhookController.php`
- `app/Services/ChannelManager/ChannexReservationIngestService.php`
- `app/Services/ReservationService.php` (L144: cancelReservation)
- `docs/adrs/ADR-007-Channel-Manager-Webhook-Ingest.md`
- `docs/sprints/CHANNEL_MANAGER_PROVIDER_WAVE2_DISCOVERY.md`
