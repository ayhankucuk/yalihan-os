<?php

namespace App\Services;

use App\Models\IlanFavori;
use Illuminate\Support\Facades\DB;

class FavoriService
{
    public function toggleFavori(int $ilanId, int $kisiId): array
    {
        return DB::transaction(function () use ($ilanId, $kisiId) {
            $favori = IlanFavori::firstOrNew([
                'ilan_id' => $ilanId,
                'kisi_id' => $kisiId,
            ]);

            $favori->aktiflik_durumu = $favori->exists ? !$favori->aktiflik_durumu : true;
            $favori->save();

            return [
                'status' => 'success',
                'is_favorited' => (bool)$favori->aktiflik_durumu,
                'message' => $favori->aktiflik_durumu ? 'İlan favorilere eklendi.' : 'İlan favorilerden çıkarıldı.'
            ];
        });
    }
}
