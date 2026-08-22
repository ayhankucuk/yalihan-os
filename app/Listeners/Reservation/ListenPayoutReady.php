<?php

namespace App\Listeners\Reservation;

use App\Events\Reservation\ReservationPayoutReadyEvent;
use App\Models\Notification\OutboundNotification;
use Illuminate\Support\Facades\Log;

/**
 * ListenPayoutReady — C3.3: Payout Readiness
 *
 * Fires AFTER ProcessFinancialCompletionJob has:
 *   1. Transitioned finansal_durum → CONFIRMED
 *   2. Created commission split + owner payable accrual ledger entries
 *   3. Dispatched ReservationPayoutReadyEvent
 *
 * This listener records the payout-ready state as a DB notification
 * for admin/operator visibility. No automatic payment is triggered.
 *
 * Design: Thin listener boundary — only records evidence, no business logic.
 *
 * SAAB principle: "AI assists, humans approve strategic decisions"
 *
 * Baseline: 76a467e (C3.2 Certified)
 */
class ListenPayoutReady
{
    public function handle(ReservationPayoutReadyEvent $event): void
    {
        Log::info('ListenPayoutReady: reservation is payout-ready', [
            'reservation_id'    => $event->reservationId,
            'tenant_id'         => $event->tenantId,
            'ilan_id'           => $event->ilanId,
            'gross_amount'      => $event->grossAmount,
            'currency'          => $event->currency,
            'commission_amount'  => $event->commissionAmount,
            'owner_entitlement' => $event->ownerEntitlement,
            'owner_name'        => $event->ownerName,
        ]);

        // Record payout-ready evidence for admin dashboard visibility
        // Uses OutboundNotification as an audit/notification record.
        // The channel is 'internal' to distinguish from customer-facing notifications.
        $this->recordPayoutReadyNotification($event);
    }

    /**
     * Record payout-ready notification evidence.
     * This surfaces the readiness in the admin notification center.
     */
    private function recordPayoutReadyNotification(ReservationPayoutReadyEvent $event): void
    {
        try {
            OutboundNotification::create([
                'channel' => 'internal',
                'recipient' => 'admin',
                'template_key' => 'finance.payout_ready',
                'payload_data' => [
                    'reservation_id' => $event->reservationId,
                    'tenant_id' => $event->tenantId,
                    'ilan_id' => $event->ilanId,
                    'ilan_baslik' => $event->ilanBaslik,
                    'guest_name' => $event->guestName,
                    'start_date' => $event->startDate,
                    'end_date' => $event->endDate,
                    'gross_amount' => $event->grossAmount,
                    'currency' => $event->currency,
                    'commission_amount' => $event->commissionAmount,
                    'owner_entitlement' => $event->ownerEntitlement,
                    'owner_name' => $event->ownerName,
                    'management_model' => $event->managementModelSnapshot,
                    'commission_rate' => $event->commissionRateSnapshot,
                    'completed_at' => $event->completedAt,
                    'external_channel' => $event->externalChannel,
                    'external_reservation_id' => $event->externalReservationId,
                ],
                'gonderim_durumu' => OutboundNotification::STATE_SENT,
                'deneme_sayisi' => 1,
                'gonderim_tarihi' => now(),
                'aktiflik_durumu' => 1,
                'display_order' => 10,
            ]);
        } catch (\Throwable $e) {
            // Non-fatal: notification recording failure must not break the pipeline.
            // Payout readiness is still recorded in the reservation + ledger.
            Log::warning('ListenPayoutReady: failed to record notification', [
                'reservation_id' => $event->reservationId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
