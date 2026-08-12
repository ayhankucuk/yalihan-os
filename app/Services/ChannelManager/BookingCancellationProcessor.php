<?php

namespace App\Services\ChannelManager;

use App\DTOs\ChannelManager\Booking\BookingModificationPayload;
use App\Events\ChannelManager\BookingAckFailedEvent;
use App\Events\ChannelManager\BookingReservationCancelledEvent;
use App\Infrastructure\ChannelManager\Booking\BookingAcknowledgementException;
use App\Infrastructure\ChannelManager\Booking\BookingReservationAcknowledger;
use App\Models\PropertyReservation;
use App\Services\ReservationService;
use Illuminate\Support\Facades\Log;

/**
 * BookingCancellationProcessor — Applies cancellation from Booking.com to canonical reservation.
 *
 * Sprint 4.12 — Booking.com Provider Wave 3
 * ADR-009 invariant: cancel → canonical ReservationService → availability unblock
 *
 * BW3-04: Delegates to ReservationService.cancelReservation() (canonical chain)
 * BW3-06: Cancellation on unknown reservation → no-op (no exception)
 * BW3-08: Cancellation is idempotent (already cancelled → safe)
 * BW3-11: ACK sent after successful cancellation
 */
class BookingCancellationProcessor
{
    public function __construct(
        private readonly BookingPropertyResolver          $propertyResolver,
        private readonly BookingReservationAcknowledger  $acknowledger,
        private readonly ReservationService              $reservationService,
    ) {}

    /**
     * Process a cancellation payload from Booking.com.
     *
     * @return PropertyReservation|null null if reservation unknown
     */
    public function process(int $ilanId, int $tenantId, BookingModificationPayload $payload): ?PropertyReservation
    {
        // Resolve HotelCode → property reference
        $ref = $this->propertyResolver->resolve($tenantId, $payload->hotelCode);
        if ($ref === null) {
            Log::warning('BookingCancellationProcessor: unknown HotelCode', [
                'hotel_code' => $payload->hotelCode,
                'tenant_id' => $tenantId,
            ]);
            return null;
        }

        // Tenant isolation
        if ($ref->ilanId !== $ilanId) {
            Log::warning('BookingCancellationProcessor: cross-tenant blocked', [
                'ilan_id' => $ilanId,
                'ref_ilan_id' => $ref->ilanId,
                'tenant_id' => $tenantId,
            ]);
            return null;
        }

        // Find reservation by external ID
        $reservation = PropertyReservation::withoutGlobalScopes()
            ->where('external_reservation_id', $payload->externalReservationId)
            ->where('external_channel', 'booking_com')
            ->where('tenant_id', $tenantId)
            ->first();

        if ($reservation === null) {
            Log::info('BookingCancellationProcessor: unknown reservation, no-op', [
                'external_reservation_id' => $payload->externalReservationId,
            ]);
            return null;
        }

        // BW3-08: Cancellation is idempotent — already cancelled is safe
        if ($reservation->reservation_state->value === 'cancelled') {
            Log::info('BookingCancellationProcessor: already cancelled, returning existing', [
                'reservation_id' => $reservation->id,
            ]);
            return $reservation;
        }

        // Apply cancellation via canonical ReservationService
        try {
            $this->applyCancellation($reservation->id);
        } catch (\Exception $e) {
            Log::error('BookingCancellationProcessor: cancellation failed', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        // BW3-11: ACK after successful cancellation
        $this->safeAcknowledge($ref->ilanId, $payload->externalReservationId, $reservation->id);

        event(new BookingReservationCancelledEvent(
            reservationId: $reservation->id,
            tenantId: $tenantId,
            ilanId: $ref->ilanId,
            externalReservationId: $payload->externalReservationId,
            hotelCode: $payload->hotelCode,
        ));

        return $reservation->fresh();
    }

    private function applyCancellation(int $reservationId): void
    {
        $this->reservationService->cancelReservation($reservationId);
    }

    private function safeAcknowledge(int $ilanId, string $extResId, int $reservationId): void
    {
        try {
            $this->acknowledger->acknowledgeCancellation($ilanId, $extResId);
        } catch (BookingAcknowledgementException $e) {
            event(new BookingAckFailedEvent(
                reservationId: $reservationId,
                tenantId: 0,
                externalReservationId: $extResId,
                errorCode: (string) $e->httpStatus,
                errorMessage: $e->getMessage(),
                retryable: $e->isRetryable(),
            ));
        }
    }
}
