<?php

namespace App\Listeners;

use App\Events\GorevDurumChanged;
use App\Jobs\NotifyN8nAboutGorevDurumChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Görev Durumu Değişti Event Listener
 *
 * Context7: Takım Yönetimi Otomasyonu - Temel Event Sistemi
 */
class NotifyN8nOnGorevDurumChanged implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(GorevDurumChanged $event): void
    {
        $notificationChannels = ['telegram', 'whatsapp', 'email'];

        NotifyN8nAboutGorevDurumChanged::dispatch(
            $event->gorev->id,
            $event->eskiDurum,
            $event->yeniDurum,
            $notificationChannels
        );
    }
}
