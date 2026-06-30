<?php

namespace App\Listeners;

use App\Events\GorevCreated;
use App\Jobs\NotifyN8nAboutNewGorev;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Görev Oluşturuldu Event Listener
 *
 * Context7: Takım Yönetimi Otomasyonu - Temel Event Sistemi
 * Event'i dinler ve n8n'e bildirim gönderecek Job'ı dispatch eder
 */
class NotifyN8nOnGorevCreated implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param GorevCreated $event
     * @return void
     */
    public function handle(GorevCreated $event): void
    {
        // Multi-channel bildirim desteği
        $notificationChannels = ['telegram', 'whatsapp', 'email'];

        // Job'ı dispatch et
        NotifyN8nAboutNewGorev::dispatch(
            $event->gorev->id,
            $notificationChannels
        );
    }
}
