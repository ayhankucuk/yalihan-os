<?php

namespace App\Console\Commands;

use App\Events\Reservation\ReservationCompletedEvent;
use App\Models\PropertyReservation;
use App\Models\Ilan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

/**
 * ReservationCompleteCommand — Daily checkout completion processor.
 *
 * Runs as a scheduled command (Laravel scheduler).
 * Scans for reservations where end_date <= today and completed_at IS NULL.
 * For each qualifying reservation:
 *   1. Sets completed_at timestamp
 *   2. Dispatches ReservationCompletedEvent (wired to turnover Gorev creation)
 *
 * Idempotent: processes only reservations where completed_at IS NULL.
 * Tenant-scoped: processes all tenants in the system.
 *
 * SAAB Decision CHECKOUT-D1: Option B (timestamps + events)
 * SAAB Decision CHECKOUT-D4 (Q4): Scheduled command
 * Baseline: 88ccfc8
 */
class ReservationCompleteCommand extends Command
{
    protected $signature = 'reservation:complete
        {--dry-run : Simulate only, no changes made}
        {--tenant= : Process only this tenant ID}';

    protected $description = 'Mark reservations as completed when end_date has passed';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $tenantId = $this->option('tenant');

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be made');
        }

        $today = now()->toDateString();

        $query = PropertyReservation::query()
            ->whereDate('end_date', '<=', $today)
            ->whereNull('completed_at')
            ->whereNotIn('reservation_state', ['cancelled']);

        if ($tenantId) {
            $query->where('tenant_id', (int) $tenantId);
        }

        $reservations = $query->orderBy('id')->get();

        if ($reservations->isEmpty()) {
            $this->info("No pending completions found (checked: end_date <= {$today})");
            return Command::SUCCESS;
        }

        $this->info("Found {$reservations->count()} reservation(s) to complete");

        $completed = 0;
        $skipped = 0;

        foreach ($reservations as $reservation) {
            $tenantInfo = $tenantId ? "tenant={$reservation->tenant_id}" : "tenant={$reservation->tenant_id}";

            if ($dryRun) {
                $this->line("  [DRY] Would complete reservation #{$reservation->id} ({$tenantInfo}) — {$reservation->guest_name}");
                $skipped++;
                continue;
            }

            // Idempotency: skip if already completed (race condition protection)
            $current = PropertyReservation::where('id', $reservation->id)
                ->whereNull('completed_at')
                ->first();

            if (!$current) {
                $this->warn("  Reservation #{$reservation->id} already completed (skipped)");
                $skipped++;
                continue;
            }

            // Tenant isolation: double-check tenant_id matches query scope
            if ($tenantId && $current->tenant_id !== (int) $tenantId) {
                Log::error('ReservationCompleteCommand: tenant mismatch', [
                    'reservation_id' => $current->id,
                    'expected_tenant_id' => (int) $tenantId,
                    'actual_tenant_id' => $current->tenant_id,
                ]);
                $skipped++;
                continue;
            }

            // Mark as completed
            $current->completed_at = now();
            $current->save();

            Log::info('ReservationCompleteCommand: reservation marked completed', [
                'reservation_id' => $current->id,
                'tenant_id' => $current->tenant_id,
                'end_date' => $current->end_date,
                'completed_at' => $current->completed_at->toIso8601String(),
            ]);

            // Dispatch ReservationCompletedEvent → wired listener → turnover Gorev
            $event = ReservationCompletedEvent::fromModel($current);
            event($event);

            Log::info('ReservationCompleteCommand: ReservationCompletedEvent dispatched', [
                'reservation_id' => $current->id,
                'tenant_id' => $current->tenant_id,
            ]);

            $completed++;
        }

        $this->info("Completed: {$completed} | Skipped: {$skipped}");

        if ($dryRun) {
            $this->warn('DRY RUN completed — no actual changes made');
        }

        return Command::SUCCESS;
    }
}
