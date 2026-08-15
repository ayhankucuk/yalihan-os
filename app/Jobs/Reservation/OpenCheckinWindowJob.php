<?php

namespace App\Jobs\Reservation;

use App\Enums\ReservationState;
use App\Events\Reservation\CheckinWindowOpenedEvent;
use App\Models\PropertyReservation;
use App\Services\Reservation\GuestArrivalReadinessService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * OpenCheckinWindowJob — Wave 2: Opens check-in windows for eligible reservations.
 *
 * Scheduled: Daily (Kernel schedules this job)
 *
 * Logic:
 * 1. Find all CONFIRMED, non-cancelled reservations where:
 *    - start_date - 24h <= now()
 *    - checkin_window_opened_at is still NULL
 * 2. For each reservation, call GuestArrivalReadinessService::openCheckinWindow()
 * 3. Dispatch CheckinWindowOpenedEvent for each opened window
 *
 * Queue-safe: idempotent, tenant-scoped, retryable.
 * Idempotency: service checks checkin_window_opened_at NULL before writing.
 *
 * CHECKIN_CHECKOUT Wave 2
 */
class OpenCheckinWindowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function handle(GuestArrivalReadinessService $readinessService): void
    {
        Log::info('OpenCheckinWindowJob: starting');

        // Find reservations whose window should open:
        // start_date - 24h <= now() AND checkin_window_opened_at IS NULL
        $windowBoundary = Carbon::now()->startOfDay();

        $reservations = PropertyReservation::query()
            ->where('reservation_state', ReservationState::CONFIRMED)
            ->whereNull('cancelled_at')
            ->whereNull('checkin_window_opened_at')
            ->whereDate('start_date', '<=', $windowBoundary->copy()->addDay()->toDateString())
            ->with('ilan')
            ->get();

        $openedCount = 0;
        $skippedCount = 0;

        foreach ($reservations as $reservation) {
            // Double-check: is it actually 24h before?
            $shouldOpen = $readinessService->shouldOpenWindow($reservation);
            if (!$shouldOpen) {
                $skippedCount++;
                continue;
            }

            try {
                $opened = $readinessService->openCheckinWindow($reservation);
                if ($opened) {
                    $openedCount++;
                    Log::info('OpenCheckinWindowJob: window opened', [
                        'reservation_id' => $reservation->id,
                        'tenant_id' => $reservation->tenant_id,
                        'ilan_id' => $reservation->ilan_id,
                        'start_date' => $reservation->start_date,
                    ]);

                    // Dispatch event for downstream handlers (Wave 3: notifications, AI)
                    CheckinWindowOpenedEvent::dispatch(
                        CheckinWindowOpenedEvent::fromModel($reservation)
                    );
                }
            } catch (\Throwable $e) {
                Log::error('OpenCheckinWindowJob: failed to open window', [
                    'reservation_id' => $reservation->id,
                    'tenant_id' => $reservation->tenant_id,
                    'error' => $e->getMessage(),
                ]);
                // Continue processing other reservations
            }
        }

        Log::info('OpenCheckinWindowJob: completed', [
            'total_checked' => $reservations->count(),
            'opened' => $openedCount,
            'skipped' => $skippedCount,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('OpenCheckinWindowJob: all retries exhausted', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
