# 03_DECISIONS.md — Sprint 4.10

## Decisions Made

| # | Decision | Rationale |
|---|----------|-----------|
| D1 | `BookingAuthTransport` token exchange on every request (pre-check) | Simpler than event-driven refresh; token is short-lived (~1h) |
| D2 | Token stored in `IlanTakvimSync` (encrypted column) | Per-property token per platform — already the right granularity |
| D3 | `DB::table` in `BookingPropertyResolver` | Avoids Eloquent global scope issues (learned from Channex Wave 2) |
| D4 | `null` return for unknown HotelCode | Transport layer expects failure DTO, not exception — consistent with Channex |
| D5 | 401 triggers single retry with refreshed token | No infinite retry loop; prevents token refresh storm |
| D6 | No Basic Auth anywhere | ADR-009 enforcement — credential sunset 2025-12-31 |
| D7 | `ChannelReservationContract` methods are stub in Wave 1 | Contract must exist for container binding; implementation in Wave 2 |
| D8 | `BookingAuthResult` DTO for token exchange response | Immutable, testable, no model coupling |
