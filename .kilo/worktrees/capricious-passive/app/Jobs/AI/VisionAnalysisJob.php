<?php

namespace App\Jobs\AI;

use App\Services\AI\VisionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VisionAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        protected int $ilanId,
        protected string $dosyaYolu
    ) {}

    public function handle(VisionService $service): void
    {
        // Bekçi onaylı VisionService'i çağır
        $service->analizEt($this->ilanId, $this->dosyaYolu);
    }
}
