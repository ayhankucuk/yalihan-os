<?php

namespace App\Actions\CRM\Customer;

use App\Models\Kisi;
use App\Modules\Crm\Services\KisiService;
use App\Services\Admin\KisiManagerService;

class UpdateCustomerAction
{
    public function __construct(
        private readonly KisiManagerService $managerService
    ) {}

    public function handle(Kisi $kisi, array $data): Kisi
    {
        return $this->managerService->update($kisi, $data);
    }
}
