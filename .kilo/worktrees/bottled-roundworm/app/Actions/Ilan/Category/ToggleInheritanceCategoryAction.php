<?php

namespace App\Actions\Ilan\Category;

use App\Services\Ilan\IlanKategoriService;
use App\Models\IlanKategori;

class ToggleInheritanceCategoryAction
{
    public function __construct(
        private readonly IlanKategoriService $kategoriService
    ) {}

    public function handle(IlanKategori $kategori): bool
    {
        return $this->kategoriService->toggleInheritance($kategori);
    }
}
