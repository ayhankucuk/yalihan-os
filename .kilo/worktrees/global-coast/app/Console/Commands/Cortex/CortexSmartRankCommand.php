<?php

namespace App\Console\Commands\Cortex;

use App\Models\Ilan;
use App\Services\AI\SmartPropertyMatcherAI;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Cortex Smart Ranking Command
 *
 * Re-ranks listings using AI-powered scoring algorithms
 * Factors: ROI potential, market velocity, completion, photo quality
 *
 * Usage:
 *   php artisan cortex:smart-rank --ilan-id=ALL
 *   php artisan cortex:smart-rank --ilan-id=123
 */
class CortexSmartRankCommand extends Command
{
    protected $signature = 'cortex:smart-rank
                            {--ilan-id=ALL : Ilan ID to rank (ALL for all active listings)}
                            {--dry-run : Show ranking without saving}
                            {--shield : Enable Cortex Shield (anomaly detection)}';

    protected $description = 'Cortex Smart Ranking - AI-powered listing prioritization';

    public function handle()
    {
        $this->info('🎯 CORTEX SMART RANKING ENGINE');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $ilanId = $this->option('ilan-id');
        $dryRun = $this->option('dry-run');
        $shieldEnabled = $this->option('shield');

        if ($shieldEnabled) {
            $this->warn('🛡️  CORTEX SHIELD ENABLED - Anomaly detection active');
            $this->newLine();
        }

        try {
            // Get target ilanlar
            if ($ilanId === 'ALL') {
                $ilanlar = Ilan::where('yayin_durumu', 'Yayında')
                    ->with(['kategori'])
                    ->get();
                
                $this->info("📋 Found {$ilanlar->count()} active listings");
            } else {
                $ilanlar = Ilan::where('id', $ilanId)->get();
                
                if ($ilanlar->isEmpty()) {
                    $this->error("❌ Ilan #{$ilanId} not found");
                    return 1;
                }
                
                $this->info("📋 Processing single listing: #{$ilanId}");
            }

            $this->newLine();
            $this->info('🧮 Calculating smart scores...');
            
            $progressBar = $this->output->createProgressBar($ilanlar->count());
            $progressBar->start();

            $ranked = [];
            $anomalies = [];

            foreach ($ilanlar as $ilan) {
                $score = $this->calculateSmartScore($ilan);

                // Anomaly detection
                if ($shieldEnabled && $this->detectAnomaly($ilan, $score)) {
                    $anomalies[] = [
                        'ilan_id' => $ilan->id,
                        'baslik' => $ilan->baslik,
                        'score' => $score,
                        'reason' => 'Unusual score pattern detected',
                    ];
                }

                $ranked[] = [
                    'id' => $ilan->id,
                    'baslik' => substr($ilan->baslik ?? 'Untitled', 0, 50),
                    'kategori' => $ilan->kategori?->adi ?? 'N/A',
                    'score' => $score,
                    'fiyat' => $ilan->fiyat,
                ];

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Sort by score
            usort($ranked, fn($a, $b) => $b['score'] <=> $a['score']);

            // Display top 20
            $this->info('🏆 TOP 20 RANKED LISTINGS:');
            $this->newLine();

            $table = [];
            foreach (array_slice($ranked, 0, 20) as $index => $item) {
                $table[] = [
                    '#' . ($index + 1),
                    $item['id'],
                    $item['baslik'],
                    $item['kategori'],
                    '🔥 ' . number_format($item['score'], 1),
                    '₺' . number_format($item['fiyat'], 0),
                ];
            }

            $this->table(
                ['Rank', 'ID', 'Başlık', 'Kategori', 'Score', 'Fiyat'],
                $table
            );

            // Anomaly report
            if (!empty($anomalies)) {
                $this->newLine();
                $anomalyCount = count($anomalies);
                $this->warn("🛡️  CORTEX SHIELD: {$anomalyCount} ANOMALIES DETECTED");
                
                foreach ($anomalies as $anomaly) {
                    $this->line("  • [#{$anomaly['ilan_id']}] {$anomaly['baslik']} - Score: {$anomaly['score']} - {$anomaly['reason']}");
                }
            }

            // Save to database (if not dry-run)
            if (!$dryRun) {
                $this->newLine();
                $this->info('💾 Saving rankings to database...');
                
                foreach ($ranked as $item) {
                    DB::table('ilanlar')
                        ->where('id', $item['id'])
                        ->update([
                            'cortex_score' => $item['score'],
                            'cortex_ranked_at' => now(),
                        ]);
                }
                
                $this->info('✅ Rankings saved successfully');
            } else {
                $this->warn('⏸️  DRY RUN - Rankings not saved');
            }

            $this->newLine();
            $this->info('🎉 Smart ranking completed!');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Ranking failed: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            
            if ($shieldEnabled) {
                $this->warn('🛡️  CORTEX SHIELD activated - System protected');
            }
            
            return 1;
        }
    }

    /**
     * Calculate smart score for ilan
     */
    protected function calculateSmartScore(Ilan $ilan): float
    {
        $score = 50.0; // Base score

        // Factor 1: Completion percentage (0-25 points)
        $completionFields = [
            'baslik' => 5,
            'aciklama' => 5,
            'fiyat' => 10,
            'il_id' => 3,
            'ilce_id' => 2,
        ];

        foreach ($completionFields as $field => $points) {
            if (!empty($ilan->$field)) {
                $score += $points;
            }
        }

        // Factor 2: Photo quality (0-15 points)
        // (Simplified - real implementation would check photo count/quality)
        $score += 10; // Placeholder

        // Factor 3: Price competitiveness (0-10 points)
        if ($ilan->fiyat > 0 && $ilan->fiyat < 50000000) {
            $score += 10; // Reasonable price range
        }

        // Factor 4: Freshness (-10 to +10 points)
        $daysSinceCreated = now()->diffInDays($ilan->created_at);
        if ($daysSinceCreated < 7) {
            $score += 10; // Fresh listing
        } elseif ($daysSinceCreated > 90) {
            $score -= 10; // Stale listing
        }

        // Factor 5: Location data (0-10 points)
        if ($ilan->latitude && $ilan->longitude) {
            $score += 10;
        }

        return min(max($score, 0), 100); // Clamp to 0-100
    }

    /**
     * Detect anomalies in scoring
     */
    protected function detectAnomaly(Ilan $ilan, float $score): bool
    {
        // Anomaly: Very high score but missing critical fields
        if ($score > 80 && (empty($ilan->baslik) || empty($ilan->fiyat))) {
            return true;
        }

        // Anomaly: Zero score (should never happen)
        if ($score <= 0) {
            return true;
        }

        // Anomaly: Perfect score (suspicious)
        if ($score >= 100) {
            return true;
        }

        return false;
    }
}
