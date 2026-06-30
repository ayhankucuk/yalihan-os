<?php

namespace App\Actions\Api\V2\Ilan;

use App\Models\Ilan;
use App\Models\V2\Ilan as V2Ilan;
use App\Services\Ilan\IlanCrudService;

class DestroyIlanAction
{
    public function __construct(
        private readonly IlanCrudService $ilanCrudService,
    ) {}

    // Phase3-WA: delegated to IlanCrudService as single write authority
    // Bridge: V2\Ilan → App\Models\Ilan for IlanCrudService compatibility
    public function handle(V2Ilan $v2Ilan): bool
    {
        $ilan = Ilan::findOrFail($v2Ilan->id);

        return $this->ilanCrudService->destroy($ilan);
    }
}
