<?php

namespace App\Events;

use App\Modules\TakimYonetimi\Models\Gorev;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Görev Durumu Değişti Event
 *
 * Context7: Takım Yönetimi Otomasyonu - Temel Event Sistemi
 * Görev durumu (gorev_durumu) değiştiğinde bu event fırlatılır.
 */
class GorevDurumChanged
{
    use Dispatchable, SerializesModels;

    /**
     * Durumu değişen görev
     */
    public Gorev $gorev;

    /**
     * Eski görev durumu
     */
    public string $eskiDurum;

    /**
     * Yeni görev durumu
     */
    public string $yeniDurum;

    /**
     * Create a new event instance.
     *
     * @param Gorev $gorev
     * @param string $eskiDurum
     * @param string $yeniDurum
     */
    public function __construct(Gorev $gorev, string $eskiDurum, string $yeniDurum)
    {
        $this->gorev = $gorev;
        $this->eskiDurum = $eskiDurum;
        $this->yeniDurum = $yeniDurum;
    }
}
