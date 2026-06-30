<?php

namespace App\Services\Location;

use App\Actions\Location\BulkDeleteAdresItemAction;

class AdresBulkService
{
    public function __construct(
        private readonly BulkDeleteAdresItemAction $bulkDeleteAdresItemAction
    ) {}

    public function bulkDelete(string $type, array $ids): int
    {
        return $this->bulkDeleteAdresItemAction->handle($type, $ids);
    }
}
