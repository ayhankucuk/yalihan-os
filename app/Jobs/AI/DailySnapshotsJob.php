<?php

namespace App\Jobs\AI;

use App\Enums\IlanDurumu;

use App\Models\Ilan;
use App\Models\DealPredictionSnapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ️ SAB SEALED
 * Daily Snapshots Job
 * captures historical snapshots of deal scores for all active listings.
 */
class DailySnapshotsJob implements ShouldQueue, \App\Queue\Contracts\TenantAwareJobInterface
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?int $tenantId;
    public ?int $userId;

    public function __construct(?int $tenantId = null, ?int $userId = null)
    {
        $this->tenantId = $tenantId;
        $this->userId = $userId;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function middleware(): array
    {
        return [new \App\Queue\Middleware\RestoreTenantContext(app(\App\Services\SaaS\TenantContextService::class))];
    }

    public function handle()
    {
        Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)->chunk(100, function ($listings) {
            foreach ($listings as $ilan) {
                $latestLog = $ilan->dealPredictions()->latest()->first();

                if ($latestLog) {
                    DealPredictionSnapshot::create([
                        'ilan_id' => $ilan->id,
                        'snapshot_date' => now()->toDateString(),
                        'sale_probability' => $latestLog->sale_probability,
                        'estimated_days_to_sell' => $latestLog->estimated_days_to_sell,
                        'deal_quality_score' => $latestLog->deal_quality_score,
                    ]);
                }
            }
        });
    }
}
