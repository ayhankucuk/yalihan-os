<?php

namespace App\Modules\TakimYonetimi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TakimYonetimi\Models\Gorev;
use Illuminate\Http\Request;

class GorevController extends Controller
{
    public function index(Request $request)
    {
        $durum = $request->get('durum');
        $query = Gorev::with(['atananUser', 'olusturanUser'])->latest();

        if ($durum) {
            $query->where('gorev_durumu', $durum);
        }

        $gorevler = $query->paginate(20);

        // Context7: Danışmanlar listesi (view için gerekli)
        $danismanlar = \App\Models\User::whereHas('roles', function ($q) {
            $q->where('name', 'danisman');
        })->select(['id', 'name', 'email'])->get();

        $istatistikler = [
            'toplam' => Gorev::count(),
            'beklemede' => Gorev::where('gorev_durumu', 'beklemede')->count(),
            'devam_ediyor' => Gorev::where('gorev_durumu', 'devam_ediyor')->count(),
            'tamamlandi' => Gorev::where('gorev_durumu', 'tamamlandi')->count(),
        ];

        return view('admin.takim-yonetimi.gorevler.index', compact('gorevler', 'istatistikler', 'durum', 'danismanlar'));
    }
}
