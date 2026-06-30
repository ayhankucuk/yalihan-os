<?php

namespace App\Jobs\AI;

use App\Services\AI\IlanStorytellingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateStorytellingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $ilanId,
        protected string $ton = 'profesyonel'
    ) {}

    public function handle(IlanStorytellingService $service): void
    {
        $service->olustur($this->ilanId, $this->ton);
    }
}
