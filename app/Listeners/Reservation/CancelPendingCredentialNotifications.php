<?php

namespace App\Listeners\Reservation;

use App\Events\Reservation\ReservationCancelledEvent;
use App\Events\Reservation\ReservationModifiedEvent;
use App\Models\Notification\OutboundNotification;
use Illuminate\Support\Facades\Log;

/**
 * CancelPendingCredentialNotifications — Race safety for Wave 3 credential delivery.
 *
 * CHECKIN_CHECKOUT Wave 3
 *
 * Triggered by:
 *   - ReservationCancelledEvent
 *   - ReservationModifiedEvent (date changes)
 *
 * Failsafe behavior:
 *   When a reservation is cancelled or its dates are modified, any pending/processing
 *   credential notifications for that reservation must be cancelled to prevent sending
 *   credentials to a guest who should no longer receive them.
 *
 * Idempotency:
 *   Uses OutboundNotification state machine: only PENDING, PROCESSING, and
 *   RETRY_SCHEDULED states are cancelled. Already SENT notifications are NOT
 *   reverted (delivery is immutable).
 *
 * Note:
 *   This listener runs IN ADDITION to existing listeners (ListenReservationCancelled,
 *   ListenReadinessOnCancellation, etc.). It only handles credential notifications
 *   (template_key = 'checkin_credential').
 */
class CancelPendingCredentialNotifications
{
    /**
     * Handle reservation cancellation.
     */
    public function handleCancellation(ReservationCancelledEvent $event): void
    {
        $this->cancelPendingCredentialNotifications(
            $event->reservationId,
            $event->tenantId,
            'cancelled',
        );
    }

    /**
     * Handle reservation date modification.
     *
     * If dates change, the previous check-in credential is no longer valid.
     * New credential delivery will be triggered when the new check-in window opens.
     */
    public function handleModification(ReservationModifiedEvent $event): void
    {
        $this->cancelPendingCredentialNotifications(
            $event->reservationId,
            $event->tenantId,
            'date_modified',
        );
    }

    /**
     * Cancel all pending/processing checkin_credential notifications.
     *
     * Safety:
     *   - Only cancels notifications in transitional states (PENDING, PROCESSING, RETRY_SCHEDULED)
     *   - Does NOT touch SENT notifications (immutable)
     *   - Does NOT touch STATE_CANCELLED (already cancelled)
     *   - STATE_FAILED is also left as-is (already terminal)
     *
     * @param int    $reservationId
     * @param int    $tenantId
     * @param string $reason         'cancelled' | 'date_modified'
     */
    public function cancelPendingCredentialNotifications(
        int    $reservationId,
        int    $tenantId,
        string $reason,
    ): void {
        // Note: OutboundNotification does NOT have tenant_id column.
        // We use template_key + reservation_id for identification.
        // tenant_id is passed for logging purposes only.
        $affected = OutboundNotification::withoutCountryScope()
            ->where('template_key', 'checkin_credential')
            ->whereJsonContains('payload_data', ['reservation_id' => $reservationId])
            ->whereIn('gonderim_durumu', [
                OutboundNotification::STATE_PENDING,
                OutboundNotification::STATE_PROCESSING,
                OutboundNotification::STATE_RETRY_SCHEDULED,
            ])
            ->update([
                'gonderim_durumu' => OutboundNotification::STATE_CANCELLED,
                'hata_mesaji'     => "Cancelled by system: reservation {$reason}",
            ]);

        if ($affected > 0) {
            Log::info('CancelPendingCredentialNotifications: pending notifications cancelled', [
                'reservation_id' => $reservationId,
                'tenant_id'     => $tenantId,
                'affected_count' => $affected,
                'reason'        => $reason,
            ]);
        }
    }
}
