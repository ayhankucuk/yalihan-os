<?php

namespace App\Infrastructure\ChannelManager\Booking;

use App\DTOs\ChannelManager\Booking\BookingReservationPayload;
use App\DTOs\ChannelManager\ChannelTransportResult;
use Illuminate\Support\Facades\Log;

/**
 * BookingReservationRetriever — Retrieves new reservations from Booking.com OTA_HotelResNotif.
 *
 * Sprint 4.11 — Booking.com Provider Wave 2
 * ADR-009 invariant: retrieve → normalize → canonical commit → ACK
 *
 * Uses existing BookingTransport (Wave 1).
 */
class BookingReservationRetriever
{
    private const ENDPOINT = '/ota/HotelResNotif';

    public function __construct(
        private readonly \App\Infrastructure\ChannelManager\Booking\BookingTransport $transport,
    ) {}

    /**
     * Retrieve new reservations for a property since a given date.
     *
     * @return BookingReservationPayload[]
     */
    public function retrieveNew(int $ilanId, string $fromDate, string $toDate): array
    {
        $batchSize = config('services.booking.reservation_batch_size', 100);

        $params = [
            'hotel_id'  => $ilanId,
            'from_date'  => $fromDate,
            'to_date'    => $toDate,
            'status'     => 'new',
            'limit'      => $batchSize,
        ];

        Log::info('BookingReservationRetriever: retrieving new reservations', [
            'ilan_id'  => $ilanId,
            'from'     => $fromDate,
            'to'       => $toDate,
            'batch'    => $batchSize,
        ]);

        $result = $this->transport->get($ilanId, self::ENDPOINT, $params);

        if (!$result->success) {
            Log::warning('BookingReservationRetriever: API failure', [
                'ilan_id'     => $ilanId,
                'error_code'  => $result->errorCode,
                'error_msg'   => $result->errorMessage,
                'retryable'  => $result->retryable,
            ]);
            return [];
        }

        $raw = $result->metadata['reservations'] ?? [];
        $payloads = [];
        foreach ($raw as $item) {
            try {
                $payloads[] = BookingReservationPayload::fromBookingApiResponse($item);
            } catch (\Throwable $e) {
                Log::error('BookingReservationRetriever: skipping malformed reservation', [
                    'ilan_id' => $ilanId,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        Log::info('BookingReservationRetriever: retrieved', [
            'ilan_id' => $ilanId,
            'count'   => count($payloads),
        ]);

        return $payloads;
    }
}
