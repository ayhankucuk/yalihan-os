<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Models\Ilan;
use App\Models\Talep;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;

/**
 * Talep Portfolyo Controller
 *
 * Context7 Standardı: C7-TALEP-PORTFOLYO-2025-11-26
 * AI-Powered Talep-Portföy Eşleştirme Sistemi
 */
class TalepPortfolyoController extends Controller
{
    /**
     * Talep Portfolyo ana sayfası.
     * Context7: N+1 Query önleme - eager loading
     */
    public function index(Request $request)
    {
        // Context7: Eager loading ile N+1 query önleme
        $talepler = Talep::with(['kisi:id,ad,soyad,telefon', 'il:id,il_adi', 'ilce:id,ilce_adi'])
            ->latest()
            ->paginate(20);

        // Context7: İstatistikleri cache ile hesapla
        $talepStats = Cache::remember('talep_portfolyo_stats', 300, function () {
            return [
                'toplam_talep' => Talep::count(),
                'aktif_talep' => Talep::where('talep_durumu', IlanDurumu::YAYINDA->value)->count(), // ✅ SAB: talep_durumu
                'pasif_talep' => Talep::where('talep_durumu', 'Pasif')->count(), // ✅ SAB: talep_durumu
            ];
        });

        $portfolyoStats = Cache::remember('portfolyo_stats', 300, function () {
            return [
                'toplam_ilan' => Ilan::where('yayin_durumu', 'Yayında')->count(),
                'taslakyayinda_ilan' => Ilan::where('yayin_durumu', 'Taslak')->count(),
            ];
        });

        return view('admin.talep-portfolyo.index', compact('talepler', 'talepStats', 'portfolyoStats'));
    }
}
