<?php

namespace App\Actions\CRM\Customer;

use App\Models\Kisi;
use App\Modules\Crm\Services\KisiService;
use App\Services\Admin\KisiManagerService;

class CreateCustomerAction
{
    public function __construct(
        private readonly KisiManagerService $managerService
    ) {}

    public function handle(array $data): Kisi
    {
        return $this->managerService->store($data);
    }
}
