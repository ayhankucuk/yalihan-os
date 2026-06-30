<?php

namespace App\Listeners;

use App\Events\GorevDeadlineYaklasiyor;
use App\Jobs\NotifyN8nAboutGorevDeadlineYaklasiyor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Görev Deadline Yaklaşıyor Event Listener
 *
 * Context7: Takım Yönetimi Otomasyonu - Temel Event Sistemi
 */
class NotifyN8nOnGorevDeadlineYaklasiyor implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(GorevDeadlineYaklasiyor $event): void
    {
        $notificationChannels = ['telegram', 'whatsapp', 'email'];

        NotifyN8nAboutGorevDeadlineYaklasiyor::dispatch(
            $event->gorev->id,
            $event->kalanGun,
            $notificationChannels
        );
    }
}
