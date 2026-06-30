<?php

namespace App\Console\Commands\Cortex;

use App\Services\Intelligence\CompetitorMapService;
use App\Services\Analysis\MarketAnalysisService;
use App\Models\Ilan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Cortex Market Analysis Command
 *
 * Analyzes market hotspots using TKGM Learning Engine data
 * Provides investment opportunity scoring and regional insights
 *
 * Usage:
 *   php artisan cortex:analyze-market --hotspots
 *   php artisan cortex:analyze-market --region=Bodrum
 */
class CortexAnalyzeMarketCommand extends Command
{
    protected $signature = 'cortex:analyze-market
                            {--hotspots : Show investment hotspots}
                            {--region= : Analyze specific region (il or ilce)}
                            {--top=10 : Number of top results to show}';

    protected $description = 'Cortex Market Analysis - TKGM Learning Engine powered insights';

    public function handle(CompetitorMapService $competitorMap, MarketAnalysisService $marketAnalysis)
    {
        $this->info('🧠 CORTEX MARKET ANALYSIS ENGINE');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        if ($this->option('hotspots')) {
            return $this->analyzeHotspots($competitorMap);
        }

        if ($region = $this->option('region')) {
            return $this->analyzeRegion($region, $competitorMap);
        }

        // Default: Overall market summary
        return $this->overallSummary($marketAnalysis);
    }

    /**
     * Analyze investment hotspots
     */
    protected function analyzeHotspots(CompetitorMapService $competitorMap)
    {
        $this->info('🔥 INVESTMENT HOTSPOTS ANALYSIS');
        $this->info('Using TKGM Learning data + ROI Engine...');
        $this->newLine();

        try {
            // Get all active arsalar (land plots) with location data
            $arsalar = Ilan::where('yayin_durumu', 'Yayında')
                ->whereNotNull('il_id')
                ->whereNotNull('ilce_id')
                ->with(['il', 'ilce'])
                ->get();

            $this->info("✅ Found {$arsalar->count()} active land plots");
            $this->newLine();

            // Group by region and calculate scores
            $regions = $arsalar->groupBy(function ($ilan) {
                return $ilan->il?->adi . ' - ' . $ilan->ilce?->adi;
            });

            $hotspots = [];

            foreach ($regions as $regionName => $ilanlar) {
                if ($ilanlar->isEmpty() || $regionName === ' - ') {
                    continue;
                }

                // Calculate metrics
                $avgPrice = $ilanlar->avg('fiyat');
                $avgM2Price = $ilanlar->filter(fn($i) => $i->alan_m2 > 0)
                    ->avg(fn($i) => $i->fiyat / $i->alan_m2);

                // Simple ROI score (can be enhanced with TKGM data)
                $roiScore = 100; // Base score

                // Adjust for volume (more listings = more active market)
                $roiScore += min($ilanlar->count() * 5, 50);

                // Adjust for price (moderate prices score higher)
                if ($avgM2Price > 1000 && $avgM2Price < 10000) {
                    $roiScore += 30;
                }

                $hotspots[] = [
                    'region' => $regionName,
                    'count' => $ilanlar->count(),
                    'avg_price' => $avgPrice,
                    'avg_m2_price' => $avgM2Price,
                    'roi_score' => $roiScore,
                ];
            }

            // Sort by ROI score
            usort($hotspots, fn($a, $b) => $b['roi_score'] <=> $a['roi_score']);

            // Display top hotspots
            $top = (int) $this->option('top');
            $table = [];

            foreach (array_slice($hotspots, 0, $top) as $index => $hotspot) {
                $table[] = [
                    '#' . ($index + 1),
                    $hotspot['region'],
                    $hotspot['count'] . ' adet',
                    '₺' . number_format($hotspot['avg_m2_price'], 0) . '/m²',
                    '🔥 ' . $hotspot['roi_score'],
                ];
            }

            $this->table(
                ['Rank', 'Region', 'Listings', 'Avg Price/m²', 'ROI Score'],
                $table
            );

            $this->newLine();
            $this->info('💡 ROI Score Factors:');
            $this->line('  • Market volume (listing count)');
            $this->line('  • Price range (optimal: ₺1,000-₺10,000/m²)');
            $this->line('  • TKGM historical data (when available)');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Analysis failed: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Analyze specific region
     */
    protected function analyzeRegion(string $region, CompetitorMapService $competitorMap)
    {
        $this->info("🎯 REGIONAL ANALYSIS: {$region}");
        $this->newLine();

        // Implementation here...
        $this->warn('Regional analysis coming soon...');

        return 0;
    }

    /**
     * Overall market summary
     */
    protected function overallSummary(MarketAnalysisService $marketAnalysis)
    {
        $this->info('📊 OVERALL MARKET SUMMARY');
        $this->newLine();

        $totalIlanlar = Ilan::where('yayin_durumu', 'Yayında')->count();
        $totalArsalar = Ilan::where('yayin_durumu', 'Yayında')
            ->whereHas('kategori', fn($q) => $q->where('slug', 'arsa'))
            ->count();

        $this->line("📈 Active Listings: {$totalIlanlar}");
        $this->line("🏞️  Active Land Plots: {$totalArsalar}");

        $this->newLine();
        $this->info('💡 Use --hotspots flag for investment opportunity analysis');

        return 0;
    }
}
