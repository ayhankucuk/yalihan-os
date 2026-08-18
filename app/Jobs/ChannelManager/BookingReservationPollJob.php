<?php

namespace App\Jobs\ChannelManager;

use App\Services\ChannelManager\BookingReservationIngestService;
use App\Services\ChannelManager\BookingPropertyResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BookingReservationPollJob — Queue-first polling for Booking.com new reservations.
 *
 * Sprint 4.11 — Booking.com Provider Wave 2
 * Queue-first polling (NOT 20s cron sleep)
 */
class BookingReservationPollJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly ?string $fromDate = null,
    ) {}

    public function handle(
        BookingReservationIngestService $ingestService,
        BookingPropertyResolver $resolver,
    ): void {
        $from = $this->fromDate ?? now()->subDays(7)->format('Y-m-d');
        $to   = now()->format('Y-m-d');

        Log::info('BookingReservationPollJob: starting', [
            'from' => $from,
            'to'   => $to,
        ]);

        $syncConfigs = DB::table('ilan_takvim_sync AS sync')
            ->join('ilanlar AS ilan', 'ilan.id', '=', 'sync.ilan_id')
            ->where('sync.platform', 'booking_com')
            ->where('sync.is_sync_active', 1)
            ->whereNotNull('ilan.tenant_id')
            ->get(['ilan.id AS ilan_id', 'ilan.tenant_id']);

        $processed = 0;
        $errors = 0;

        foreach ($syncConfigs as $config) {
            try {
                $count = $ingestService->processNewReservations(
                    (int) $config->ilan_id,
                    (int) $config->tenant_id,
                    $from,
                    $to,
                );
                $processed += $count;
            } catch (\Throwable $e) {
                $errors++;
                Log::error('BookingReservationPollJob: property failed', [
                    'ilan_id'   => $config->ilan_id,
                    'tenant_id' => $config->tenant_id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        Log::info('BookingReservationPollJob: completed', [
            'processed' => $processed,
            'errors'    => $errors,
            'properties_polled' => $syncConfigs->count(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('BookingReservationPollJob: all retries exhausted', [
            'from'  => $this->fromDate,
            'error' => $exception->getMessage(),
        ]);
    }
}
