<?php

namespace App\Console\Commands;

use App\Enums\IlanDurumu;

use Illuminate\Console\Command;
use App\Models\Ilan;

class RankingIntegrityCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ranking:integrity-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify visibility_score invariant (0-10000) for all listings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🛡️  Ranking Integrity Check");

        $violations = Ilan::query()
            ->where(function ($query) {
                $query->where('visibility_score', '<', 0)
                      ->orWhere('visibility_score', '>', 10000);
            })
            ->count();

        $nullScores = Ilan::query()
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->whereNull('visibility_score')
            ->count();

        $this->table(['Metric', 'Count', 'Status'], [
            ['Range Violations', $violations, $violations === 0 ? '✅ PASS' : '❌ FAIL'],
            ['Null Scores (Active)', $nullScores, $nullScores === 0 ? '✅ PASS' : '❌ FAIL'],
        ]);

        if ($violations > 0 || $nullScores > 0) {
            $this->error("🔥 Integrity Check Failed!");
            return 1;
        }

        $this->info("✅ Integrity Check Passed");
        return 0;
    }
}
