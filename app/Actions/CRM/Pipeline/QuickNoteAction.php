<?php

namespace App\Actions\CRM\Pipeline;

use App\Models\KisiEtkilesim;
use Illuminate\Support\Facades\Auth;

class QuickNoteAction
{
    public function handle(int $kisiId, string $note, string $tip = 'not'): KisiEtkilesim
    {
        return KisiEtkilesim::create([
            'kisi_id' => $kisiId,
            'kullanici_id' => Auth::id() ?? 1,
            'tip' => $tip,
            'notlar' => $note,
            'etkilesim_tarihi' => now(),
            'aktiflik_durumu' => true,
        ]);
    }
}
