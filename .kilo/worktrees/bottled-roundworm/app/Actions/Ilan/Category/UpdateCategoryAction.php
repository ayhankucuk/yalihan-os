<?php

namespace App\Actions\Ilan\Category;

use App\Services\Ilan\IlanKategoriService;
use App\Models\IlanKategori;

class UpdateCategoryAction
{
    public function __construct(
        private readonly IlanKategoriService $kategoriService
    ) {}

    public function handle(IlanKategori $kategori, array $data): IlanKategori
    {
        return $this->kategoriService->updateCategory($kategori, $data);
    }
}
