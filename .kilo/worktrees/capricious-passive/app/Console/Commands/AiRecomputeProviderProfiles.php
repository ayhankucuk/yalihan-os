<?php
// context7-ignore: 'category'/'categoryId'/'olusturma_tarihi' bu dosyada AI provider profil hesaplama değişkenleri. DB kolon adı context7-ignore gerektirir.

namespace App\Console\Commands;

use App\Models\AiProviderProfile;
use App\Services\AI\ProviderScoringEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AiRecomputeProviderProfiles extends Command
{
    protected $signature = 'ai:recompute-provider-profiles
                            {--window=7d : Analysis window (7d or 30d)}
                            {--apply : Actually save profiles to DB}
                            {--category= : Optional category ID scope}
                            {--dry-run : Only show results (default)}';

    protected $description = 'Compute and update AI provider performance profiles based on telemetry';

    public function handle(ProviderScoringEngine $scoringEngine)
    {
        $window = $this->option('window');
        $apply = $this->option('apply');
        $dryRun = !$apply;
        $categoryId = $this->option('category');

        $days = (int) filter_var($window, FILTER_SANITIZE_NUMBER_INT) ?: 7;
        $this->info("AI Provider Profiling starting...");
        $this->comment("Window: {$window} ({$days} days) | Mode: " . ($dryRun ? 'DRY-RUN' : 'APPLY'));

        $providers = config('provider-optimization.static_priority', ['openai', 'vertex', 'gemini']);

        $results = [];
        foreach ($providers as $provider) {
            $metrics = $this->fetchMetrics($provider, $days, $categoryId);

            if ($metrics['sample_size'] < config('provider-optimization.sample_size.min_7d')) {
                $this->warn("Provider {$provider}: Insufficient sample size ({$metrics['sample_size']})");
                continue;
            }

            $weights = $scoringEngine->getWeights($categoryId);
            $score = $scoringEngine->calculateScore($metrics, $weights);

            $results[] = [
                'provider' => $provider,
                'metrics' => $metrics,
                'score' => $score,
                'categoryId' => $categoryId
            ];
        }

        if (empty($results)) {
            $this->error("No valid profiles computed.");
            return 1;
        }

        $headers = ['Provider', 'Cat', 'Score', 'Acc Rate', 'Latency', 'Cost', 'Error', 'Sample'];
        $rows = [];
        foreach ($results as $res) {
            $rows[] = [
                $res['provider'],
                $res['categoryId'] ?? 'GLOBAL',
                $res['score'],
                number_format($res['metrics']['accept_rate'] * 100, 1) . '%',
                $res['metrics']['avg_latency_ms'] . 'ms',
                '$' . number_format($res['metrics']['avg_cost_usd'], 4),
                number_format($res['metrics']['error_rate'] * 100, 1) . '%',
                $res['metrics']['sample_size']
            ];

            if ($apply) {
                AiProviderProfile::updateOrCreate(
                    [
                        'provider' => $res['provider'],
                        'window' => $window,
                        'kategori_id' => $res['categoryId']
                    ],
                    [
                        'accept_rate' => $res['metrics']['accept_rate'],
                        'avg_latency_ms' => $res['metrics']['avg_latency_ms'],
                        'avg_cost_usd' => $res['metrics']['avg_cost_usd'],
                        'error_rate' => $res['metrics']['error_rate'],
                        'cache_hit_rate' => $res['metrics']['cache_hit_rate'],
                        'sample_size' => $res['metrics']['sample_size'],
                        'computed_score' => $res['score'],
                        'computed_at' => now(),
                    ]
                );
            }
        }

        $this->table($headers, $rows);

        if ($apply) {
            $this->info("Profiles successfully updated in DB.");
        } else {
            $this->info("Dry-run complete. Use --apply to save.");
        }

        return 0;
    }

    private function fetchMetrics(string $provider, int $days, ?int $categoryId): array
    {
        $since = now()->subDays($days);

        // Feature Usages Data
        $usagesQuery = DB::table('ai_feature_usages')
            ->where('provider', $provider)
            ->where('created_at', '>=', $since);

        if ($categoryId) {
            $usagesQuery->where('kategori_id', $categoryId);
        }

        $totalUsages = (clone $usagesQuery)->count();
        $applied = (clone $usagesQuery)->where('aksiyon', 'user_applied')->count();
        $dismissed = (clone $usagesQuery)->where('aksiyon', 'dismissed')->count();

        $acceptRate = ($applied + $dismissed) > 0 ? ($applied / ($applied + $dismissed)) : 0;
        $avgLatency = (clone $usagesQuery)->avg('latency_ms') ?? 0;
        $avgCost = (clone $usagesQuery)->avg('maliyet_usd') ?? 0;
        $cacheHits = (clone $usagesQuery)->where('cache_hit', 1)->count();
        $cacheHitRate = $totalUsages > 0 ? ($cacheHits / $totalUsages) : 0;

        // Logs Data (for technical errors)
        $logsQuery = DB::table('ai_logs')
            ->where('provider', $provider)
            ->where('olusturma_tarihi', '>=', $since);

        $totalLogs = (clone $logsQuery)->count();
        $errors = (clone $logsQuery)->where('calisma_durumu', 'error')->count();
        $errorRate = $totalLogs > 0 ? ($errors / $totalLogs) : 0;

        return [
            'accept_rate' => (float)$acceptRate,
            'avg_latency_ms' => (int)$avgLatency,
            'avg_cost_usd' => (float)$avgCost,
            'error_rate' => (float)$errorRate,
            'cache_hit_rate' => (float)$cacheHitRate,
            'sample_size' => $totalUsages
        ];
    }
}
