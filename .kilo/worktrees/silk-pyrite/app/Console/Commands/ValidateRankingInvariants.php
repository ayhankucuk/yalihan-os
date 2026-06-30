<?php

namespace App\Console\Commands;

use App\Enums\IlanDurumu;

use Illuminate\Console\Command;
use App\Models\Ilan;
use Illuminate\Support\Facades\DB;

class ValidateRankingInvariants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ranking:validate-invariants';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate Phase 19 ranking invariants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🛡️ Checking Ranking Invariants...");
        $errors = [];

        // Invariant 1: visibility_score_not_null
        $nullCount = DB::table('ilanlar')->whereNull('visibility_score')->count();
        if ($nullCount > 0) {
            $errors[] = "CRITICAL: {$nullCount} listings have NULL visibility_score.";
        }

        // Invariant 2: visibility_score_range (0-10000)
        $outOfRange = DB::table('ilanlar')->where('visibility_score', '<', 0)->orWhere('visibility_score', '>', 10000)->count();
        if ($outOfRange > 0) {
            $errors[] = "CRITICAL: {$outOfRange} listings have visibility_score out of range [0-10000].";
        }

        // Invariant 3: published_has_visibility (If published, should ideally have non-zero score or at least be processed)
        // We can check if seo_meta is present for published listings
        $publishedMissingMeta = DB::table('ilanlar')
            ->whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'yayinda'])
            ->whereNull('seo_meta')
            ->count();

        if ($publishedMissingMeta > 0) {
            $errors[] = "WARNING: {$publishedMissingMeta} published listings have missing seo_meta.";
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->error($error);
            }
            return 1;
        }

        $this->info("✅ All Ranking Invariants Passed.");
        return 0;
    }
}
