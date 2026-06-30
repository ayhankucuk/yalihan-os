<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Ups\UpsFeatureGovernanceService;
use App\Services\Ups\UpsVersioningService;
use App\Services\AI\SmartFieldGenerationService;
use App\Services\Logging\LogService;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Support\YayinTipiRules;

class UpsAiRescueFeatures extends Command
{
    protected $signature = 'ups:ai-rescue-features';
    protected $description = 'AI-powered rescue for unassigned features using YalihanCortex + SmartFieldGenerationService';

    public function handle(): int
    {
        $t0 = LogService::startTimer('ups_ai_rescue_features');
        $gov = app(UpsFeatureGovernanceService::class);
        $versioning = app(UpsVersioningService::class);
        $smart = app(SmartFieldGenerationService::class);

        $stats = $gov->getUsageStats(['status' => true]);
        $orphans = array_values(array_filter($stats, fn($f) => ($f['is_orphaned'] ?? false) === true));

        $slugs = array_map(fn($f) => $f['slug'], $orphans);
        $recs = $smart->generateSmartRecommendations($slugs);

        $assigned = 0;
        $manual = 0;
        foreach ($recs as $rec) {
            $conf = (float) ($rec['confidence'] ?? 0);
            if ($conf < 0.7) {
                $manual++;
                LogService::info('ups_ai_manual_review', [
                    'feature_slug' => $rec['slug'],
                    'confidence' => $conf,
                ]);
                continue;
            }
            if (!$rec['category_slug'] || !$rec['yayin_tipi_slug']) {
                $manual++;
                LogService::info('ups_ai_manual_review', [
                    'feature_slug' => $rec['slug'],
                    'reason' => 'missing_target',
                ]);
                continue;
            }
            if ($conf < 0.9) {
                $manual++;
                LogService::info('ups_ai_manual_review', [
                    'feature_slug' => $rec['slug'],
                    'confidence' => $conf,
                    'reason' => 'low_confidence',
                ]);
                continue;
            }
            $feature = Feature::where('slug', $rec['slug'])->first();
            if (!$feature) {
                continue;
            }
            try {
                $versioning->createVersion(Feature::class, $feature->id, 'AI rescue mapping');
            } catch (\Throwable $e) {
                report($e);
                $this->warn("Failed to create version for feature {$feature->id}: " . $e->getMessage());
            }
            $kategori = IlanKategori::where('slug', $rec['category_slug'])->first();
            if (!$kategori) {
                $manual++;
                continue;
            }
            $ytSlug = YayinTipiRules::canonicalizeSlug($rec['yayin_tipi_slug']);
            $ytCandidates = YayinTipiSablonu::where('kategori_id', $kategori->id)
                ->where('status', 1)
                ->get();
            $yt = null;
            foreach ($ytCandidates as $row) {
                if (YayinTipiRules::canonicalizeSlug($row->yayin_tipi) === $ytSlug) {
                    $yt = $row;
                    break;
                }
            }
            if (!$yt) {
                $fallbackTerms = ['kiralik','gunluk','haftalik','aylik','sezonluk'];
                foreach ($ytCandidates as $row) {
                    $rowSlug = YayinTipiRules::canonicalizeSlug($row->yayin_tipi);
                    if (in_array($rowSlug, $fallbackTerms, true)) {
                        $yt = $row;
                        break;
                    }
                }
            }
            if (!$yt) {
                $manual++;
                continue;
            }
            $exists = FeatureAssignment::where('feature_id', $feature->id)
                ->where('assignable_type', YayinTipiSablonu::class)
                ->where('assignable_id', $yt->id)
                ->exists();
            if ($exists) {
                continue;
            }
            FeatureAssignment::create([
                'feature_id' => $feature->id,
                'assignable_type' => YayinTipiSablonu::class,
                'assignable_id' => $yt->id,
            ]);
            $assigned++;
        }

        LogService::info('ups_ai_rescue_completed', [
            'total_orphans' => count($orphans),
            'assigned' => $assigned,
            'manual_review' => $manual,
            'duration_ms' => (int) LogService::stopTimer($t0),
        ]);
        $this->info("AI Rescue done. Orphans=" . count($orphans) . " assigned={$assigned} manual={$manual}");
        return Command::SUCCESS;
    }
}
