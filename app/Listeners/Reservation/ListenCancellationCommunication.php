<?php

namespace App\Listeners\Reservation;

use App\Events\Reservation\ReservationCancelledEvent;
use App\Jobs\Reservation\SendCancellationNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * ListenCancellationCommunication — A2: Cancellation Communication Wave listener.
 *
 * Receives canonical ReservationCancelledEvent and dispatches the
 * queue-safe job for guest cancellation notification.
 *
 * Design rule: Listeners must NEVER contain business logic.
 * They only translate events into job dispatches.
 *
 * Pipeline:
 *   ReservationCancelledEvent (canonical lifecycle event)
 *     → ListenCancellationCommunication (this listener)
 *       → SendCancellationNotificationJob
 *         → GuestCommunicationPolicy
 *           → GuestCancellationNotification
 *             → NotificationDispatcher
 *               → WhatsApp / Email
 *
 * A2 — Cancellation Communication Wave
 * SAAB Decision: Cancellation Communication Pipeline
 */
class ListenCancellationCommunication implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ReservationCancelledEvent $event): void
    {
        SendCancellationNotificationJob::dispatch($event);
    }
}
