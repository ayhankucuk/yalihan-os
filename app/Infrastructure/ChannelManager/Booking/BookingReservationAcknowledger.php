<?php

namespace App\Infrastructure\ChannelManager\Booking;

use App\DTOs\ChannelManager\ChannelTransportResult;
use Illuminate\Support\Facades\Log;

/**
 * BookingAcknowledgementException — Thrown when ACK to Booking.com fails.
 *
 * Sprint 4.11 — Booking.com Provider Wave 2
 * ADR-009 §2: ACK failure → reservation stays committed (no rollback)
 */
class BookingAcknowledgementException extends \RuntimeException
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly bool $isRetryable,
        string $message = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function isRetryable(): bool
    {
        return $this->isRetryable;
    }
}

/**
 * BookingReservationAcknowledger — Acknowledges reservations to Booking.com.
 *
 * Sprint 4.11 — Booking.com Provider Wave 2
 *
 * NEW reservations → POST OTA_HotelResNotif
 * HTTP 200 = success
 * HTTP 400 = stale/out-of-order message → skip + log
 * HTTP 5xx = retryable
 */
class BookingReservationAcknowledger
{
    private const ACK_ENDPOINT = '/ota/HotelResNotif';

    public function __construct(
        private readonly \App\Infrastructure\ChannelManager\Booking\BookingTransport $transport,
    ) {}

    /**
     * Acknowledge a NEW reservation to Booking.com.
     *
     * @throws BookingAcknowledgementException on failure
     */
    public function acknowledgeNew(int $ilanId, string $externalReservationId): void
    {
        $body = [
            'reservation_id' => $externalReservationId,
            'status'        => 'accepted',
            'message'       => 'Reservation accepted by Yalihan',
        ];

        Log::info('BookingReservationAcknowledger: ACK sent', [
            'ilan_id' => $ilanId,
            'reservation_id' => $externalReservationId,
        ]);

        $result = $this->transport->post($ilanId, self::ACK_ENDPOINT, $body);

        if (!$result->success) {
            $retryable = $result->retryable;

            Log::error('BookingReservationAcknowledger: ACK failed', [
                'ilan_id'      => $ilanId,
                'reservation_id' => $externalReservationId,
                'error_code'  => $result->errorCode,
                'retryable'   => $retryable,
            ]);

            throw new BookingAcknowledgementException(
                httpStatus: (int) ($result->errorCode ?: 0),
                isRetryable: $retryable,
                message: "ACK failed: {$result->errorCode} — {$result->errorMessage}",
            );
        }

        Log::info('BookingReservationAcknowledger: ACK success', [
            'ilan_id' => $ilanId,
            'reservation_id' => $externalReservationId,
        ]);
    }

    /**
     * Acknowledge a MODIFICATION to Booking.com.
     *
     * @throws BookingAcknowledgementException on failure
     */
    public function acknowledgeModification(int $ilanId, string $externalReservationId): void
    {
        $body = [
            'reservation_id' => $externalReservationId,
            'status'         => 'modified',
            'message'        => 'Reservation modification accepted by Yalihan',
        ];

        Log::info('BookingReservationAcknowledger: Modification ACK sent', [
            'ilan_id' => $ilanId,
            'reservation_id' => $externalReservationId,
        ]);

        $result = $this->transport->post($ilanId, self::ACK_ENDPOINT, $body);

        if (!$result->success) {
            throw new BookingAcknowledgementException(
                httpStatus: (int) ($result->errorCode ?: 0),
                isRetryable: $result->retryable,
                message: "Modification ACK failed: {$result->errorCode} — {$result->errorMessage}",
            );
        }

        Log::info('BookingReservationAcknowledger: Modification ACK success', [
            'ilan_id' => $ilanId,
            'reservation_id' => $externalReservationId,
        ]);
    }

    /**
     * Acknowledge a CANCELLATION to Booking.com.
     *
     * @throws BookingAcknowledgementException on failure
     */
    public function acknowledgeCancellation(int $ilanId, string $externalReservationId): void
    {
        $body = [
            'reservation_id' => $externalReservationId,
            'status'         => 'cancelled',
            'message'        => 'Reservation cancellation accepted by Yalihan',
        ];

        Log::info('BookingReservationAcknowledger: Cancellation ACK sent', [
            'ilan_id' => $ilanId,
            'reservation_id' => $externalReservationId,
        ]);

        $result = $this->transport->post($ilanId, self::ACK_ENDPOINT, $body);

        if (!$result->success) {
            throw new BookingAcknowledgementException(
                httpStatus: (int) ($result->errorCode ?: 0),
                isRetryable: $result->retryable,
                message: "Cancellation ACK failed: {$result->errorCode} — {$result->errorMessage}",
            );
        }

        Log::info('BookingReservationAcknowledger: Cancellation ACK success', [
            'ilan_id' => $ilanId,
            'reservation_id' => $externalReservationId,
        ]);
    }
}
