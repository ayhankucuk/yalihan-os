# Sprint 4.10 — Booking.com Provider Wave 1: Auth + Property Mapping

## Charter

**Sprint:** 4.10
**Start:** 2026-08-11
**Status:** ACTIVE
**Owner:** WenOX / Kilo Agent
**Branch:** `integration/era-v-phase2a-e01`
**Baseline:** `8bfee67` (ADR-009 ACCEPTED)
**Reference:** `docs/adrs/ADR-009-Booking.com-Reservation-Provider-Architecture.md`

---

## Mission

> Booking.com Provider için iki temel altyapı sütununu kurmak:
> (1) Machine-account two-legged token auth + secure lifecycle yönetimi
> (2) Booking HotelCode → canonical Ilan + Tenant mapping + tenant isolation
> Reservation retrieval, acknowledgement ve persistence Wave 2'ye aittır.

---

## Scope

### In Scope
- [ ] `BookingAuthTransport` — token acquisition, expiry handling, secure credential resolution, 401 handling
- [ ] `BookingPropertyResolver` — HotelCode → `ilan_id` + tenant isolation
- [ ] `BookingTransport` — authenticated HTTP requests, timeout, retryable classification, telemetry
- [ ] `ChannelReservationContract` — interface boundary (lifecycle metotları boş/stub; retrieval davranışı zorlanmaz)
- [ ] `IlanTakvimSync` token alan migration: `token_access`, `token_refresh`, `token_expires_at`
- [ ] BW1-01..BW1-10 testleri (10/10 gate)

### Out of Scope
- [ ] Reservation retrieval (`GET /reservations`)
- [ ] Canonical persistence (`ReservationService`)
- [ ] Acknowledgement (`POST /reservations/{id}/ack`)
- [ ] Modification / Cancellation
- [ ] Recovery polling job
- [ ] Availability push
- [ ] Rates push
- [ ] Finance side-effects

---

## Definition of Done

| # | Criterion | Method |
|---|-----------|--------|
| 1 | Token auth: valid credentials → access token alınabilir | BW1-01 |
| 2 | Secret handling: credential secret loglanmaz (masked) | BW1-02 |
| 3 | Token lifecycle: geçerli token yeniden kullanılır | BW1-03 |
| 4 | Token expiry: expired token otomatik yenilenir | BW1-04 |
| 5 | Auth failure: 401 kontrollü `ChannelTransportResult` üretir | BW1-05 |
| 6 | Property mapping: HotelCode → doğru ilan resolve edilir | BW1-06 |
| 7 | Unknown HotelCode: null döner, exception atmaz | BW1-07 |
| 8 | Tenant isolation: cross-tenant mapping engellenir | BW1-08 |
| 9 | Retry classification: timeout → retryable=true | BW1-09 |
| 10 | Container: bindings / contracts doğru resolve edilir | BW1-10 |
| 11 | No Basic Auth fallback: legacy credential akışı yok | Code review |
| 12 | No reservation mutation: transport reservation yazmaz | Code review |

---

## Exit Criteria

Sprint closes when:
- [ ] All 10 gate tests pass (10/10)
- [ ] `BookingAuthTransport` — token acquisition + lifecycle implement
- [ ] `BookingPropertyResolver` — HotelCode mapping + tenant isolation
- [ ] `BookingTransport` — authenticated request foundation
- [ ] `ChannelReservationContract` — interface defined
- [ ] No new SAB violations introduced
- [ ] No credentials in git / fixtures / evidence
- [ ] Sprint close document generated
