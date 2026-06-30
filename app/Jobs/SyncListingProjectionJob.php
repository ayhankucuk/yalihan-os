<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncListingProjectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @inheritDoc */
    public int $tries = 3;

    /** @inheritDoc */
    public array $backoff = [5, 15, 30];

    public int $ilanId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $ilanId)
    {
        $this->ilanId = $ilanId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $ilan = \App\Models\Ilan::withTrashed()->find($this->ilanId);

        if (!$ilan || $ilan->trashed()) {
            \Illuminate\Support\Facades\DB::table('proj_listings')->where('ilan_id', $this->ilanId)->delete();
            return;
        }

        \Illuminate\Support\Facades\DB::table('proj_listings')->updateOrInsert(
            ['ilan_id' => $ilan->id],
            [
                'baslik' => $ilan->baslik ?? '',
                'yayin_durumu' => $ilan->yayin_durumu ?? 'Taslak',
                'aktiflik_durumu' => $ilan->aktiflik_durumu ?? 1,
                'fiyat' => $ilan->fiyat ?? 0,
                'para_birimi_id' => $ilan->para_birimi_id ?? $ilan->para_birimi, // Support both if needed, but para_birimi_id is preferred
                'sahip_id' => $ilan->danisman_id ?? $ilan->user_id,
                'kategori_id' => $ilan->kategori_id,
                'il_id' => $ilan->il_id,
                'ilce_id' => $ilan->ilce_id,
                'lat' => $ilan->lat,
                'lng' => $ilan->lng,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::critical('SyncListingProjectionJob failed', [
            'ilan_id' => $this->ilanId,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
