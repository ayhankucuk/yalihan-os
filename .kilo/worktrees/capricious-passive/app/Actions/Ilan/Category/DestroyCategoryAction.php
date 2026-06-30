<?php

namespace App\Actions\Ilan\Category;

use App\Services\Ilan\IlanKategoriService;

class DestroyCategoryAction
{
    public function __construct(
        private readonly IlanKategoriService $kategoriService
    ) {}

    public function handle(int $id): bool
    {
        return $this->kategoriService->deleteCategory($id);
    }
}
