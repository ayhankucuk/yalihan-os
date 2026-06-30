<?php

namespace App\Modules\TakimYonetimi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TakimUyesi;
use Illuminate\Http\Request;

class TakimController extends Controller
{
    public function index(Request $request)
    {
        $durum = $request->get('aktiflik_durumu');
        $query = TakimUyesi::with('user')->orderBy('performans_skoru', 'desc');

        if ($durum) {
            $query->where('yayin_durumu', $durum);
        }

        $takimUyeleri = $query->paginate(20);

        $istatistikler = [
            'toplam' => TakimUyesi::count(),
            'aktif' => TakimUyesi::where('aktiflik_durumu', 'aktif')->count(),
            'pasif' => TakimUyesi::where('aktiflik_durumu', 'pasif')->count(),
            'ortalama_performans' => TakimUyesi::avg('performans_skoru'),
        ];

        $lokasyonlar = TakimUyesi::select('lokasyon')->distinct()->whereNotNull('lokasyon')->pluck('lokasyon');

        return view('admin.takim-yonetimi.takim.index', compact('takimUyeleri', 'istatistikler', 'lokasyonlar', 'aktiflik_durumu'));
    }

    public function performans()
    {
        $topPerformers = TakimUyesi::with('user')
            ->orderBy('performans_skoru', 'desc')
            ->take(10)
            ->get();

        return view('admin.takim-yonetimi.takim.performans', compact('topPerformers'));
    }
}
