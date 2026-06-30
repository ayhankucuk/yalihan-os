<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Analytics\IlanAnalizService;
use Illuminate\Http\Request;
use App\Services\Logging\LogService;

/**
 * IlanAnalizController
 * 
 * İlan performans metriklerini yöneten ve dashboard verilerini sağlayan kontrolör.
 * 
 * Context7: 
 * - yayin_durumu, talep_durumu
 * - siralama_sirasi
 * - aktiflik_durumu
 */
class IlanAnalizController extends Controller
{
    public function __construct(
        protected IlanAnalizService $analizService
    ) {}

    /**
     * Genel performans dashboard'unu gösterir.
     */
    public function dashboard()
    {
        $veriler = $this->analizService->getGenelIstatistikler();

        return view('admin.analytics.dashboard_v2', [
            'istatistikler' => $veriler,
            'sayfa_basligi' => 'Cortex Performans Analizi',
            'metrik_durumu' => $veriler['metrik_durumu']
        ]);
    }

    /**
     * Belirli bir ilan için detaylı analiz raporu döner.
     */
    public function show($id)
    {
        try {
            $rapor = $this->analizService->getDetayliRapor($id);

            return view('admin.analytics.ilan_detay_analiz', [
                'rapor' => $rapor,
                'metrik_durumu' => $rapor['metrikler']['metrik_durumu']
            ]);
        } catch (\Exception $e) {
            LogService::error("Analiz raporu yüklenemedi: {$id}", [], $e);
            return redirect()->back()->with('error', 'Analiz verisi alınırken bir hata oluştu.');
        }
    }

    /**
     * API üzerinden metrik verilerini döndürür.
     */
    public function apiStats(Request $request)
    {
        $veriler = $this->analizService->getGenelIstatistikler();
        
        return response()->json([
            'success' => true,
            'data' => $veriler,
            'metrik_durumu' => $veriler['metrik_durumu']
        ]);
    }

    /**
     * Cache temizleme işlemi.
     */
    public function yenile(Request $request, $id = null)
    {
        $this->analizService->clearCache($id);

        return redirect()->back()->with('success', 'Analiz verileri başarıyla tazelendi.');
    }
}
