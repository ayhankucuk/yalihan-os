<?php
// context7-ignore: 'category' bu dosyada AI optimizasyon kategori string sabiti. Domain model DB alanı değil.

namespace App\Console\Commands;

use App\Models\AiOptimizationRun;
use App\Models\AiThresholdOverride;
use App\Models\IlanKategori;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AiOptimizeThresholdsCommand extends Command
{
    protected $signature = 'ai:optimize-thresholds
        {--apply : Persist overrides}
        {--dry-run : Do not persist changes}
        {--window=7d : Analysis window, e.g. 7d}
        {--category= : Filter by kategori_id}';

    protected $description = 'Optimize AI confidence thresholds using recent usage telemetry';

    public function handle(): int
    {
        $window = (string) $this->option('window');
        $isDryRun = (bool) $this->option('dry-run');
        $isApply = (bool) $this->option('apply') && ! $isDryRun;
        $categoryFilter = $this->option('category');

        $startedAt = now();
        $from = $this->resolveWindowStart($window);

        $query = DB::table('ai_feature_usages')
            ->select('kategori_id', 'yayin_tipi_id')
            ->whereNotNull('kategori_id')
            ->where('created_at', '>=', $from);

        if ($categoryFilter) {
            $query->where('kategori_id', (int) $categoryFilter);
        }

        $contexts = $query->groupBy('kategori_id', 'yayin_tipi_id')->get();

        $diffs = [];

        foreach ($contexts as $ctx) {
            $kategoriId = (int) $ctx->kategori_id;
            $yayinTipiId = $ctx->yayin_tipi_id ? (int) $ctx->yayin_tipi_id : null;

            $total = (int) DB::table('ai_feature_usages')
                ->where('kategori_id', $kategoriId)
                ->when($yayinTipiId, fn ($q) => $q->where('yayin_tipi_id', $yayinTipiId))
                ->where('created_at', '>=', $from)
                ->count();

            if ($total < 50) {
                continue;
            }

            $dismissed = (int) DB::table('ai_feature_usages')
                ->where('kategori_id', $kategoriId)
                ->when($yayinTipiId, fn ($q) => $q->where('yayin_tipi_id', $yayinTipiId))
                ->where('created_at', '>=', $from)
                ->where('aksiyon', 'dismissed')
                ->count();

            $accepted = (int) DB::table('ai_feature_usages')
                ->where('kategori_id', $kategoriId)
                ->when($yayinTipiId, fn ($q) => $q->where('yayin_tipi_id', $yayinTipiId))
                ->where('created_at', '>=', $from)
                ->where('aksiyon', 'user_applied')
                ->count();

            $fpRate = $total > 0 ? $dismissed / $total : 0.0;
            $acceptRate = $total > 0 ? $accepted / $total : 0.0;

            [$baseAuto, $baseSuggest] = $this->resolveBaseThresholds($kategoriId);
            $newAuto = $baseAuto;
            $newSuggest = $baseSuggest;

            if ($fpRate > 0.30) {
                $newAuto += 0.03;
            } elseif ($fpRate < 0.10 && $acceptRate > 0.75) {
                $newAuto -= 0.02;
            }

            if ($acceptRate < 0.45) {
                $newSuggest += 0.02;
            }

            if ($this->isYazlik($kategoriId)) {
                $newAuto = max($newAuto, 0.90);
            }

            $newAuto = max($newAuto, $newSuggest + 0.20);
            $newAuto = min(0.99, max(0.50, $newAuto));
            $newSuggest = min(0.95, max(0.20, $newSuggest));

            $newAuto = round($newAuto, 2);
            $newSuggest = round($newSuggest, 2);

            if ($newAuto === round($baseAuto, 2) && $newSuggest === round($baseSuggest, 2)) {
                continue;
            }

            $diffs[] = [
                'kategori_id' => $kategoriId,
                'yayin_tipi_id' => $yayinTipiId,
                'auto_apply_threshold' => $newAuto,
                'suggest_threshold' => $newSuggest,
                'source' => 'continuous_optimization',
                'calculated_at' => now(),
                'metrics' => [
                    'total' => $total,
                    'dismissed' => $dismissed,
                    'accepted' => $accepted,
                ],
            ];
        }

        if (empty($diffs)) {
            $this->info('No optimization needed');
            return self::SUCCESS;
        }

        if ($isDryRun || ! $isApply) {
            $this->info('Dry-run complete');
            return self::SUCCESS;
        }

        $run = AiOptimizationRun::create([
            'window' => $window,
            'changed_count' => count($diffs),
            'diff_json' => $diffs,
            'executed_by' => 'cli',
            'started_at' => $startedAt,
            'ended_at' => now(),
        ]);

        foreach ($diffs as $diff) {
            AiThresholdOverride::create([
                'kategori_id' => $diff['kategori_id'],
                'yayin_tipi_id' => $diff['yayin_tipi_id'],
                'auto_apply_threshold' => $diff['auto_apply_threshold'],
                'suggest_threshold' => $diff['suggest_threshold'],
                'source' => $diff['source'],
                'run_id' => $run->id,
                'calculated_at' => $diff['calculated_at'],
            ]);
        }

        $this->info('Threshold optimization applied');
        return self::SUCCESS;
    }

    private function resolveWindowStart(string $window): Carbon
    {
        if (preg_match('/^(\d+)d$/', $window, $m)) {
            return now()->subDays((int) $m[1]);
        }

        return now()->subDays(7);
    }

    private function resolveBaseThresholds(int $kategoriId): array
    {
        $auto = (float) config('ai-governance.global.auto_apply_min_confidence', 0.80);
        $suggest = (float) config('ai-governance.global.suggest_min_confidence', 0.50);

        if ($this->isYazlik($kategoriId)) {
            $auto = (float) config('ai-governance.category_overrides.yazlik.auto_apply_min_confidence', $auto);
            $suggest = (float) config('ai-governance.category_overrides.yazlik.suggest_min_confidence', $suggest);
        }

        return [$auto, $suggest];
    }

    private function isYazlik(int $kategoriId): bool
    {
        $slug = IlanKategori::where('id', $kategoriId)->value('slug');

        return $slug === 'yazlik';
    }
}
