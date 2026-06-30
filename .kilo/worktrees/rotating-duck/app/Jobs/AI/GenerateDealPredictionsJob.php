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
 * Generate Deal Predictions Job
 */
class GenerateDealPredictionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Ilan $ilan;

    public function __construct(Ilan $ilan)
    {
        $this->ilan = $ilan;
    }

    public function handle(YalihanCortex $cortex)
    {
        try {
            $cortex->predictDeal($this->ilan, [
                'trigger' => 'job',
                'snapshot' => true,
            ]);
        } catch (\Exception $e) {
            Log::error("Job failed for listing {$this->ilan->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
