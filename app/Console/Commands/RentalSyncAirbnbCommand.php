<?php

namespace App\Console\Commands;

use App\Models\IlanTakvimSync;
use App\Services\CalendarSyncService;
use Illuminate\Console\Command;

/**
 * rental:sync-airbnb — Airbnb iCal calendar sync scheduler command.
 *
 * Scheduled every 15 minutes via Kernel::schedule().
 * Reads IlanTakvimSync records flagged for Airbnb auto-sync that are due
 * (next_sync_at null or past), then delegates to CalendarSyncService.
 *
 * SAFETY:
 * - withoutOverlapping() prevents parallel runs (handled by scheduler)
 * - --dry-run: reports records that would sync without executing
 * - --force: bypasses the next_sync_at window check
 *
 * OUTPUT:
 * Per-record JSON lines + summary at end.
 */
class RentalSyncAirbnbCommand extends Command
{
    protected $signature = 'rental:sync-airbnb
        {--dry-run : Sadece rapor ver, islemy yapma}
        {--force : next_sync_at kontrolünü atla, tüm aktifleri zorla}
        {--ilan=* : Belirli ilan ID(leri) ile sinirla}';

    protected $description = 'Airbnb iCal feed senkronizasyonunu calistirir — active auto-sync records';

    public function handle(CalendarSyncService $service): int
    {
        $this->info('📅 Airbnb iCal Sync — ' . now()->toIso8601String());

        $query = IlanTakvimSync::query()
            ->where('platform', 'airbnb')
            ->where('senkron_durumu', 'active') // context7-ignore
            ->where('auto_sync', true);

        if ($this->option('force')) {
            $this->warn('  [FORCE] next_sync_at kontrolü atlanıyor.');
        } else {
            $query->where(function ($q): void {
                $q->whereNull('next_sync_at')
                    ->orWhere('next_sync_at', '<=', now());
            });
        }

        $ilanIds = $this->option('ilan');
        if ($ilanIds) {
            $query->whereIn('ilan_id', $ilanIds);
            $this->warn('  [FILTER] ilan_id: ' . implode(', ', $ilanIds));
        }

        $records = $query->with('ilan')->get();

        $this->line("  Toplam: {$records->count()} kayıt");

        if ($records->isEmpty()) {
            $this->info('  ✅ Synclenecek kayıt yok.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('  [DRY-RUN] Islem yapilmayacak:');
            foreach ($records as $sync) {
                $this->line(sprintf(
                    '  - ilan_id=%d | ext=%s | last=%s | next=%s',
                    $sync->ilan_id,
                    $sync->external_listing_id ?? '-',
                    $sync->last_sync_at?->toDateString() ?? 'hiç yok',
                    $sync->next_sync_at?->toDateString() ?? 'ayar yok'
                ));
            }
            return self::SUCCESS;
        }

        $success = 0;
        $failed = 0;

        foreach ($records as $sync) {
            $ilanLabel = $sync->ilan?->baslik ?? "ilan_id={$sync->ilan_id}";
            $this->line("  → {$ilanLabel}");

            $result = $service->syncCalendar($sync->ilan_id, 'airbnb');

            if ($result['success']) {
                $this->info("    ✅ {$result['message']} ({$result['dates']} tarih)");
                $success++;
            } else {
                $this->error("    ❌ {$result['message']}");
                $failed++;
            }
        }

        $this->line('');
        $this->line("  ── Özet ──");
        $this->line("  Başarılı: {$success}");
        $this->line("  Başarısız: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
