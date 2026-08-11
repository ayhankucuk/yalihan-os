# Sprint 4.11 — Booking.com Provider Wave 2: Reservation Retrieval + ACK

## Charter

**Sprint:** 4.11
**Start:** 2026-08-11
**Status:** ACTIVE
**Owner:** WenOX / Kilo Agent
**Branch:** `integration/era-v-phase2a-e01`
**Baseline:** `b70c2c2` (Wave 1 CERTIFIED)
**Reference:** `docs/adrs/ADR-009`

---

## Mission

> ADR-009'un en kritik invariant'ını çalışan koda dönüştürmek:
> Retrieve → Canonical Commit → ACK
> ACK ancak ve yalnızca başarılı DB commit sonrasında gönderilir.
> Reservation lifecycle scope: **NEW reservations only.**
> Modification / Cancellation / Recovery → Wave 3.

---

## Scope

### In Scope
- [ ] `BookingReservationPayload` DTO — provider XML → immutable canonical DTO
- [ ] `BookingReservationRetriever` — `GET OTA_HotelResNotif`
- [ ] `BookingReservationAcknowledger` — `POST OTA_HotelResNotif`
- [ ] `BookingReservationIngestService` — normalize → resolve → canonical commit → ACK orchestrator
- [ ] `BookingReservationPollJob` — queue-first polling (20s NOT hardcoded in job)
- [ ] Idempotency — `(external_reservation_id, external_channel)` dedup
- [ ] ACK failure handling — committed reservation rollback edilmez
- [ ] BW2-01..BW2-12 gate tests (12/12 PASS)

### Out of Scope
- [ ] Modification / Cancellation retrieval (`OTA_HotelResModifyNotif`)
- [ ] Recovery polling job (~30 dakika — Wave 3)
- [ ] Availability push
- [ ] Rates push
- [ ] Finance side-effects

---

## Definition of Done

| # | Criterion | Method |
|---|-----------|--------|
| 1 | NEW reservation retrieve → OTA_HotelResNotif endpoint | BW2-01 |
| 2 | Provider payload → canonical DTO normalize | BW2-02 |
| 3 | HotelCode → canonical Property + Tenant resolve | BW2-03 |
| 4 | Unknown HotelCode → reject + NO ACK | BW2-04 |
| 5 | Canonical ReservationService.createReservation kullanılır | BW2-05 |
| 6 | Başarılı commit sonrası ACK gönderilir | BW2-06 |
| 7 | Persistence failure → ACK YOK | BW2-07 |
| 8 | Duplicate reservation → ikinci insert yok | BW2-08 |
| 9 | Duplicate → yine güvenli ACK edilir | BW2-09 |
| 10 | ACK failure → reservation rollback edilmez | BW2-10 |
| 11 | Cross-tenant ingest → ENGELLENİR | BW2-11 |
| 12 | Poll job retry/replay safe | BW2-12 |

---

## ACK Invariant — Zorunlu Kod Yapısı

```
try {
    // 1. Retrieve
    $reservations = $retriever->retrieveNew(...);

    // 2. Normalize
    foreach ($reservations as $raw) {
        $payload = BookingReservationPayload::fromRaw($raw);

        // 3. Resolve
        $ref = $propertyResolver->resolve($tenantId, $payload->hotelCode);
        if ($ref === null) { continue; } // BW2-04

        // 4. Canonical commit
        $reservation = $ingestService->ingest($ref, $payload); // throws on failure

        // 5. ACK — ONLY on success
        $acknowledger->acknowledge($ref->ilanId, $reservation->id, 'NEW');
    }
} catch (PersistenceException $e) {
    // ACK YOK — Booking.com retry bekler
    throw $e;
} catch (AcknowledgementException $e) {
    // Reservation ZATEN commit edilmiş — rollback ETME
    // Booking.com idempotency koruması devrede
    Log::error('ACK failed, reservation committed', [...]);
}
```
