<?php

namespace App\Events;

use App\Modules\TakimYonetimi\Models\Gorev;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Görev Deadline Yaklaşıyor Event
 *
 * Context7: Takım Yönetimi Otomasyonu - Temel Event Sistemi
 * Görev deadline'ı yaklaştığında bu event fırlatılır ve hatırlatma gönderilir.
 */
class GorevDeadlineYaklasiyor
{
    use Dispatchable, SerializesModels;

    /**
     * Deadline'ı yaklaşan görev
     */
    public Gorev $gorev;

    /**
     * Kalan gün sayısı
     */
    public int $kalanGun;

    /**
     * Create a new event instance.
     *
     * @param Gorev $gorev
     * @param int $kalanGun
     */
    public function __construct(Gorev $gorev, int $kalanGun)
    {
        $this->gorev = $gorev;
        $this->kalanGun = $kalanGun;
    }
}
