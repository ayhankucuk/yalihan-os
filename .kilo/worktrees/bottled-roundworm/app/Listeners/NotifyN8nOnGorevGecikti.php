<?php

namespace App\Listeners;

use App\Events\GorevGecikti;
use App\Jobs\NotifyN8nAboutGorevGecikti;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Görev Gecikti Event Listener
 *
 * Context7: Takım Yönetimi Otomasyonu - Temel Event Sistemi
 */
class NotifyN8nOnGorevGecikti implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(GorevGecikti $event): void
    {
        $notificationChannels = ['telegram', 'whatsapp', 'email'];

        NotifyN8nAboutGorevGecikti::dispatch(
            $event->gorev->id,
            $event->gecikmeGunu,
            $notificationChannels
        );
    }
}
