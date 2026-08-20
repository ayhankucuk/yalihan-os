<?php

namespace App\Services\ChannelManager;

use App\DTOs\ChannelManager\ChannexReservationPayload;
use App\Events\ChannelManager\ChannexReservationCancelledViaChanEvent;
use App\Events\ChannelManager\ChannexReservationIngestedEvent;
use App\Events\ChannelManager\ChannexReservationModifiedEvent;
use App\Events\ChannelManager\ChannexReservationRejectedEvent;
use App\Models\PropertyReservation;
use App\Services\ReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
        // 1. Idempotency — resolve ilan_id first
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

        $hasExternalCols = Schema::hasColumn('property_reservations', 'external_reservation_id');

        if ($hasExternalCols) {
            $existingRow = DB::table('property_reservations')
                ->where('external_reservation_id', $payload->externalReservationId)
                ->where('external_channel', $payload->channel)
                ->where('tenant_id', $tenantId)
                ->first();
        } else {
            $existingRow = DB::table('property_reservations')
                ->where('property_id', $ilanId)
                ->where('start_date', $payload->arrivalDate)
                ->where('end_date', $payload->departureDate)
                ->where('tenant_id', $tenantId)
                ->whereNull('cancelled_at')
                ->first();
        }

        if ($existingRow !== null) {
            Log::info('ChannexReservationIngestService: duplicate, returning existing', [
                'external_reservation_id' => $payload->externalReservationId,
            ]);
            return PropertyReservation::withoutGlobalScopes()->findOrFail($existingRow->id);
        }

        // 2. Create reservation + stamp external IDs atomically
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
            if ($hasExternalCols) {
                DB::table('property_reservations')
                    ->where('id', $reservation->id)
                    ->update([
                        'external_reservation_id' => $payload->externalReservationId,
                        'external_channel'        => $payload->channel,
                    ]);
                $reservation->external_reservation_id = $payload->externalReservationId;
                $reservation->external_channel        = $payload->channel;
            } else {
                $stamp = "[Channel: {$payload->channel} | ExternalID: {$payload->externalReservationId}]";
                DB::table('property_reservations')
                    ->where('id', $reservation->id)
                    ->update([
                        'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' {$stamp}')"),
                    ]);
            }

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

    /**
     * Ingest a Channex reservation modification.
     * ADR-008: delegate to ReservationService.modifyReservation() (canonical).
     */
    public function ingestModification(
        string $externalReservationId,
        string $externalChannel,
        int    $tenantId,
        string $newStartDate,
        string $newEndDate,
        array  $guestData = [],
    ): ?PropertyReservation {
        $hasExternalCols = Schema::hasColumn('property_reservations', 'external_reservation_id');

        if ($hasExternalCols) {
            $row = DB::table('property_reservations')
                ->where('external_reservation_id', $externalReservationId)
                ->where('external_channel', $externalChannel)
                ->where('tenant_id', $tenantId)
                ->first();
        } else {
            $row = DB::table('property_reservations')
                ->where('tenant_id', $tenantId)
                ->where('notes', 'LIKE', "%ExternalID: {$externalReservationId}%")
                ->first();
        }

        if ($row === null) {
            Log::warning('ChannexReservationIngestService: ingestModification — unknown external_reservation_id', [
                'external_reservation_id' => $externalReservationId,
                'tenant_id'               => $tenantId,
            ]);
            return null;
        }

        try {
            $reservation = $this->reservationService->modifyReservation(
                $row->id,
                $newStartDate,
                $newEndDate,
                $guestData,
            );

            $stateValue = is_object($reservation->reservation_state)
                ? $reservation->reservation_state->value
                : $reservation->reservation_state;

            if ($stateValue === 'cancelled') {
                Log::info('ChannexReservationIngestService: ingestModification ignored — reservation is cancelled', [
                    'external_reservation_id' => $externalReservationId,
                ]);
                return $reservation;
            }

            event(new ChannexReservationModifiedEvent(
                reservationId:         $reservation->id,
                tenantId:              $tenantId,
                externalReservationId: $externalReservationId,
                externalChannel:       $externalChannel,
                newStartDate:          $newStartDate,
                newEndDate:            $newEndDate,
            ));

            return $reservation;

        } catch (\Exception $e) {
            Log::error('ChannexReservationIngestService: ingestModification failed', [
                'external_reservation_id' => $externalReservationId,
                'error'                   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Ingest a Channex reservation cancellation.
     * ADR-008: delegate to ReservationService.cancelReservation() (canonical, idempotent).
     */
    public function ingestCancellation(
        string $externalReservationId,
        string $externalChannel,
        int    $tenantId,
    ): ?PropertyReservation {
        $hasExternalCols = Schema::hasColumn('property_reservations', 'external_reservation_id');

        if ($hasExternalCols) {
            $row = DB::table('property_reservations')
                ->where('external_reservation_id', $externalReservationId)
                ->where('external_channel', $externalChannel)
                ->where('tenant_id', $tenantId)
                ->first();
        } else {
            $row = DB::table('property_reservations')
                ->where('tenant_id', $tenantId)
                ->where('notes', 'LIKE', "%ExternalID: {$externalReservationId}%")
                ->first();
        }

        if ($row === null) {
            Log::warning('ChannexReservationIngestService: ingestCancellation — unknown external_reservation_id', [
                'external_reservation_id' => $externalReservationId,
                'tenant_id'               => $tenantId,
            ]);
            return null;
        }

        try {
            $this->reservationService->cancelReservation($row->id);

            $reservation = PropertyReservation::withoutGlobalScopes()->findOrFail($row->id);

            event(new ChannexReservationCancelledViaChanEvent(
                reservationId:         $reservation->id,
                tenantId:              $tenantId,
                externalReservationId: $externalReservationId,
                externalChannel:       $externalChannel,
            ));

            return $reservation;

        } catch (\Exception $e) {
            Log::error('ChannexReservationIngestService: ingestCancellation failed', [
                'external_reservation_id' => $externalReservationId,
                'error'                   => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
