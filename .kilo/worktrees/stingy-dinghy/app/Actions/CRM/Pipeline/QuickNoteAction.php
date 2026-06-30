<?php

namespace App\Actions\CRM\Pipeline;

use App\Models\KisiEtkilesim;
use Illuminate\Support\Facades\Auth;

class QuickNoteAction
{
    public function handle(int $kisiId, string $note): KisiEtkilesim
    {
        return KisiEtkilesim::create([
            'kisi_id' => $kisiId,
            'etkilesim_tipi' => 'not',
            'aciklama' => $note,
            'tarih' => now(),
            'kullanici_id' => Auth::id()
        ]);
    }
}
