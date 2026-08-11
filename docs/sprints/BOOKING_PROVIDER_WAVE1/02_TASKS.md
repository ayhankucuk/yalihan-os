# 02_TASKS.md — Sprint 4.10

## Task Execution Order

```
BookingAuthTransport
        ↓
BookingPropertyResolver
        ↓
BookingTransport
        ↓
ChannelReservationContract
        ↓
IlanTakvimSync Migration
        ↓
BW1-01..BW1-10 tests
        ↓
Verification + Sprint Close
```

---

## Task 1: BookingAuthTransport
**Priority:** P0
**Type:** Feature
**Files:** `app/Infrastructure/ChannelManager/Booking/BookingAuthTransport.php`

**Implementation:**

Two-legged machine-account token auth — no user interaction.

```
Client ID + Client Secret → POST /oauth/tokens → Access Token (~1h)
```

- Token cache: `token_access`, `token_refresh`, `token_expires_at` fields on `IlanTakvimSync`
- Before every API call: check if token expired (compare `token_expires_at` to now)
- If expired: re-exchange via Client ID + Client Secret (refresh, not re-auth)
- 401 response from API → trigger token refresh → retry once
- Secret masking: never log `client_secret`, `token_access` — log only masked refs
- Return: `BookingAuthResult` DTO with token + expiry

**Interface for testability (interface extracted for testability):**

```php
interface BookingAuthClientInterface
{
    public function exchangeToken(string $clientId, string $clientSecret): BookingAuthResult;
    public function refreshToken(string $refreshToken, string $clientId, string $clientSecret): BookingAuthResult;
}
```

**No Basic Auth:** No `Authorization: Basic ...` anywhere in the codebase.

---

## Task 2: BookingPropertyResolver
**Priority:** P0
**Type:** Feature
**Files:** `app/Services/ChannelManager/BookingPropertyResolver.php`

**Implementation:**

```
Booking HotelCode (BasicPropertyInfo.HotelCode)
        ↓
IlanTakvimSync::where('external_listing_id', $hotelCode)
              ->where('platform', 'booking')
              ->where('is_sync_active', true)
        ↓
Ilan (canonical)
        ↓
Tenant context validation (tenant isolation)
        ↓
BookingPropertyRef { ilanId, tenantId, externalHotelCode }
```

- Uses `DB::table` to avoid Eloquent global scope issues
- Tenant isolation: verifies `ilan.tenant_id` matches the calling tenant context
- Returns `null` for unknown HotelCode — never throws exception to transport layer
- Logs warning for unknown HotelCode (not error — expected during onboarding)

---

## Task 3: BookingTransport
**Priority:** P0
**Type:** Feature
**Files:** `app/Infrastructure/ChannelManager/Booking/BookingTransport.php`

**Implementation:**

Thin HTTP client wrapping `BookingAuthTransport`.

- Base URL from config (`services.booking.api_url`)
- Every request: inject `Authorization: Bearer {token}`
- Timeout: configurable (default 30s)
- Retryable classification:
  - 429 (rate limit) → `retryable: true`
  - 5xx server error → `retryable: true`
  - 401 Unauthorized → trigger token refresh → retry once
  - 400 Bad Request → `retryable: false`
  - Network timeout → `retryable: true`
- Response normalization: Booking API JSON → `ChannelTransportResult`-compatible DTO
- Telemetry: log correlationId, duration, HTTP status

---

## Task 4: ChannelReservationContract
**Priority:** P1
**Type:** Interface
**Files:** `app/Contracts/ChannelManager/ChannelReservationContract.php`

**Implementation:**

Canonical reservation lifecycle interface — provider-agnostic.

```php
interface ChannelReservationContract
{
    // Retrieval (Wave 2)
    public function retrieveNew(int $tenantId, int $propertyId, string $from, string $to): ReservationFetchResult;
    public function retrieveModified(int $tenantId, int $propertyId, string $from, string $to): ReservationFetchResult;
    public function retrieveCancelled(int $tenantId, int $propertyId, string $from, string $to): ReservationFetchResult;

    // Acknowledgement (Wave 2)
    public function acknowledge(int $tenantId, string $reservationId, string $status): AckResult;

    // Health
    public function testConnection(int $tenantId): ChannelSyncResponse;
}
```

**Wave 1 note:** Methods are stub/throw `NOT_IMPLEMENTED` — retrieval logic NOT forced into this contract yet. Booking.com has its own OTA-specific retrieval semantics (different endpoints for new/modified/cancelled). Contract is defined; implementation comes Wave 2.

---

## Task 5: IlanTakvimSync Token Migration
**Priority:** P0
**Type:** Database Migration
**Files:** `database/migrations/2026_08_11_*_add_booking_token_fields_to_ilan_takvim_sync.php`

**Implementation:**

Add three new columns to `ilan_takvim_sync`:

```php
$table->string('token_access')->nullable();
$table->string('token_refresh')->nullable();
$table->timestamp('token_expires_at')->nullable();
```

`fillable` array in `IlanTakvimSync` model updated.

---

## Task 6: BookingAuthTransportTest (BW1-01..BW1-10)
**Priority:** P0
**Type:** Test
**Files:** `tests/Feature/ChannelManager/Booking/BookingWave1AuthTest.php`

**Implementation — 10 gate tests:**

| ID | Test | Method |
|----|------|--------|
| BW1-01 | Valid credentials → token acquired | Mock HTTP 200 |
| BW1-02 | Secret not logged | Assert log does not contain client_secret |
| BW1-03 | Valid token reused without re-exchange | BW1-03 |
| BW1-04 | Expired token triggers refresh | Mock expired token |
| BW1-05 | 401 → controlled failure result | Mock 401 |
| BW1-06 | HotelCode → correct ilan resolved | DB setup |
| BW1-07 | Unknown HotelCode → null, no exception | DB setup |
| BW1-08 | Cross-tenant mapping blocked | Two-tenant DB setup |
| BW1-09 | Timeout → retryable=true result | Mock timeout |
| BW1-10 | Container bindings resolve correctly | `app()` + contract |

---

## Verification Plan

| Task | Verification | Expected |
|------|-------------|---------|
| BookingAuthTransport | `php artisan test --filter=BW1_01` | PASS |
| BookingPropertyResolver | `php artisan test --filter=BW1_06` | PASS |
| BookingTransport | `php artisan test --filter=BW1_09` | PASS |
| ChannelReservationContract | `php artisan test --filter=BW1_10` | PASS |
| All 10 tests | `php artisan test --filter=BookingWave1` | **10/10 PASS** |
| No sab violations | `php artisan sab:integrity-scan` | 0 new violations |
| No Basic Auth | `grep -r "Basic.*auth\|Authorization.*Basic" app/` | 0 results |
