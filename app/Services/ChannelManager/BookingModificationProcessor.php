<?php

namespace App\Services\ChannelManager;

use App\DTOs\ChannelManager\Booking\BookingModificationPayload;
use App\Events\ChannelManager\BookingAckFailedEvent;
use App\Events\ChannelManager\BookingReservationModifiedEvent;
use App\Infrastructure\ChannelManager\Booking\BookingAcknowledgementException;
use App\Infrastructure\ChannelManager\Booking\BookingReservationAcknowledger;
use App\Models\PropertyReservation;
use App\Services\ReservationService;
use Illuminate\Support\Facades\Log;

/**
 * BookingModificationProcessor — Applies modification from Booking.com to canonical reservation.
 *
 * Sprint 4.12 — Booking.com Provider Wave 3
 * ADR-009 invariant: modify → canonical ReservationService → availability re-block
 *
 * BW3-03: Delegates to ReservationService.modifyReservation() (canonical chain)
 * BW3-07: Modification on cancelled reservation → silently ignored (ADR-008 terminal state)
 * BW3-10: ACK sent after successful modification commit
 */
class BookingModificationProcessor
{
    public function __construct(
        private readonly BookingPropertyResolver              $propertyResolver,
        private readonly BookingReservationAcknowledger      $acknowledger,
        private readonly ReservationService                  $reservationService,
    ) {}

    /**
     * Process a modification payload from Booking.com.
     *
     * @return PropertyReservation|null null if reservation unknown or terminal state
     */
    public function process(int $ilanId, int $tenantId, BookingModificationPayload $payload): ?PropertyReservation
    {
        // Resolve HotelCode → property reference
        $ref = $this->propertyResolver->resolve($tenantId, $payload->hotelCode);
        if ($ref === null) {
            Log::warning('BookingModificationProcessor: unknown HotelCode', [
                'hotel_code' => $payload->hotelCode,
                'tenant_id'  => $tenantId,
            ]);
            return null;
        }

        // Tenant isolation
        if ($ref->ilanId !== $ilanId) {
            Log::warning('BookingModificationProcessor: cross-tenant blocked', [
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
            Log::info('BookingModificationProcessor: unknown reservation, ignoring', [
                'external_reservation_id' => $payload->externalReservationId,
            ]);
            return null;
        }

        // ADR-008: terminal state → silently ignore modification
        if ($reservation->reservation_state->value === 'cancelled') {
            Log::info('BookingModificationProcessor: modification on cancelled reservation ignored', [
                'reservation_id' => $reservation->id,
            ]);
            return $reservation;
        }

        // Store old dates for event
        $oldArrival = $reservation->start_date;
        $oldDeparture = $reservation->end_date;

        // Apply modification via canonical ReservationService
        try {
            $updated = $this->applyModification($ref, $payload);
        } catch (\Exception $e) {
            Log::error('BookingModificationProcessor: modification failed', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        // BW3-10: ACK after successful modification
        $this->safeAcknowledge($ref->ilanId, $payload->externalReservationId, $updated->id);

        event(new BookingReservationModifiedEvent(
            reservationId: $updated->id,
            tenantId: $tenantId,
            ilanId: $ref->ilanId,
            externalReservationId: $payload->externalReservationId,
            hotelCode: $payload->hotelCode,
            oldArrival: $oldArrival,
            oldDeparture: $oldDeparture,
            newArrival: $payload->arrivalDate,
            newDeparture: $payload->departureDate,
        ));

        return $updated;
    }

    private function applyModification(
        \App\Infrastructure\ChannelManager\Booking\BookingPropertyRef $ref,
        BookingModificationPayload $payload,
    ): PropertyReservation {
        return $this->reservationService->modifyReservation(
            $this->findReservationId($ref->tenantId, $payload->externalReservationId),
            $payload->arrivalDate,
            $payload->departureDate,
            $payload->toCanonicalGuestData(),
        );
    }
    private function findReservationId(int $tenantId, string $externalReservationId): int
    {
        return PropertyReservation::withoutGlobalScopes()
            ->where('external_reservation_id', $externalReservationId)
            ->where('external_channel', 'booking_com')
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->firstOrFail()
            ->id;
    }

    private function safeAcknowledge(int $ilanId, string $extResId, int $reservationId): void
    {
        try {
            $this->acknowledger->acknowledgeModification($ilanId, $extResId);
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
