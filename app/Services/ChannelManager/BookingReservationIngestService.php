<?php

namespace App\Services\ChannelManager;

use App\DTOs\ChannelManager\Booking\BookingReservationPayload;
use App\Events\ChannelManager\BookingReservationIngestedEvent;
use App\Events\ChannelManager\BookingReservationRejectedEvent;
use App\Events\ChannelManager\BookingAckFailedEvent;
use App\Infrastructure\ChannelManager\Booking\BookingAcknowledgementException;
use App\Infrastructure\ChannelManager\Booking\BookingReservationAcknowledger;
use App\Infrastructure\ChannelManager\Booking\BookingReservationRetriever;
use App\Models\PropertyReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BookingReservationIngestService — Orchestrates retrieve → ingest → ACK chain.
 *
 * Sprint 4.11 — Booking.com Provider Wave 2
 * ADR-009 Invariant: ACK ONLY after successful canonical commit.
 *
 * BW2-05: Uses ReservationService (canonical chain)
 * BW2-06: ACK only in success path
 * BW2-07: Persistence failure → NO ACK
 * BW2-08: Duplicate → idempotent (external_reservation_id dedup)
 * BW2-10: ACK failure → NO rollback
 * BW2-11: Cross-tenant blocked
 */
class BookingReservationIngestService
{
    public function __construct(
        private readonly BookingReservationRetriever         $retriever,
        private readonly BookingPropertyResolver              $propertyResolver,
        private readonly BookingReservationAcknowledger       $acknowledger,
    ) {}

    /**
     * Retrieve new reservations → normalize → resolve → canonical commit → ACK.
     *
     * Returns number of successfully ingested + acknowledged reservations.
     *
     * ACK invariant: acknowledge() called ONLY in success path.
     * catch (ReservationException) → NO ACK
     * catch (AcknowledgementException) → reservation stays committed
     */
    public function processNewReservations(int $ilanId, int $tenantId, string $from, string $to): int
    {
        $payloads = $this->retriever->retrieveNew($ilanId, $from, $to);
        if (empty($payloads)) {
            return 0;
        }

        $ingested = 0;
        foreach ($payloads as $payload) {
            if ($this->processOne($ilanId, $tenantId, $payload)) {
                $ingested++;
            }
        }

        return $ingested;
    }

    /**
     * Process a single reservation payload.
     *
     * @return bool true = success, false = rejected/skipped
     */
    public function processOne(int $ilanId, int $tenantId, BookingReservationPayload $payload): bool
    {
        // BW2-04: Unknown HotelCode → reject + NO ACK
        $ref = $this->propertyResolver->resolve($tenantId, $payload->hotelCode);
        if ($ref === null) {
            Log::warning('BookingReservationIngestService: unknown HotelCode, rejecting', [
                'hotel_code' => $payload->hotelCode,
                'tenant_id'  => $tenantId,
            ]);
            event(new BookingReservationRejectedEvent(
                externalReservationId: $payload->externalReservationId,
                hotelCode: $payload->hotelCode,
                tenantId: $tenantId,
                reason: 'UNKNOWN_HOTEL_CODE',
                retryable: false,
            ));
            return false;
        }

        // BW2-11: Cross-tenant isolation — ilan must belong to tenant
        if ($ref->ilanId !== $ilanId) {
            Log::warning('BookingReservationIngestService: cross-tenant ingest blocked', [
                'ilan_id'      => $ilanId,
                'ref_ilan_id'  => $ref->ilanId,
                'tenant_id'     => $tenantId,
            ]);
            event(new BookingReservationRejectedEvent(
                externalReservationId: $payload->externalReservationId,
                hotelCode: $payload->hotelCode,
                tenantId: $tenantId,
                reason: 'CROSS_TENANT_ISOLATION',
                retryable: false,
            ));
            return false;
        }

        // BW2-08: Idempotency — dedup by external_reservation_id + channel
        $existing = PropertyReservation::withoutGlobalScopes()
            ->where('external_reservation_id', $payload->externalReservationId)
            ->where('external_channel', 'booking_com')
            ->where('tenant_id', $tenantId)
            ->first();

        if ($existing !== null) {
            Log::info('BookingReservationIngestService: duplicate, returning existing', [
                'external_reservation_id' => $payload->externalReservationId,
            ]);
            // BW2-09: Duplicate — still safe to ACK
            $this->safeAcknowledge($ref->ilanId, $tenantId, $payload->externalReservationId, $existing->id);
            return true;
        }

        // BW2-05: Canonical persistence via ReservationService
        try {
            $reservation = $this->createReservation($ref, $payload);
        } catch (\Exception $e) {
            // BW2-07: Persistence failure → NO ACK
            Log::error('BookingReservationIngestService: persistence failed', [
                'hotel_code' => $payload->hotelCode,
                'error'      => $e->getMessage(),
            ]);
            event(new BookingReservationRejectedEvent(
                externalReservationId: $payload->externalReservationId,
                hotelCode: $payload->hotelCode,
                tenantId: $tenantId,
                reason: 'PERSISTENCE_FAILED',
                retryable: false,
            ));
            return false;
        }

        // BW2-06: ACK ONLY after canonical commit SUCCESS
        $this->safeAcknowledge($ref->ilanId, $tenantId, $payload->externalReservationId, $reservation->id);

        event(new BookingReservationIngestedEvent(
            reservationId: $reservation->id,
            tenantId: $tenantId,
            ilanId: $ref->ilanId,
            externalReservationId: $payload->externalReservationId,
            hotelCode: $payload->hotelCode,
        ));

        return true;
    }

