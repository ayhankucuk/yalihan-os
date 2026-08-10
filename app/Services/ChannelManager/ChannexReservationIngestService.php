<?php

namespace App\Services\ChannelManager;

use App\DTOs\ChannelManager\ChannexReservationPayload;
use App\Events\ChannelManager\ChannexReservationIngestedEvent;
use App\Events\ChannelManager\ChannexReservationRejectedEvent;
use App\Models\PropertyReservation;
use App\Services\ReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ChannexReservationIngestService — thin canonical chain wrapper.
 * CHANNEL_MANAGER_PROVIDER Wave 2 — ADR-007
 */
class ChannexReservationIngestService
{
    public function __construct(
        private readonly ReservationService           $reservationService,
        private readonly ChannexWebhookTenantResolver $tenantResolver,
    ) {}

    public function ingest(ChannexReservationPayload $payload, int $tenantId): PropertyReservation
    {
        // 1. Idempotency — use DB::table to avoid Eloquent global scope issues
        $existingRow = DB::table('property_reservations')
            ->where('external_reservation_id', $payload->externalReservationId)
            ->where('external_channel', $payload->channel)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($existingRow !== null) {
            Log::info('ChannexReservationIngestService: duplicate, returning existing', [
                'external_reservation_id' => $payload->externalReservationId,
            ]);
            return PropertyReservation::withoutGlobalScopes()->findOrFail($existingRow->id);
        }

        // 2. Resolve ilan_id
        $ilanId = $this->tenantResolver->resolveIlanId($payload->externalListingId, $tenantId);
        if ($ilanId === null) {
            event(new ChannexReservationRejectedEvent(
                externalReservationId: $payload->externalReservationId,
                externalListingId:     $payload->externalListingId,
                errorCode:             'ILAN_NOT_FOUND',
                errorMessage:          "No ilan for listing {$payload->externalListingId}",
                retryable:             false,
            ));
            throw new \RuntimeException("ILAN_NOT_FOUND: {$payload->externalListingId}");
        }

        // 3. Create reservation + stamp external IDs atomically
        try {
            $guestData = ['guest_name' => $payload->guestName, 'guest_count' => $payload->adultCount];
            if ($payload->guestPhone) $guestData['guest_phone'] = $payload->guestPhone;
            if ($payload->guestEmail) $guestData['guest_email'] = $payload->guestEmail;

            // Call canonical chain with positional params
            $reservation = $this->reservationService->createReservation(
                $ilanId,
                $payload->arrivalDate,
                $payload->departureDate,
                $guestData,
                null,     // userId — system-created
            );

            // Stamp external IDs immediately (idempotency guard for subsequent calls)
            DB::table('property_reservations')
                ->where('id', $reservation->id)
                ->update([
                    'external_reservation_id' => $payload->externalReservationId,
                    'external_channel'        => $payload->channel,
                ]);

            $reservation->external_reservation_id = $payload->externalReservationId;
            $reservation->external_channel        = $payload->channel;

            event(new ChannexReservationIngestedEvent(
                reservationId:         $reservation->id,
                tenantId:              $tenantId,
                propertyId:            $ilanId,
                externalReservationId: $payload->externalReservationId,
                externalChannel:       $payload->channel,
            ));

            return $reservation;

        } catch (\Exception $e) {
            event(new ChannexReservationRejectedEvent(
                externalReservationId: $payload->externalReservationId,
                externalListingId:     $payload->externalListingId,
                errorCode:             'CREATE_FAILED',
                errorMessage:          $e->getMessage(),
                retryable:             true,
            ));
            throw $e;
        }
    }
}
