<?php

namespace App\Jobs\AI;

use App\Models\Lead;
use App\Models\Kisi;
use App\Services\CRMIntelligenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Logging\LogService;

class SyncLeadToIntelligence implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Lead|Kisi
     */
    protected $target;

    /**
     * Create a new job instance.
     */
    public function __construct($target)
    {
        $this->target = $target;
    }

    /**
     * Execute the job.
     */
    public function handle(CRMIntelligenceService $service): void
    {
        LogService::info('Background intelligence sync starting', [
            'type' => get_class($this->target),
            'id' => $this->target->id
        ]);

        $service->syncLead($this->target);
    }
}
