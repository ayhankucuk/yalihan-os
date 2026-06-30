<?php

namespace App\Actions\Ilan\Category;

use App\Services\Ilan\IlanKategoriService;

class BulkActionCategoryAction
{
    public function __construct(
        private readonly IlanKategoriService $kategoriService
    ) {}

    public function handle(string $action, array $ids): int
    {
        return $this->kategoriService->bulkAction($action, $ids);
    }
}
