<?php

namespace App\Actions\Ilan\Category;

use App\Services\Ilan\IlanKategoriService;

class UpdateOrderCategoryAction
{
    public function __construct(
        private readonly IlanKategoriService $kategoriService
    ) {}

    public function handle(array $items): void
    {
        $this->kategoriService->updateSequence($items);
    }
}
