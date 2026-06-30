<?php

namespace App\Observers;

use App\Models\IlanKategori;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IlanKategoriObserver
{
    public function updated(IlanKategori $kategori): void
    {
        $this->bustCategory($kategori);
    }

    public function deleted(IlanKategori $kategori): void
    {
        $this->bustCategory($kategori);
    }

    private function bustCategory(IlanKategori $kategori): void
    {
        try {
            Cache::tags(["ups_features", "category:{$kategori->id}"])->flush();
            Log::info('UPS Cache bust (kategori)', ['kategori_id' => $kategori->id]);
        } catch (\Throwable $e) {
            Log::warning('UPS Cache bust failed (kategori)', ['error' => $e->getMessage()]);
        }
    }
}

