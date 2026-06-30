<?php

namespace App\Observers;

use App\Enums\IlanDurumu;

use App\Events\TalepReceived;
use App\Models\Talep;

/**
 * TalepObserver
 *
 * Context7: Otonom Fırsat Sentezi ve Bildirim Sistemi
 * Yeni talep oluşturulduğunda TalepReceived event'ini fırlatır
 */
class TalepObserver
{
    /**
     * Handle the Talep "created" event.
     *
     * Context7: Yeni talep oluşturulduğunda event fire edilir
     */
    public function created(Talep $talep): void
    {
        // Sadece aktif talepler için event fire et
        if ($talep->talep_durumu === IlanDurumu::YAYINDA->value || $talep->talep_durumu === 'active') {
            event(new TalepReceived($talep));
        }
    }

    /**
     * Handle the Talep "updated" event.
     */
    public function updated(Talep $talep): void
    {
        //
    }

    /**
     * Handle the Talep "deleted" event.
     */
    public function deleted(Talep $talep): void
    {
        //
    }

    /**
     * Handle the Talep "restored" event.
     */
    public function restored(Talep $talep): void
    {
        //
    }

    /**
     * Handle the Talep "force deleted" event.
     */
    public function forceDeleted(Talep $talep): void
    {
        //
    }
}
