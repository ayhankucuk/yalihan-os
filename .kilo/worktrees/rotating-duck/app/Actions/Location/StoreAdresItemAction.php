<?php

namespace App\Actions\Location;

use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Models\Ulke;
use App\Services\Location\AdresCacheService;
use Exception;

class StoreAdresItemAction
{
    public function __construct(private AdresCacheService $adresCache) {}

    /**
     * @throws Exception
     */
    public function handle(string $type, string $name, ?int $parentId = null)
    {
        switch ($type) {
            case 'ulke':
                $item = Ulke::create(['ulke_adi' => $name]);
                $this->adresCache->invalidateUlkeler();
                return $item;

            case 'il':
                $item = Il::create(['il_adi' => $name]);
                $this->adresCache->invalidateIller();
                return $item;

            case 'ilce':
                $item = Ilce::create(['il_id' => $parentId, 'ilce_adi' => $name]);
                $this->adresCache->invalidateIlceGroup([(int) $parentId]);
                return $item;

            case 'mahalle':
                $item = Mahalle::create(['ilce_id' => $parentId, 'mahalle_adi' => $name]);
                $this->adresCache->invalidateMahallelerByIlce((int) $parentId);
                return $item;

            default:
                throw new Exception("Geçersiz adres öğesi türü: {$type}");
        }
    }
}
