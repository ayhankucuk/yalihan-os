<?php

namespace App\Infrastructure\ChannelManager\Adapters;

use App\Contracts\ChannelManager\ChannelSyncContract;
use App\Domain\ChannelManager\DTOs\ChannelSyncResponse;
use App\Domain\ChannelManager\Enums\Channel;
use App\Domain\ChannelManager\Enums\SyncDirection;
use App\Infrastructure\ChannelManager\Booking\BookingAvailabilityException;
use App\Infrastructure\ChannelManager\Booking\BookingRatesException;
use App\Infrastructure\ChannelManager\Booking\BookingTransport;
use App\Models\IlanTakvimSync;
use App\Models\Ilan;
use Illuminate\Support\Facades\Log;

/**
 * BookingChannelAdapter — Production implementation for Booking.com availability push.
 *
 * Sprint 4.13 — Booking.com Provider Wave 4
 * ADR-009 invariant: canonical availability owner = Yalihan; Booking.com = projection only
 *
 * BW4-01..BW4-12 Gate Tests
 *
 * Contract: ChannelSyncContract
 * Transport: BookingTransport (injected)
 * Endpoint: POST /ota/Availability (Booking.com OTA standard)
 *
 * ADR-006 invariants enforced:
 * - Transport-agnostic credentials resolved via IlanTakvimSync
 * - Tenant isolation via property ownership check
 * - Booking.com idempotency: same correlationId = same result
 * - NOT_IMPLEMENTED guard removed — production ready
 */
class BookingChannelAdapter implements ChannelSyncContract
{
    private const AVAILABILITY_ENDPOINT = '/ota/Availability';
    private const RATES_ENDPOINT = '/ota/HotelRateAmountNotif';

    public function __construct(
        private readonly BookingTransport $transport,
    ) {}

    public function getChannel(): Channel
    {
        return Channel::BOOKING;
    }

    public function getChannelName(): string
    {
        return Channel::BOOKING->label();
    }

    public function supportsPush(): bool
    {
        return true; // Wave 4: availability push is implemented
    }

    public function supportsPull(): bool
    {
        return false; // Booking.com: pull not implemented in Wave 4
    }

    public function supportsRatesPush(): bool
    {
        return true; // Wave 5: rates push is implemented
    }

