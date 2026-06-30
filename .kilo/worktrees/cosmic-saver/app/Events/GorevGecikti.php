<?php

namespace App\Events;

use App\Modules\TakimYonetimi\Models\Gorev;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Görev Gecikti Event
 *
 * Context7: Takım Yönetimi Otomasyonu - Temel Event Sistemi
 * Görev deadline'ı geçtiğinde bu event fırlatılır ve acil bildirim gönderilir.
 */
class GorevGecikti
{
    use Dispatchable, SerializesModels;

    /**
     * Geciken görev
     */
    public Gorev $gorev;

    /**
     * Gecikme günü sayısı
     */
    public int $gecikmeGunu;

    /**
     * Create a new event instance.
     *
     * @param Gorev $gorev
     * @param int $gecikmeGunu
     */
    public function __construct(Gorev $gorev, int $gecikmeGunu)
    {
        $this->gorev = $gorev;
        $this->gecikmeGunu = $gecikmeGunu;
    }
}
