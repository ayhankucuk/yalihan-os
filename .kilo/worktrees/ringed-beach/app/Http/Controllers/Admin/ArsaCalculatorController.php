<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Services\Cache\ControllerCacheMutationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ArsaCalculatorController extends AdminController
{
    public function __construct(private readonly ControllerCacheMutationService $cacheMutationService) {}

    public function index()
    {
        return view('admin.arsa.calculator');
    }

    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'ada' => 'nullable|string|max:20',
            'parsel' => 'nullable|string|max:20',
            'il' => 'nullable|string|max:50',
            'ilce' => 'nullable|string|max:50',
            'alan' => 'required|numeric|min:0.1',
            'kaks' => 'required|numeric|min:0',
            'taks' => 'required|numeric|min:0',
            'toplam_fiyat' => 'nullable|numeric|min:0',
            'satilabilir_oran' => 'nullable|numeric|min:0|max:100',
            'insaat_birim_maliyet' => 'nullable|numeric|min:0',
            'hedef_satis_m2_fiyati' => 'nullable|numeric|min:0',
            'finansman_maliyeti' => 'nullable|numeric|min:0',
            'vergi_harc_orani' => 'nullable|numeric|min:0|max:100',
            'para_birimi' => 'nullable|string|in:TRY,USD,EUR,GBP',
        ]);

        $alan = (float) $validated['alan'];
        $kaks = (float) $validated['kaks'];
        $taks = (float) $validated['taks'];
        $toplamFiyat = (float) ($validated['toplam_fiyat'] ?? 0);
        $satilabilirOranPct = (float) ($validated['satilabilir_oran'] ?? 80);
        $satilabilirOran = max(0, min(100, $satilabilirOranPct)) / 100.0;
        $insaatBirimMaliyet = (float) ($validated['insaat_birim_maliyet'] ?? 0);
        $hedefSatisM2Fiyati = (float) ($validated['hedef_satis_m2_fiyati'] ?? 0);
        $finansmanMaliyeti = (float) ($validated['finansman_maliyeti'] ?? 0);
        $vergiHarcOranPct = (float) ($validated['vergi_harc_orani'] ?? 0);
        $vergiHarcOran = max(0, min(100, $vergiHarcOranPct)) / 100.0;

        // Çok para birimli girdiler TRY'ye dönüştürülür
        $inputCurrency = strtoupper($validated['para_birimi'] ?? 'TRY');
        if ($inputCurrency && $inputCurrency !== 'TRY') {
            $converter = app(\App\Services\CurrencyConversionService::class);
            $convToplam = $converter->convert($toplamFiyat, $inputCurrency, 'TRY');
            if ($convToplam) {
                $toplamFiyat = (float) $convToplam['amount'];
            }
            $convInsaat = $converter->convert($insaatBirimMaliyet, $inputCurrency, 'TRY');
            if ($convInsaat) {
                $insaatBirimMaliyet = (float) $convInsaat['amount'];
            }
            $convSatis = $converter->convert($hedefSatisM2Fiyati, $inputCurrency, 'TRY');
            if ($convSatis) {
                $hedefSatisM2Fiyati = (float) $convSatis['amount'];
            }
            $convFin = $converter->convert($finansmanMaliyeti, $inputCurrency, 'TRY');
            if ($convFin) {
                $finansmanMaliyeti = (float) $convFin['amount'];
            }
        }

        $maxInsaatAlani = $alan * $kaks;
        $maxTabanAlani = $alan * $taks;
        $metreFiyati = $alan > 0 && $toplamFiyat > 0 ? ($toplamFiyat / $alan) : null;

        $satilabilirAlani = $maxInsaatAlani * $satilabilirOran;
        $toplamInsaatMaliyeti = $maxInsaatAlani * $insaatBirimMaliyet;
        $toplamSatisGeliri = $satilabilirAlani * $hedefSatisM2Fiyati;
        $vergiHarci = $toplamSatisGeliri * $vergiHarcOran;
        $toplamMaliyet = ($toplamFiyat ?: 0) + $toplamInsaatMaliyeti + $finansmanMaliyeti + $vergiHarci;
        $netKar = $toplamSatisGeliri - $toplamMaliyet;
        $roi = $toplamMaliyet > 0 ? ($netKar / $toplamMaliyet) : null;
        $breakEvenM2Fiyat = $satilabilirAlani > 0 ? ($toplamMaliyet / $satilabilirAlani) : null;
        $breakEvenInsaatBirimMaliyet = $maxInsaatAlani > 0
            ? (($toplamSatisGeliri - ($toplamFiyat + $finansmanMaliyeti + $vergiHarci)) / $maxInsaatAlani)
            : null;

        $sensInsaatUp = $maxInsaatAlani * ($insaatBirimMaliyet * 1.10);
        $sensInsaatDown = $maxInsaatAlani * ($insaatBirimMaliyet * 0.90);
        $sensSalesUp = $satilabilirAlani * ($hedefSatisM2Fiyati * 1.10);
        $sensSalesDown = $satilabilirAlani * ($hedefSatisM2Fiyati * 0.90);
        $sensInsaatUpCost = ($toplamFiyat ?: 0) + $sensInsaatUp + $finansmanMaliyeti + $vergiHarci;
        $sensInsaatDownCost = ($toplamFiyat ?: 0) + $sensInsaatDown + $finansmanMaliyeti + $vergiHarci;
        $sensSalesUpCost = ($toplamFiyat ?: 0)
            + $toplamInsaatMaliyeti
            + $finansmanMaliyeti
            + ($sensSalesUp * $vergiHarcOran);
        $sensSalesDownCost = ($toplamFiyat ?: 0)
            + $toplamInsaatMaliyeti
            + $finansmanMaliyeti
            + ($sensSalesDown * $vergiHarcOran);

        $sensRoiInsaatUp = ($toplamSatisGeliri - $sensInsaatUpCost) / max(1, $sensInsaatUpCost);
        $sensRoiInsaatDown = ($toplamSatisGeliri - $sensInsaatDownCost) / max(1, $sensInsaatDownCost);
        $sensRoiSalesUp = ($sensSalesUp - $sensSalesUpCost) / max(1, $sensSalesUpCost);
        $sensRoiSalesDown = ($sensSalesDown - $sensSalesDownCost) / max(1, $sensSalesDownCost);

        $result = [
            'alan' => $alan,
            'kaks' => $kaks,
            'taks' => $taks,
            'max_insaat_alani' => round($maxInsaatAlani, 2),
            'max_taban_alani' => round($maxTabanAlani, 2),
            'metre_fiyati' => $metreFiyati ? round($metreFiyati, 2) : null,
            'satilabilir_oran' => round($satilabilirOranPct, 2),
            'satilabilir_alani' => round($satilabilirAlani, 2),
            'insaat_birim_maliyet' => round($insaatBirimMaliyet, 2),
            'hedef_satis_m2_fiyati' => round($hedefSatisM2Fiyati, 2),
            'toplam_insaat_maliyeti' => round($toplamInsaatMaliyeti, 2),
            'toplam_satis_geliri' => round($toplamSatisGeliri, 2),
            'finansman_maliyeti' => round($finansmanMaliyeti, 2),
            'vergi_harc_orani' => round($vergiHarcOranPct, 2),
            'vergi_harci' => round($vergiHarci, 2),
            'toplam_maliyet' => round($toplamMaliyet, 2),
            'net_kar' => round($netKar, 2),
            'roi' => $roi !== null ? round($roi, 4) : null,
            'break_even_m2_fiyati' => $breakEvenM2Fiyat !== null ? round($breakEvenM2Fiyat, 2) : null,
            'break_even_insaat_birim_maliyet' => $breakEvenInsaatBirimMaliyet !== null
                ? round($breakEvenInsaatBirimMaliyet, 2)
                : null,
            'sensitivities' => [
                'roi_insaat_up_10' => round($sensRoiInsaatUp, 4),
                'roi_insaat_down_10' => round($sensRoiInsaatDown, 4),
                'roi_satis_up_10' => round($sensRoiSalesUp, 4),
                'roi_satis_down_10' => round($sensRoiSalesDown, 4),
            ],
        ];

        $userId = Auth::id();
        $historyKey = 'arsa_calc_history_' . $userId;
        $record = [
            'input' => $validated,
            'result' => $result,
            'timestamp' => now()->toISOString(),
        ];
        $history = Cache::get($historyKey, []);
        array_unshift($history, $record);
        $history = array_slice($history, 0, 50);
        $this->cacheMutationService->put($historyKey, $history, 86400);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function history()
    {
        $userId = Auth::id();
        $historyKey = 'arsa_calc_history_' . $userId;
        $history = Cache::get($historyKey, []);

        return response()->json(['success' => true, 'data' => $history]);
    }
}
