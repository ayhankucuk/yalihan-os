<?php

namespace App\Actions\Ilan\Category;

use App\Services\Ilan\IlanKategoriService;
use App\Models\IlanKategori;

class OverrideFeatureCategoryAction
{
    public function __construct(
        private readonly IlanKategoriService $kategoriService
    ) {}

    public function handle(IlanKategori $kategori, int $featureId): void
    {
        $this->kategoriService->overrideFeature($kategori, $featureId);
    }
}
