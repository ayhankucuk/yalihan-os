<?php

namespace App\Console\Commands;

use App\Enums\IlanDurumu;
use App\Jobs\UpdateListingVisibilityScore;
use App\Models\Ilan;
use App\Models\SaaS\Tenant;
use App\Services\Ranking\ListingRankingService;
use App\Services\SaaS\TenantContextService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ranking:recalculate-all — Recalculate visibility scores for all listings.
 *
 * TENANT ISOLATION:
 * Resolves authoritative tenant set from active Ilan records (no hardcoded
 * eligibility rules). Processes each tenant under its TenantContext so that
 * TenantScope naturally constrains the Ilan query — no global scope bypass
 * for listing processing.
 *
 * CANONICAL SERVICE:
 * Uses App\Services\Ranking\ListingRankingService as the single ranking
 * authority. This service provides calculateScore(), calculateQualityScore(),
 * and calculateSeoScore().
 *
 * PATTERN:
 * RentalSyncAirbnbCommand established the CLI-tenant-context lifecycle:
 * save original → resolve tenant → set context → process → restore/clear.
 */
class RecalculateRankingCommand extends Command
{
    protected $signature = 'ranking:recalculate-all {--chunk=500 : Records per tenant chunk} {--dry : Check only, no persistence} {--sync : Force sync execution}';

    protected $description = 'Recalculate visibility scores for all listings (tenant-bounded, Phase 19)';

    public function __construct(
        private readonly TenantContextService $tenantContextService
    ) {
        parent::__construct();
    }

