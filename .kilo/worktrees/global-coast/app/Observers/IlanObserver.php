<?php

namespace App\Observers;

use App\Models\Ilan;
use App\Jobs\AITranslation\TranslateListingJob;

class IlanObserver
{
    /**
     * Handle the Ilan "saved" event.
     */
    public function saved(Ilan $ilan): void
    {
        // Yalnızca başlık veya açıklama değiştiyse veya yeni kayıt ise çeviri tetikle
        if ($ilan->wasRecentlyCreated || $ilan->wasChanged(['baslik', 'aciklama'])) {
            TranslateListingJob::dispatch($ilan);
        }
    }
}
