<?php

namespace App\Actions\Location;

use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Models\Ulke;
use App\Services\Location\AdresCacheService;
use Exception;

class UpdateAdresItemAction
{
    public function __construct(private AdresCacheService $adresCache) {}

    /**
     * @throws Exception
     */
    public function handle(string $type, int $id, string $name)
    {
        switch ($type) {
            case 'ulke':
                $item = Ulke::findOrFail($id);
                $item->update(['ulke_adi' => $name]);
                $this->adresCache->invalidateUlkeler();
                return $item;

            case 'il':
                $item = Il::findOrFail($id);
                $item->update(['il_adi' => $name]);
                $this->adresCache->invalidateIller();
                return $item;

            case 'ilce':
                $item = Ilce::findOrFail($id);
                $oldIlId = $item->il_id;
                $item->update(['ilce_adi' => $name]);
                $this->adresCache->invalidateIlceGroup(array_unique([(int) $oldIlId, (int) $item->il_id]));
                return $item;

            case 'mahalle':
                $item = Mahalle::findOrFail($id);
                $oldIlceId = $item->ilce_id;
                $item->update(['mahalle_adi' => $name]);
                $this->adresCache->invalidateMahalleGroup(array_unique([(int) $oldIlceId, (int) $item->ilce_id]));
                return $item;

            default:
                throw new Exception("Geçersiz adres öğesi türü: {$type}");
        }
    }
}
