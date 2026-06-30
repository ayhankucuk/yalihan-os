<?php

namespace App\Events;

class PublicationTypeDeleted
{
    public int $kategoriId;

    public int $yayinTipiId;

    public function __construct(int $kategoriId, int $yayinTipiId)
    {
        $this->kategoriId = $kategoriId;
        $this->yayinTipiId = $yayinTipiId;
    }
}
