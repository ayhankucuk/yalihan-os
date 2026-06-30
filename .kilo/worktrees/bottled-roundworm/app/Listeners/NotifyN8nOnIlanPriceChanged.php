<?php

namespace App\Listeners;

use App\Events\IlanPriceChanged;
use App\Jobs\NotifyN8nAboutIlanPriceChange;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * İlan Fiyat Değişikliği Event Listener
 *
 * Context7: Otonom Fiyat Değişim Takibi ve n8n Entegrasyonu
 * Event'i dinler ve n8n'e bildirim gönderecek Job'ı dispatch eder
 */
class NotifyN8nOnIlanPriceChanged implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param IlanPriceChanged $event
     * @return void
     */
    public function handle(IlanPriceChanged $event): void
    {
        // Multi-channel bildirim desteği
        // Varsayılan olarak tüm kanalları aktif et
        // n8n workflow'unda notification_channels alanına göre kanallar seçilebilir
        $notificationChannels = ['telegram', 'whatsapp', 'email'];

        // Job'ı dispatch et
        NotifyN8nAboutIlanPriceChange::dispatch(
            $event->ilan->id,
            $event->oldPrice,
            $event->newPrice,
            $event->currency,
            $notificationChannels
        );
    }
}
