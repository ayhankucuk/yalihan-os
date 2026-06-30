<?php

namespace App\Observers;

use App\Enums\IlanDurumu;

use App\Models\IlanFotografi;
use App\Jobs\Cortex\AnalyzeListingPhotosJob;

/**
 * IlanFotografiObserver
 *
 * 👁️ Cortex Vision tetikleyici.
 * Yeni fotoğraf eklendiğinde veya silindiğinde analizi kuyruğa atar.
 */
class IlanFotografiObserver
{
    /**
     * Handle the IlanFotografi "created" event.
     */
    public function created(IlanFotografi $ilanFotografi): void
    {
        $this->dispatchAnalysis($ilanFotografi);
    }

    /**
     * Handle the IlanFotografi "updated" event.
     */
    public function updated(IlanFotografi $ilanFotografi): void
    {
        // Eğer dosya yolu değiştiyse tekrar analiz et
        if ($ilanFotografi->isDirty('dosya_yolu')) {
            $this->dispatchAnalysis($ilanFotografi);
        }
    }

    /**
     * Handle the IlanFotografi "deleted" event.
     */
    public function deleted(IlanFotografi $ilanFotografi): void
    {
        $this->dispatchAnalysis($ilanFotografi);
    }

    /**
     * Analiz işini kuyruğa gönderir.
     */
    protected function dispatchAnalysis(IlanFotografi $ilanFotografi): void
    {
        $ilan = $ilanFotografi->ilan;

        if ($ilan) {
            // Anti-Döngü: Sadece Aktif ilanlar öncelikli, ama Taslaklar da işlenebilir.
            // Ancak maliyet kontrolü için sadece yayin_durumu IlanDurumu::YAYINDA->value olanlarda
            // veya yeni oluşturulanlarda tetikleyelim.

            $job = new AnalyzeListingPhotosJob($ilan);

            if (
                $ilan->yayin_durumu instanceof \App\Enums\IlanDurumu
                    ? $ilan->yayin_durumu === \App\Enums\IlanDurumu::YAYINDA
                    : $ilan->yayin_durumu === 'yayinda'
            ) {
                // Aktif ilanlar için hemen (veya kısa gecikmeyle)
                dispatch($job->delay(now()->addSeconds(30)));
            } else {
                // Diğerleri için daha uzun gecikme (maliyet ve yük dengeleme)
                dispatch($job->delay(now()->addMinutes(5)));
            }

            // 🏆 Phase 19.1: Visibility Scoring (Photo changes affect Media Score)
            app(\App\Services\Ranking\ListingRankingService::class)->recalculateAndPersist($ilan);
        }
    }
}
