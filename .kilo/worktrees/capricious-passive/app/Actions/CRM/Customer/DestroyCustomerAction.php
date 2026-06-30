<?php

namespace App\Actions\CRM\Customer;

use App\Models\Kisi;
use App\Modules\Crm\Services\KisiService;

class DestroyCustomerAction
{
    public function __construct(
        private readonly KisiService $kisiService
    ) {}

    public function handle(Kisi $kisi): void
    {
        $this->kisiService->deleteKisi($kisi);
    }
}