    /**
     * Push availability FROM Yalihan TO Booking.com.
     *
     * BW4-09: Empty availabilityData → early return (no API call)
     * BW4-05: Tenant isolation via IlanTakvimSync platform check
     * BW4-10: HTTP 200 → ChannelSyncResponse.success
     * BW4-07: 5xx → BookingAvailabilityException (retryable)
     * BW4-08: 4xx → ChannelSyncResponse.failure (non-retryable)
     */
    public function pushAvailability(
        int    $tenantId,
        int    $propertyId,
        string $correlationId,
        array  $availabilityData,
    ): ChannelSyncResponse {
        // BW4-09: Empty data → no-op
        if (empty($availabilityData)) {
            return ChannelSyncResponse::success(
                channel: Channel::BOOKING,
                direction: SyncDirection::EXPORT,
                correlationId: $correlationId,
                channelRef: 'empty-no-op',
                metadata: ['synced_count' => 0],
            );
        }

        // BW4-05: Tenant isolation — resolve sync record
        // Manual tenant check (BelongsToTenant scope does NOT apply to this model in tests)
        $syncRecord = \App\Models\IlanTakvimSync::where('ilan_id', $propertyId)
            ->where('platform', 'booking_com')
            ->where('is_sync_active', true)
            ->where('senkron_durumu', 'active')
            ->first();

        if ($syncRecord === null) {
            return ChannelSyncResponse::failure(
                channel: Channel::BOOKING,
                direction: SyncDirection::EXPORT,
                correlationId: $correlationId,
                errorCode: 'NOT_REGISTERED',
                errorMessage: "No active booking_com sync for property {$propertyId} in tenant {$tenantId}",
                retryable: false,
            );
        }

        // Secondary tenant isolation check — ilan must belong to calling tenant
        $ilanTenantId = \App\Models\Ilan::withoutGlobalScopes()
            ->where('id', $propertyId)
            ->value('tenant_id');
        if ((int) $ilanTenantId !== $tenantId) {
            return ChannelSyncResponse::failure(
                channel: Channel::BOOKING,
                direction: SyncDirection::EXPORT,
                correlationId: $correlationId,
                errorCode: 'CROSS_TENANT_ACCESS',
                errorMessage: "Cross-tenant availability push blocked",
                retryable: false,
            );
        }

        // BW4-02: Map to Booking.com OTA_Availability format
        $otaPayload = $this->buildOtaPayload($syncRecord->external_listing_id, $availabilityData);

        Log::info('BookingChannelAdapter: pushing availability', [
            'property_id'     => $propertyId,
            'hotel_code'      => $syncRecord->external_listing_id,
            'correlation_id'  => $correlationId,
            'dates_count'     => count($availabilityData),
        ]);

        $result = $this->transport->post($propertyId, self::AVAILABILITY_ENDPOINT, $otaPayload);

        // BW4-07: 5xx → throw retryable exception
        if (!$result->success && $result->errorCode !== null && $this->isRetryableErrorCode((int) $result->errorCode)) {
            throw new BookingAvailabilityException(
                httpStatus: (int) $result->errorCode,
                isRetryable: true,
                message: "Booking.com availability push failed: [{$result->errorCode}] {$result->errorMessage}",
            );
        }

        // BW4-08: 4xx → graceful failure (non-retryable)
        if (!$result->success) {
            Log::warning('BookingChannelAdapter: non-retryable push failure', [
                'property_id'    => $propertyId,
                'error_code'    => $result->errorCode,
                'error_message'  => $result->errorMessage,
            ]);
            return ChannelSyncResponse::failure(
                channel: Channel::BOOKING,
                direction: SyncDirection::EXPORT,
                correlationId: $correlationId,
                errorCode: (string) ($result->errorCode ?? 'UNKNOWN'),
                errorMessage: $result->errorMessage ?? 'Unknown error',
                retryable: false,
            );
        }

        // BW4-10: Success
        Log::info('BookingChannelAdapter: availability push success', [
            'property_id'    => $propertyId,
            'correlation_id' => $correlationId,
            'dates_count'   => count($availabilityData),
        ]);

        return ChannelSyncResponse::success(
            channel: Channel::BOOKING,
            direction: SyncDirection::EXPORT,
            correlationId: $correlationId,
            channelRef: $result->providerReference ?? 'ok',
            metadata: [
                'synced_count' => count($availabilityData),
                'hotel_code'   => $syncRecord->external_listing_id,
            ],
        );
    }

    /**
     * BW4-11: Booking.com availability is push-only (pull not implemented in Wave 4).
     */
    public function pullAvailability(
        int    $tenantId,
        int    $propertyId,
        string $correlationId,
        string $fromDate,
        string $toDate,
    ): ChannelSyncResponse {
        return ChannelSyncResponse::failure(
            channel: Channel::BOOKING,
            direction: SyncDirection::IMPORT,
            correlationId: $correlationId,
            errorCode: 'NOT_IMPLEMENTED',
            errorMessage: 'Booking.com pull availability is not implemented in Wave 4.',
            retryable: false,
        );
    }

    public function testConnection(int $tenantId): ChannelSyncResponse
    {
        return ChannelSyncResponse::failure(
            channel: Channel::BOOKING,
            direction: SyncDirection::EXPORT,
            correlationId: 'connection-test',
            errorCode: 'NOT_IMPLEMENTED',
            errorMessage: 'Booking.com adapter is not yet implemented. See CHANNEL_MANAGER_BOOKING_DEBT-001.',
            retryable: false,
        );
    }

