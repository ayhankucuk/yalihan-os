<?php

namespace App\Actions\Admin\Talep;

use App\Models\Talep;
use App\Models\Kisi;
use App\Enums\TalepDurumu;
use App\Enums\KisiDurumu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StoreTalepAction
{
    public function __construct(
        private \App\Services\CRM\TalepAuthorityService $authorityService
    ) {}

    /**
     * Handle the storage of a new Talep and potentially a new Kisi.
     *
     * @param array $data
     * @return Talep
     */
    public function handle(array $data): Talep
    {
        return $this->authorityService->createTalep($data, Auth::user());
    }
}
