<?php

namespace App\Http\Controllers\AI;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Enums\IlanDurumu;
use App\Models\Ilan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AISearchController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['message' => 'AI Search endpoint - to be implemented']);
    }

    public function explore(Request $request)
    {
        // ?id= parametresi: belirli bir ilanı öne çıkar
        $highlightId = $request->integer('id', 0);

        // Son 8 aktif ilan — kart grid için (highlight ilan varsa önce getir)
        $query = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->with(['ilce:id,ilce_adi', 'il:id,il_adi', 'anaKategori:id,name,slug', 'fotograflar', 'yayinTipi:id,yayin_tipi']);

        if ($highlightId > 0) {
            $query->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$highlightId]);
        }

        $ilanlar = $query->latest()->take(8)->get();

        // Öne çıkan ilçeler — ilan sayısına göre
        $featuredIlceler = DB::table('ilanlar')
            ->join('ilceler', 'ilanlar.ilce_id', '=', 'ilceler.id')
            ->where('ilanlar.yayin_durumu', IlanDurumu::YAYINDA->value)
            ->whereNotNull('ilanlar.ilce_id')
            ->groupBy('ilceler.id', 'ilceler.ilce_adi')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(6)
            ->select('ilceler.id', 'ilceler.ilce_adi', DB::raw('COUNT(*) as ilan_sayisi'))
            ->get();

        // Kategori bazlı sayılar
        $kategoriSayilari = DB::table('ilanlar')
            ->join('ilan_kategorileri', 'ilanlar.ana_kategori_id', '=', 'ilan_kategorileri.id')
            ->where('ilanlar.yayin_durumu', IlanDurumu::YAYINDA->value)
            ->groupBy('ilan_kategorileri.id', 'ilan_kategorileri.name', 'ilan_kategorileri.slug')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(5)
            ->select('ilan_kategorileri.name', 'ilan_kategorileri.slug', DB::raw('COUNT(*) as toplam'))
            ->get();

        // Toplam istatistikler
        $toplamIlan  = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)->count();
        $toplamIlce  = $featuredIlceler->count();

        return view('ai.explore', compact(
            'ilanlar',
            'featuredIlceler',
            'kategoriSayilari',
            'toplamIlan',
            'toplamIlce',
            'highlightId'
        ));
    }
}
