<?php

namespace App\Jobs\AITranslation;

use App\Models\Ilan;
use App\Services\AITranslation\ListingTranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TranslateListingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Ilan $ilan;

    /**
     * Create a new job instance.
     */
    public function __construct(Ilan $ilan)
    {
        $this->ilan = $ilan;
    }

    /**
     * Execute the job.
     */
    public function handle(ListingTranslationService $translationService): void
    {
        try {
            $translationService->translateAll($this->ilan);

            Log::info('listing_translation_job_completed', [
                'listing_id' => $this->ilan->id
            ]);
        } catch (\Throwable $e) {
            Log::error('listing_translation_job_failed', [
                'listing_id' => $this->ilan->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
