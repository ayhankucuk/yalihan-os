<?php

namespace App\Events;

class PublicationTypeReassigned
{
    public int $kategoriId;

    public int $fromYayinTipiId;

    public int $toYayinTipiId;

    public int $affectedCount;

    public function __construct(int $kategoriId, int $fromYayinTipiId, int $toYayinTipiId, int $affectedCount)
    {
        $this->kategoriId = $kategoriId;
        $this->fromYayinTipiId = $fromYayinTipiId;
        $this->toYayinTipiId = $toYayinTipiId;
        $this->affectedCount = $affectedCount;
    }
}