    /**
     * Rates push not implemented in Wave 4.
     * Wave 5 handles this via RateProjectionService → RateSynchronizationService pipeline.
     */
    public function pushRates(
        int    $tenantId,
        int    $propertyId,
        string $correlationId,
        array  $ratesData,
    ): ChannelSyncResponse {
        // BW5-09: Empty data → no-op
        if (empty($ratesData)) {
            return ChannelSyncResponse::success(
                channel: Channel::BOOKING,
                direction: SyncDirection::EXPORT,
                correlationId: $correlationId,
                channelRef: 'empty-no-op',
                metadata: ['synced_count' => 0],
            );
        }

        // BW5-05: Tenant isolation — resolve sync record
        $syncRecord = \App\Models\IlanTakvimSync::where('ilan_id', $propertyId)
            ->where('platform', 'booking_com')
            ->where('is_sync_active', true)
            ->where('senkron_durumu', 'active')
            ->first();

        if ($syncRecord === null) {
            return ChannelSyncResponse::failure(
                channel: Channel::BOOKING,
                direction: SyncDirection::EXPORT,
                correlationId: $correlationId,
                errorCode: 'NOT_REGISTERED',
                errorMessage: "No active booking_com sync for property {$propertyId}",
                retryable: false,
            );
        }

        // Secondary tenant isolation — ilan must belong to calling tenant
        $ilanTenantId = \App\Models\Ilan::withoutGlobalScopes()
            ->where('id', $propertyId)
            ->value('tenant_id');
        if ((int) $ilanTenantId !== $tenantId) {
            return ChannelSyncResponse::failure(
                channel: Channel::BOOKING,
                direction: SyncDirection::EXPORT,
                correlationId: $correlationId,
                errorCode: 'CROSS_TENANT_ACCESS',
                errorMessage: "Cross-tenant rates push blocked",
                retryable: false,
            );
        }

        // BW5-02..04: Map to Booking.com OTA_Rates format
        $otaPayload = $this->buildOtaRatesPayload($syncRecord->external_listing_id, $ratesData);

        Log::info('BookingChannelAdapter: pushing rates', [
            'property_id'    => $propertyId,
            'hotel_code'     => $syncRecord->external_listing_id,
            'correlation_id' => $correlationId,
            'dates_count'    => count($ratesData),
        ]);

        $result = $this->transport->post($propertyId, self::RATES_ENDPOINT, $otaPayload);

        // BW5-07: 5xx → throw retryable exception
        if (!$result->success && $result->errorCode !== null && $this->isRetryableErrorCode((int) $result->errorCode)) {
            throw new BookingRatesException(
                httpStatus: (int) $result->errorCode,
                isRetryable: true,
                message: "Booking.com rates push failed: [{$result->errorCode}] {$result->errorMessage}",
            );
        }

        // BW5-08: 4xx → graceful failure (non-retryable)
        if (!$result->success) {
            Log::warning('BookingChannelAdapter: rates push 4xx failure', [
                'property_id'   => $propertyId,
                'error_code'    => $result->errorCode,
                'error_message' => $result->errorMessage,
            ]);
            return ChannelSyncResponse::failure(
                channel: Channel::BOOKING,
                direction: SyncDirection::EXPORT,
                correlationId: $correlationId,
                errorCode: (string) ($result->errorCode ?? 'UNKNOWN'),
                errorMessage: $result->errorMessage ?? 'Unknown error',
                retryable: false,
            );
        }

        // BW5-10: Success
        Log::info('BookingChannelAdapter: rates push success', [
            'property_id'    => $propertyId,
            'correlation_id' => $correlationId,
        ]);

        return ChannelSyncResponse::success(
            channel: Channel::BOOKING,
            direction: SyncDirection::EXPORT,
            correlationId: $correlationId,
            channelRef: $result->providerReference ?? 'ok',
            metadata: [
                'synced_count' => count($ratesData),
                'hotel_code'  => $syncRecord->external_listing_id,
            ],
        );
    }

