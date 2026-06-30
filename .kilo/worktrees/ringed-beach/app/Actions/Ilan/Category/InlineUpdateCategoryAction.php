<?php

namespace App\Actions\Ilan\Category;

use App\Models\IlanKategori;
use App\Services\Ilan\IlanKategoriService;

class InlineUpdateCategoryAction
{
    public function __construct(
        private readonly IlanKategoriService $kategoriService
    ) {}

    public function handle(IlanKategori $kategori, string $field, mixed $value): IlanKategori
    {
        return $this->kategoriService->inlineUpdateCategory($kategori, $field, $value);
    }
}
