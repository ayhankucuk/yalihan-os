<?php

namespace App\Jobs\AITranslation;

use App\Models\Ilan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RebuildListingTranslationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Ilan::active()->chunk(50, function ($ilanlar) {
            foreach ($ilanlar as $ilan) {
                TranslateListingJob::dispatch($ilan);
            }
        });
    }
}
