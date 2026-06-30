<?php

namespace App\Events;

use App\Models\Ilan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Rapor Oluşturuldu Event
 * 
 * [YALIHAN_COMMUNICATION_0206]
 * Fires when a yatirim_analiz_raporu is generated
 */
class RaporOlusturuldu
{
    use Dispatchable, SerializesModels;

    public Ilan $ilan;
    public ?string $raporYolu;
    public ?string $raporHash;
    public string $imzaliUrl;

    public function __construct(Ilan $ilan, string $imzaliUrl)
    {
        $this->ilan = $ilan;
        $this->raporYolu = $ilan->rapor_yolu;
        $this->raporHash = $ilan->rapor_hash;
        $this->imzaliUrl = $imzaliUrl;
    }
}
