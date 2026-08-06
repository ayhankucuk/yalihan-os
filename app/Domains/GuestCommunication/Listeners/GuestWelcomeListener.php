<?php

namespace App\Domains\GuestCommunication\Listeners;

use App\Domains\GuestCommunication\Services\GuestCommunicationService;
use App\Events\Reservation\ReservationConfirmedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * GuestWelcomeListener
 *
 * GuestCommunication WAVE 1
 *
 * ReservationConfirmedEvent'i dinler ve GuestCommunicationService'i çağırır.
 * Queue-based for async processing.
 */
class GuestWelcomeListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private readonly GuestCommunicationService $guestCommunicationService
    ) {}

    /**
     * Handle the ReservationConfirmedEvent.
     */
    public function handle(ReservationConfirmedEvent $event): void
    {
        $this->guestCommunicationService->handleReservationConfirmed($event);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('GuestWelcomeListener: Failed after retries', [
            'reservation_id' => $event->reservationId ?? null,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
