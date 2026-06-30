<?php

namespace App\Jobs\AI;

use App\Models\Ilan;
use App\Services\AI\YalihanCortex;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ️ SAB SEALED
 * Generate Buyer Matches Job
 * Asynchronously triggers the match engine for a specific listing.
 */
class GenerateBuyerMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $ilanId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(YalihanCortex $cortex): void
    {
        $ilan = Ilan::find($this->ilanId);

        if (!$ilan) {
            Log::warning("GenerateBuyerMatchesJob: Ilan #{$this->ilanId} not found.");
            return;
        }

        try {
            $cortex->detectBuyerMatches($ilan);
        } catch (\Exception $e) {
            Log::error("GenerateBuyerMatchesJob failed for Ilan #{$this->ilanId}: " . $e->getMessage());
            throw $e;
        }
    }
}