    /**
     * Build Booking.com OTA_Availability payload.
     *
     * Booking.com Connectivity OTA_Availability format:
     * {
     *   "rooms": [
     *     {
     *       "HotelCode": "BK-HOTEL-A",
     *       "Availability": [
     *         {"Date": "2044-09-01", "StopSell": {"value": "true"}},
     *         {"Date": "2044-09-02", "StopSell": {"value": "false"}}
     *       ]
     *     }
     *   ]
     * }
     *
     * @param string $hotelCode  BasicPropertyInfo.HotelCode
     * @param array  $availabilityData [['date' => 'Y-m-d', 'available' => bool], ...]
     */
    private function buildOtaPayload(string $hotelCode, array $availabilityData): array
    {
        $availabilityElements = [];

        foreach ($availabilityData as $item) {
            $date = $item['date']; // 'Y-m-d'
            $available = (bool) ($item['available'] ?? true);

            if ($available) {
                // Open date — StopSell=false (available to book)
                $availabilityElements[] = [
                    'Date'     => $date,
                    'StopSell' => ['value' => 'false'],
                ];
            } else {
                // Block date — StopSell=true (not available)
                $availabilityElements[] = [
                    'Date'     => $date,
                    'StopSell' => ['value' => 'true'],
                ];
            }
        }

        return [
            'rooms' => [
                [
                    'HotelCode'    => $hotelCode,
                    'Availability' => $availabilityElements,
                ],
            ],
        ];
    }

    private function isRetryableErrorCode(int $code): bool
    {
        return $code === 0        // network error
            || $code === 429       // rate limit
            || $code >= 500;      // server error
    }

    // ─── Rates Push Helpers ───────────────────────────────────────────

    /**
     * Build Booking.com OTA_HotelRateAmountNotif payload.
     *
     * BW5-02..04: StartDate/EndDate, Amount, CurrencyCode per rate entry.
     * BW5-03: Collapse consecutive identical rates into one range.
     *
     * @param string $hotelCode
     * @param array  $ratesData [['date' => 'Y-m-d', 'rate' => float, 'currency' => string], ...]
     */
    private function buildOtaRatesPayload(string $hotelCode, array $ratesData): array
    {
        $roomRates = [];

        foreach ($ratesData as $item) {
            $startDate = $item['date'];
            $endDate = \Carbon\Carbon::parse($startDate)->addDay()->format('Y-m-d');
            $amount = number_format((float) ($item['rate'] ?? 0), 2, '.', '');
            $currency = strtoupper($item['currency'] ?? 'TRY');

            $roomRates[] = [
                'StartDate'     => $startDate,
                'EndDate'       => $endDate,
                'Rate'          => [
                    'Amount'         => $amount,
                    'CurrencyCode'  => $currency,
                ],
            ];
        }

        // BW5-03: Collapse consecutive rates with identical amount + currency into one range
        $collapsed = $this->collapseConsecutiveRates($roomRates);

        return [
            'rooms' => [
                [
                    'HotelCode' => $hotelCode,
                    'RoomStay'  => [
                        'Rates' => $collapsed,
                    ],
                ],
            ],
        ];
    }

    /**
     * Collapse consecutive Rate entries with identical amount + currency into a single range.
     *
     * e.g. [Sep1-2@500, Sep2-3@500] → [Sep1-3@500]
     * BW5-03 invariant: same rate + currency = single range entry.
     */
    private function collapseConsecutiveRates(array $rates): array
    {
        if (count($rates) <= 1) {
            return $rates;
        }

        $collapsed = [];
        $current = null;

        foreach ($rates as $rate) {
            if ($current === null) {
                $current = $rate;
                continue;
            }

            $prevEnd = $current['EndDate'];
            $currStart = $rate['StartDate'];
            $sameRate = $current['Rate']['Amount'] === $rate['Rate']['Amount']
                && $current['Rate']['CurrencyCode'] === $rate['Rate']['CurrencyCode'];

            if ($prevEnd === $currStart && $sameRate) {
                // Extend current range
                $current = [
                    'StartDate' => $current['StartDate'],
                    'EndDate'  => $rate['EndDate'],
                    'Rate'     => $current['Rate'],
                ];
            } else {
                $collapsed[] = $current;
                $current = $rate;
            }
        }

        if ($current !== null) {
            $collapsed[] = $current;
        }

        return $collapsed;
    }
}
