<?php

namespace App\Console\Commands;

use App\Models\IlanTakvimSync;
use App\Models\SaaS\Tenant;
use App\Services\CalendarSyncService;
use App\Services\SaaS\TenantContextService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * rental:sync-airbnb — Airbnb iCal calendar sync scheduler command.
 *
 * Scheduled every 15 minutes via Kernel::schedule().
 * Reads IlanTakvimSync records flagged for Airbnb auto-sync that are due
 * (next_sync_at null or past), then delegates to CalendarSyncService.
 *
 * TENANT ISOLATION:
 * Each sync record is processed under its authoritative tenant context,
 * resolved via: IlanTakvimSync → ilan_id → Ilan → tenant_id → Tenant.
 * Tenant context is established before accessing Ilan and cleaned up
 * in finally block — even on exception.
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

    public function __construct(
        private readonly TenantContextService $tenantContextService
    ) {
        parent::__construct();
    }

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
            $this->syncWithTenantContext($sync, $service, $success, $failed);
        }

        $this->line('');
        $this->line("  ── Özet ──");
        $this->line("  Başarılı: {$success}");
        $this->line("  Başarısız: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Process a single sync record under its authoritative tenant context.
     *
     * Tenant authority: IlanTakvimSync → ilan_id → Ilan → tenant_id → Tenant
     *
     * Lifecycle: save original → resolve tenant → set context → sync → restore/clear
     */
    private function syncWithTenantContext(
        IlanTakvimSync $sync,
        CalendarSyncService $service,
        int &$success,
        int &$failed
    ): void {
        // Capture original context (null if clean, tenant ID if already set)
        $originalTenantId = $this->tenantContextService->hasTenant()
            ? $this->tenantContextService->getTenant()->id
            : null;

        try {
            // Resolve authoritative tenant from IlanTakvimSync → Ilan → tenant_id
            $tenant = $this->resolveTenantForSync($sync);

            if (!$tenant) {
                $this->error("    ❌ Tenant bulunamadı (ilan_id={$sync->ilan_id})");
                $failed++;
                return;
            }

            // Establish tenant context for this sync
            $this->tenantContextService->setTenant($tenant);

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

        } catch (\Throwable $e) {
            Log::error('RentalSyncAirbnb: Tenant context sync failed', [
                'ilan_id' => $sync->ilan_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error("    ❌ {$e->getMessage()}");
            $failed++;

        } finally {
            // Restore or clear tenant context to prevent cross-tenant bleeding
            if ($originalTenantId !== null) {
                $previousTenant = Tenant::find($originalTenantId);
                if ($previousTenant) {
                    $this->tenantContextService->setTenant($previousTenant);
                } else {
                    $this->tenantContextService->clearTenant();
                }
            } else {
                // CLI started with clean context — leave it clean
                $this->tenantContextService->clearTenant();
            }
        }
    }

    /**
     * Resolve the authoritative tenant for a given IlanTakvimSync record.
     *
     * Authority chain: IlanTakvimSync → ilan_id → Ilan → tenant_id → Tenant
     *
     * @return Tenant|null Null if Ilan not found (fail-closed: no fallback)
     */
    private function resolveTenantForSync(IlanTakvimSync $sync): ?Tenant
    {
        // Use withoutGlobalScopes to bypass TenantScope — we're resolving
        // the tenant, not querying tenant-scoped data. The join pattern
        // matches ChannexBookingAcknowledger::resolveApiKey() which is
        // the canonical precedent for this lookup.
        $ilan = \App\Models\Ilan::withoutGlobalScopes()
            ->join('tenants', 'ilanlar.tenant_id', '=', 'tenants.id')
            ->where('ilanlar.id', $sync->ilan_id)
            ->select('tenants.*')
            ->orderBy('ilanlar.id')
            ->first();

        return $ilan ? Tenant::find($ilan->id) : null;
    }
}
