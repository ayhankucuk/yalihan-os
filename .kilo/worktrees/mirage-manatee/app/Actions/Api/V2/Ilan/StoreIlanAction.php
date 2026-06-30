<?php

namespace App\Actions\Api\V2\Ilan;

use App\Models\Ilan;
use App\Enums\IlanDurumu;
use App\Services\Ilan\IlanCrudService;

class StoreIlanAction
{
    public function __construct(
        private readonly IlanCrudService $ilanCrudService,
    ) {}

    // Phase3-WA: delegated to IlanCrudService as single write authority
    public function handle(array $data): Ilan
    {
        $ilanData = [
            'danisman_id' => auth('sanctum')->id(),
            'baslik' => $data['baslik'],
            'aciklama' => $data['aciklama'],
            'alan_m2' => $data['alan_m2'],
            'birim_fiyat' => $data['birim_fiyat'],
            'il' => $data['il'],
            'ilce' => $data['ilce'],
            'mahalle' => $data['mahalle'],
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'one_cikan' => $data['one_cikan'] ?? false,
            'yayin_durumu' => IlanDurumu::TASLAK->value,
            'aktiflik_durumu' => true,
        ];

        return $this->ilanCrudService->store($ilanData);
    }
}
