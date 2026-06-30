<?php

namespace App\Actions\Ilan\Category;

use App\Services\Ilan\IlanKategoriService;
use App\Models\IlanKategori;

class CreateCategoryAction
{
    public function __construct(
        private readonly IlanKategoriService $kategoriService
    ) {}

    public function handle(array $data, bool $isJsonRequest = false): IlanKategori
    {
        return $this->kategoriService->createCategory($data, $isJsonRequest);
    }
}
