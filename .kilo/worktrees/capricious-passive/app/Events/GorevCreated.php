<?php

namespace App\Events;

use App\Modules\TakimYonetimi\Models\Gorev;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Görev Oluşturuldu Event
 *
 * Context7: Takım Yönetimi Otomasyonu - Temel Event Sistemi
 * Görev oluşturulduğunda bu event fırlatılır ve n8n'e bildirim gönderilir.
 */
class GorevCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Oluşturulan görev
     */
    public Gorev $gorev;

    /**
     * Create a new event instance.
     *
     * @param Gorev $gorev
     */
    public function __construct(Gorev $gorev)
    {
        $this->gorev = $gorev;
    }
}
