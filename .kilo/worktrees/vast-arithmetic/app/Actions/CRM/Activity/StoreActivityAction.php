<?php

namespace App\Actions\CRM\Activity;

use App\Models\KisiEtkilesim;
use Illuminate\Support\Facades\Auth;

class StoreActivityAction
{
    public function handle(int $kisiId, array $data): KisiEtkilesim
    {
        $activity = KisiEtkilesim::create([
            'kisi_id' => $kisiId,
            'kullanici_id' => Auth::id(),
            'etkilesim_tipi' => $data['etkilesim_tipi'],
            'aciklama' => $data['aciklama'],
        ]);

        $activity->load('kullanici:id,ad_soyad');

        return $activity;
    }
}
