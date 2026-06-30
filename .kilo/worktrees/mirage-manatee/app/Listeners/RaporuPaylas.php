<?php

namespace App\Listeners;

use App\Events\RaporOlusturuldu;
use App\Services\IletisimService;
use App\Services\VipFiltreService;
use Illuminate\Support\Facades\Log;

/**
 * Raporu Paylaş Listener
 * 
 * [YALIHAN_COMMUNICATION_0206]
 * Filters VIPs and delivers report via WhatsApp
 */
class RaporuPaylas
{
    public function __construct(
        private VipFiltreService $vipFiltre,
        private IletisimService $iletisim
    ) {}

    public function handle(RaporOlusturuldu $event): void
    {
        Log::info('[NEURAL_HANDSHAKE] RaporuPaylas triggered', [
            'ilan_id' => $event->ilan->id,
        ]);

        // 1. VIP filtrele
        $uygunVIPler = $this->vipFiltre->filtrele($event->ilan);

        if ($uygunVIPler->isEmpty()) {
            Log::info('[NEURAL_HANDSHAKE] No matching VIPs found', [
                'ilan_id' => $event->ilan->id,
            ]);
            return;
        }

        Log::info('[NEURAL_HANDSHAKE] Found matching VIPs', [
            'ilan_id' => $event->ilan->id,
            'count' => $uygunVIPler->count(),
        ]);

        // 2. Her VIP için iletim
        foreach ($uygunVIPler as $vip) {
            $this->iletisim->sinyalGonder(
                alici: $vip,
                ilan: $event->ilan,
                imzaliUrl: $event->imzaliUrl
            );

            // Rate limiting: 1 saniye bekle
            sleep(1);
        }

        Log::info('[NEURAL_HANDSHAKE] All deliveries processed', [
           'ilan_id' => $event->ilan->id,
            'vip_count' => $uygunVIPler->count(),
        ]);
    }
}
