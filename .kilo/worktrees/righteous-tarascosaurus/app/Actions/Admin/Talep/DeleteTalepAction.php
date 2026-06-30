<?php

namespace App\Actions\Admin\Talep;

use App\Models\Talep;
use App\Services\CRM\TalepAuthorityService;
use Illuminate\Support\Facades\Auth;

class DeleteTalepAction
{
    public function __construct(
        protected TalepAuthorityService $authorityService
    ) {}

    /**
     * Handle the deletion of a Talep.
     *
     * @param Talep $talep
     * @return bool
     */
    public function handle(Talep $talep): bool
    {
        return $this->authorityService->deleteTalep($talep, Auth::user());
    }
}
