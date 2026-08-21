<?php

namespace App\Services\ChannelManager;

use App\DTOs\ChannelManager\ChannexReservationPayload;
use App\Infrastructure\ChannelManager\Channex\ChannexAcknowledgementException;
use App\Infrastructure\ChannelManager\Channex\ChannexBookingAcknowledger;
use App\Models\PropertyReservation;
use Illuminate\Support\Facades\Log;

/**
 * ChannexRevisionProcessor — Unified canonical processor for Channex revisions.
 *
 * WAVE 7 Phase B1.1R — Channex Reliability Recovery
 *
 * Architectural Invariants:
 * 1. Webhook and Polling Recovery CONVERGE on this single processor.
 * 2. COMMIT -> ACK: Explicit ACK is sent ONLY after canonical DB commit succeeds.
 * 3. ACK Failure is non-fatal to the committed PropertyReservation (no rollback).
 * 4. Idempotency: Replay/retry of same revision returns existing record without duplication.
 */
class ChannexRevisionProcessor
{
    public function __construct(
        private readonly ChannexReservationIngestService $ingestService,
        private readonly ChannexBookingAcknowledger      $acknowledger,
    ) {}

    /**
     * Process a canonical Channex revision and explicitly acknowledge on success.
     *
     * @param ChannexReservationPayload $payload
     * @param int                       $tenantId
     * @return PropertyReservation|null
     */
    public function process(ChannexReservationPayload $payload, int $tenantId): ?PropertyReservation
    {
        Log::info('ChannexRevisionProcessor: processing revision', [
            'external_reservation_id' => $payload->externalReservationId,
            'revision_id'             => $payload->revisionId,
            'action'                  => $payload->action,
            'tenant_id'               => $tenantId,
        ]);

        $reservation = match ($payload->action) {
            'cancelled' => $this->ingestService->ingestCancellation(
                externalReservationId: $payload->externalReservationId,
                externalChannel:       $payload->channel,
                tenantId:              $tenantId,
            ),
            'modified'  => $this->ingestService->ingestModification(
                externalReservationId: $payload->externalReservationId,
                externalChannel:       $payload->channel,
                tenantId:              $tenantId,
                newStartDate:          $payload->arrivalDate,
                newEndDate:            $payload->departureDate,
                guestData:             [
                    'guest_name'  => $payload->guestName,
                    'guest_count' => $payload->adultCount,
                    'guest_phone' => $payload->guestPhone,
                    'guest_email' => $payload->guestEmail,
                ],
            ),
            default     => $this->ingestService->ingest(
                payload:  $payload,
                tenantId: $tenantId,
            ),
        };

        // If DB commit succeeded, attempt explicit booking ACK
        if ($reservation !== null && !empty($payload->revisionId)) {
            $this->safeAcknowledge(
                tenantId:   $tenantId,
                revisionId: $payload->revisionId,
                resId:      $reservation->id,
            );
        }

        return $reservation;
    }

    /**
     * Attempt explicit ACK to Channex.
     * Invariant: ACK failure must NOT rollback or corrupt the committed reservation.
     */
    public function safeAcknowledge(int $tenantId, string $revisionId, int $resId): bool
    {
        try {
            return $this->acknowledger->acknowledgeRevision($tenantId, $revisionId);
        } catch (ChannexAcknowledgementException $e) {
            Log::error('ChannexRevisionProcessor: ACK failed, reservation stays committed', [
                'tenant_id'      => $tenantId,
                'revision_id'    => $revisionId,
                'reservation_id' => $resId,
                'error'          => $e->getMessage(),
                'retryable'      => $e->isRetryable(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('ChannexRevisionProcessor: unexpected error during ACK', [
                'tenant_id'      => $tenantId,
                'revision_id'    => $revisionId,
                'reservation_id' => $resId,
                'error'          => $e->getMessage(),
            ]);
            return false;
        }
    }
}