    public function handle(ListingRankingService $rankingService): int
    {
        $chunkSize = (int) $this->option('chunk');
        $dry = $this->option('dry');
        $sync = $this->option('sync');

        $this->info('🚀 DAP Ranking Engine v2 (Tenant-Bounded)');
        $this->info('--------------------------------');
        $this->info("📦 Chunk: {$chunkSize}");
        $this->info('🛡️  Mode: ' . ($dry ? 'DRY-RUN (No Persistence)' : 'APPLY (Persist)'));
        $this->info('⚡ Exec: ' . ($sync || $dry ? 'Sync' : 'Async Queue'));

        // ── 1. Discover authoritative tenant set from active Ilan records ──────
        // withoutGlobalScopes only for discovery — consistent with
        // RentalSyncAirbnbCommand::resolveTenantForSync().
        $tenantIds = Ilan::withoutGlobalScopes()
            ->whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'yayinda'])
            ->distinct()
            ->pluck('tenant_id')
            ->filter()
            ->values();

        if ($tenantIds->isEmpty()) {
            $this->info('  ✅ Yayında ilan yok, islem yapilmayacak.');
            return self::SUCCESS;
        }

        $this->info('  👥 ' . $tenantIds->count() . ' tenant bulundu.');

        // ── 2. Capture pre-existing context ───────────────────────────────────
        $originalTenantId = $this->tenantContextService->hasTenant()
            ? $this->tenantContextService->getTenant()->id
            : null;

        $totalProcessed = 0;
        $totalChanged = 0;
        $startTime = microtime(true);

        // ── 3. Iterate tenants ────────────────────────────────────────────────
        foreach ($tenantIds as $tenantId) {
            $this->processTenant($tenantId, $originalTenantId, $rankingService, $chunkSize, $dry, $sync, $totalProcessed, $totalChanged);
        }

        // ── 4. Restore pre-existing context ──────────────────────────────────
        $this->restoreContext($originalTenantId);

        $runtime = round(microtime(true) - $startTime, 2) . 's';
        $this->newLine();
        $this->table(['Metric', 'Value'], [
            ['Total Processed', $totalProcessed],
            ['Changed', $totalChanged],
            ['Tenants', $tenantIds->count()],
            ['Runtime', $runtime],
        ]);

        $this->info("\n📄 Report: docs/_reports/RANKING_BACKFILL_REPORT.md");

        return self::SUCCESS;
    }

    /**
     * Process all active listings for a single tenant under its authoritative
     * context.
     *
     * Tenant authority: Ilan → tenant_id → Tenant.
     * Lifecycle: save original → set context → query Ilan normally → process
     *            → restore/clear (in finally).
     */
    private function processTenant(
        int $tenantId,
        ?int $originalTenantId,
        ListingRankingService $rankingService,
        int $chunkSize,
        bool $dry,
        bool $sync,
        int &$totalProcessed,
        int &$totalChanged
    ): void {
        // Resolve tenant using the join pattern from RentalSyncAirbnbCommand —
        // the canonical precedent for cross-scope tenant resolution at CLI entry.
        $tenant = Tenant::withoutGlobalScopes()
            ->where('id', $tenantId)
            ->orderBy('id')
            ->first();

        if (!$tenant) {
            $this->warn("  ⚠️  Tenant #{$tenantId} bulunamadi, atlandi.");
            return;
        }

        $this->info("  ── Tenant: {$tenant->name} (#{$tenantId}) ──");

        try {
            $this->tenantContextService->setTenant($tenant);

            // TenantScope applies naturally because context is set.
            $query = Ilan::query()
                ->whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'yayinda']);

            $tenantTotal = $query->count();
            $this->line("    [DEBUG] Query returned: {$tenantTotal} ilanlar");
            if ($tenantTotal === 0) {
                $this->info("    (0 ilan — atlandi)");
                return;
            }

            $bar = $this->output->createProgressBar($tenantTotal);
            $tenantProcessed = 0;
            $tenantChanged = 0;

            $query->chunkById($chunkSize, function ($ilanlar) use (
                $rankingService, $dry, $sync, $bar,
                &$tenantProcessed, &$tenantChanged, &$totalProcessed, &$totalChanged
            ) {
                foreach ($ilanlar as $ilan) {
                    $this->processListing($ilan, $rankingService, $dry, $sync, $tenantChanged);
                    $tenantProcessed++;
                    $totalProcessed++;
                    $bar->advance();
                }
            });

            $bar->finish();
            $totalChanged += $tenantChanged;
            $this->line("    [DEBUG] {$tenantProcessed} ilan chunked, {$tenantChanged} changed, query count: " . $tenantTotal);
            $this->info("    ✅ {$tenantProcessed} ilan ishlendi, {$tenantChanged} değisti.");

        } catch (\Throwable $e) {
            Log::error('ranking:recalculate-all: Tenant processing failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error("    ❌ {$e->getMessage()}");

        } finally {
            $this->restoreContext($originalTenantId);
        }
    }

    /**
     * Calculate and optionally persist ranking scores for a single listing.
     *
     * Sync path:   Uses Ranking\ListingRankingService for all score components
     *             and persists directly.
     * Async path:  Dispatches UpdateListingVisibilityScore job. The job uses
     *             Ranking\ListingRankingService internally and is already
     *             verified as TenantAwareJobInterface with proper context
     *             restoration via RestoreTenantContext middleware.
     */
    private function processListing(
        Ilan $ilan,
        ListingRankingService $rankingService,
        bool $dry,
        bool $sync,
        int &$tenantChanged
    ): void {
        // Canonical service: all score calculations through Ranking\ListingRankingService
        $currentScore = $ilan->visibility_score;
        $newScore = $rankingService->calculateScore($ilan);
        $qualityScore = $rankingService->calculateQualityScore($ilan);
        $seoScore = $rankingService->calculateSeoScore($ilan);

        if ($currentScore !== $newScore) {
            $tenantChanged++;
        }

        if (!$dry) {
            if ($sync) {
                $ilan->visibility_score = $newScore;
                $ilan->seo_score = $seoScore;
                $ilan->quality_score = $qualityScore;
                $ilan->saveQuietly();
            } else {
                // Async: job re-calculates under its own TenantContext.
                // Job's getTenantId() uses withoutGlobalScopes() to resolve tenant.
                UpdateListingVisibilityScore::dispatch($ilan->id);
            }
        }
    }

    /**
     * Restore the tenant context to its pre-command state.
     */
    private function restoreContext(?int $originalTenantId): void
    {
        if ($originalTenantId !== null) {
            $previousTenant = Tenant::find($originalTenantId);
            if ($previousTenant) {
                $this->tenantContextService->setTenant($previousTenant);
            } else {
                $this->tenantContextService->clearTenant();
            }
        } else {
            $this->tenantContextService->clearTenant();
        }
    }
}
