# 02_TASKS.md — Sprint 4.11

## Task Execution Order

```
BookingReservationPayload DTO
        ↓
BookingReservationRetriever
        ↓
BookingReservationAcknowledger
        ↓
BookingReservationIngestService (orchestrator)
        ↓
BookingReservationPollJob
        ↓
Events (Ingested / Rejected / AckFailed)
        ↓
BW2-01..BW2-12 tests
        ↓
Verification + Sprint Close
```

---

## Task 1: BookingReservationPayload DTO
**Priority:** P0
**Type:** DTO
**Files:** `app/DTOs/ChannelManager/Booking/BookingReservationPayload.php`

**Implementation:**

Immutable canonical DTO — Booking.com reservation → Yalıhan format.

```php
readonly final class BookingReservationPayload
{
    public function __construct(
        public readonly string  $externalReservationId,   // Reservation ID from Booking.com
        public readonly string  $hotelCode,              // BasicPropertyInfo.HotelCode
        public readonly string  $arrivalDate,
        public readonly string  $departureDate,
        public readonly int    $nights,
        public readonly string  $guestName,
        public readonly ?string $guestPhone,
        public readonly ?string $guestEmail,
        public readonly int    $adultCount,
        public readonly float  $totalPrice,
        public readonly string $currency,
        public readonly string $roomDescription,
        // Status: 'new' | 'modified' | 'cancelled'
        public readonly string  $status,
    ) {}

    public static function fromBookingApiResponse(array $raw): self;
    public function toCanonicalGuestData(): array;
}
```

---

## Task 2: BookingReservationRetriever
**Priority:** P0
**Type:** Service
**Files:** `app/Infrastructure/ChannelManager/Booking/BookingReservationRetriever.php`

**Implementation:**

Calls `GET /ota/HotelResNotif` via `BookingTransport`.

```php
class BookingReservationRetriever
{
    public function __construct(
        private readonly BookingTransport $transport,
    ) {}

    /**
     * Retrieve new reservations since last sync.
     *
     * @return BookingReservationPayload[]
     */
    public function retrieveNew(int $ilanId, string $fromDate, string $toDate): array;
}
```

**Note:** Uses existing `BookingTransport` (Wave 1) — no new transport needed.

---

## Task 3: BookingReservationAcknowledger
**Priority:** P0
**Type:** Service
**Files:** `app/Infrastructure/ChannelManager/Booking/BookingReservationAcknowledger.php`

**Implementation:**

POST to `OTA_HotelResNotif` endpoint. Separate from retriever.

```php
class BookingAcknowledgementException extends \RuntimeException
{
    public function isRetryable(): bool { /* HTTP 400 → false */ }
}

class BookingReservationAcknowledger
{
    public function __construct(
        private readonly BookingTransport $transport,
    ) {}

    /**
     * ACK a new reservation to Booking.com.
     *
     * @throws BookingAcknowledgementException
     */
    public function acknowledgeNew(
        int $ilanId,
        string $externalReservationId,
    ): void;

    /**
     * ACK response:
     * - HTTP 200 = success
     * - HTTP 400 = stale/out-of-order → log + skip
     * - HTTP 5xx = retryable
     */
}
```

---

## Task 4: BookingReservationIngestService
**Priority:** P0
**Type:** Service / Orchestrator
**Files:** `app/Services/ChannelManager/BookingReservationIngestService.php`

**Implementation:**

ADR-009 invariant orchestrator.

```
retrieve() → normalize() → resolve() → ingest() → ACK (success only)
```

```php
class BookingReservationIngestService
{
    public function __construct(
        private readonly BookingReservationRetriever  $retriever,
        private readonly BookingPropertyResolver        $propertyResolver,
        private readonly ReservationService            $reservationService,
        private readonly BookingReservationAcknowledger $acknowledger,
    ) {}

    /**
     * Orchestrate full retrieve → ingest → ACK chain.
     *
     * ACK invariant: acknowledge() called ONLY after reservation is committed.
     *
     * @throws PersistenceException — rollback'dan sonra ACK YOK
     */
    public function processNewReservations(int $ilanId, int $tenantId): int;
}
```

---

## Task 5: BookingReservationPollJob
**Priority:** P0
**Type:** Job
**Files:** `app/Jobs/ChannelManager/BookingReservationPollJob.php`

**Implementation:**

Queue-first polling. NOT a cron job. Dispatched by scheduler or manually.

```php
class BookingReservationPollJob implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 60;

    public function handle(BookingReservationIngestService $service): void
    {
        // Iterate all active booking sync configs
        // For each: $service->processNewReservations($ilanId, $tenantId)
    }
}
```

---

## Task 6: Idempotency + Events
**Priority:** P0
**Type:** Service + Events
**Files:** `app/Services/ChannelManager/BookingReservationIngestService.php`

Idempotency uses `external_reservation_id` + `external_channel` — already in schema (Wave 2 migration).

Events:
```php
class BookingReservationIngestedEvent { /* tenantId, reservationId, hotelCode */ }
class BookingReservationRejectedEvent { /* hotelCode, reason */ }
class BookingAckFailedEvent { /* reservationId, reason */ }
```

---

## Task 7: BW2-01..BW2-12 Tests
**Priority:** P0
**Type:** Test
**Files:** `tests/Feature/ChannelManager/Booking/BookingWave2ReservationTest.php`

See 00_CHARTER.md for full test matrix.
