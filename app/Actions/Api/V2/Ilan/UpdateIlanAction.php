<?php

namespace App\Actions\Api\V2\Ilan;

use App\Models\Ilan;
use App\Models\V2\Ilan as V2Ilan;
use App\Services\Ilan\IlanCrudService;

class UpdateIlanAction
{
    public function __construct(
        private readonly IlanCrudService $ilanCrudService,
    ) {}

    // Phase3-WA: delegated to IlanCrudService as single write authority
    // Bridge: V2\Ilan → App\Models\Ilan for IlanCrudService compatibility
    public function handle(V2Ilan $v2Ilan, array $data): Ilan
    {
        $ilan = Ilan::findOrFail($v2Ilan->id);

        if (array_key_exists('birim_fiyat', $data) && !array_key_exists('fiyat', $data)) {
            $data['fiyat'] = $data['birim_fiyat'];
        }
        if (array_key_exists('alan_m2', $data) && !array_key_exists('brut_m2', $data)) {
            $data['brut_m2'] = $data['alan_m2'];
        }

        return $this->ilanCrudService->update($ilan, $data);
    }
}