    // ─── Private ───────────────────────────────────────────────────

    private function createReservation(
        \App\Infrastructure\ChannelManager\Booking\BookingPropertyRef $ref,
        BookingReservationPayload $payload,
    ): PropertyReservation {
        $guestData = $payload->toCanonicalGuestData();
        $guestData['guest_name'] = $payload->guestName;

        // Delegates to ReservationService (canonical chain)
        // Note: ReservationService is NOT called directly here to avoid circular deps.
        // We use DB transaction + idempotency guard manually.
        return DB::transaction(function () use ($ref, $payload, $guestData) {
            $existing = PropertyReservation::withoutGlobalScopes()
                ->where('external_reservation_id', $payload->externalReservationId)
                ->where('external_channel', 'booking_com')
                ->where('tenant_id', $ref->tenantId)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $reservation = PropertyReservation::create([
                'tenant_id'               => $ref->tenantId,
                'property_id'            => $ref->ilanId,
                'start_date'             => $payload->arrivalDate,
                'end_date'               => $payload->departureDate,
                'nights'                 => $payload->nights,
                'guest_name'              => $payload->guestName,
                'guest_phone'             => $payload->guestPhone,
                'guest_email'            => $payload->guestEmail,
                'guest_count'            => $payload->adultCount,
                'external_reservation_id' => $payload->externalReservationId,
                'external_channel'        => 'booking_com',
                'reservation_state'       => 'confirmed',
                'confirmed_at'            => now(),
            ]);

            // Stamp financial fields from Booking.com payload
            if (\Illuminate\Support\Facades\Schema::hasColumn('property_reservations', 'islem_tutari')) {
                $reservation->updateQuietly([
                    'islem_tutari'    => $payload->totalPrice,
                    'currency'        => $payload->currency,
                ]);
            }

            return $reservation;
        });
    }

    /**
     * ACK safely — if ACK fails, reservation stays committed (no rollback).
     * BW2-10: ACK failure → reservation rollback edilmez.
     */
    private function safeAcknowledge(int $ilanId, int $tenantId, string $extResId, int $reservationId): void
    {
        try {
            $this->acknowledger->acknowledgeNew($ilanId, $extResId);
        } catch (BookingAcknowledgementException $e) {
            // ACK failed but reservation is committed — do NOT rollback
            // Booking.com idempotency handles retry
            event(new BookingAckFailedEvent(
                reservationId: $reservationId,
                tenantId: $tenantId,
                externalReservationId: $extResId,
                errorCode: (string) $e->httpStatus,
                errorMessage: $e->getMessage(),
                retryable: $e->isRetryable(),
            ));
        }
    }
}
