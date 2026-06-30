<?php

namespace App\Actions\Location;

use App\Services\Location\AdresLocationService;
use App\Services\Location\AdresCacheService;

class DestroyAdresItemAction
{
    public function __construct(
        private readonly AdresLocationService $adresLocationService,
        private readonly AdresCacheService $adresCache
    ) {}

    public function handle(string $type, int $id): bool
    {
        switch ($type) {
            case 'ulke':
                $this->adresLocationService->deleteUlke($id);
                $this->adresCache->invalidateUlkeler();
                break;
            case 'il':
                $this->adresLocationService->deleteIl($id);
                $this->adresCache->invalidateIller();
                break;
            case 'ilce':
                $ilce = \App\Models\Ilce::find($id);
                $ilId = $ilce?->il_id;
                $this->adresLocationService->deleteIlce($id);
                $this->adresCache->invalidateTumIlceler();
                if ($ilId) {
                    $this->adresCache->invalidateIlcelerByIl((int) $ilId);
                }
                break;
            case 'mahalle':
                $mahalle = \App\Models\Mahalle::find($id);
                $ilceId = $mahalle?->ilce_id;
                $this->adresLocationService->deleteMahalle($id);
                if ($ilceId) {
                    $this->adresCache->invalidateMahallelerByIlce((int) $ilceId);
                }
                break;
            default:
                throw new \InvalidArgumentException('Geçersiz adres türü');
        }

        return true;
    }
}
