<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Services\Integrations\TKGMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Arsa Hesaplama Controller
 * Sadece arsa seçiminde aktif olacak backend hesaplamaları
 * Context7 Standard: C7-ARSA-CALCULATION-2025-10-17
 */
class ArsaCalculationController extends AdminController
{
    protected $tkgmService;

    public function __construct(TKGMService $tkgmService)
    {
        $this->tkgmService = $tkgmService;
    }

    /**
     * KAKS/TAKS Hesaplama
     * POST /admin/api/arsa/calculate
     */
    public function calculate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alan_m2' => 'required|numeric|min:1',
            'kaks' => 'nullable|numeric|min:0|max:10',
            'taks' => 'nullable|numeric|min:0|max:1',
            'ada' => 'nullable|string',
            'parsel' => 'nullable|string',
            'il' => 'nullable|string',
            'ilce' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Hesaplama için gerekli bilgiler eksik',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $alanM2 = floatval($request->alan_m2);
            $kaks = floatval($request->kaks ?? 0);
            $taks = floatval($request->taks ?? 0);

            // Temel hesaplamalar
            $calculations = [
                'alan_m2' => $alanM2,
                'alan_dunum' => $alanM2 / 1000,
                'kaks' => $kaks,
                'taks' => $taks,
                'maksimum_insaat_alani' => $kaks > 0 ? $alanM2 * $kaks : 0,
                'maksimum_taban_alani' => $taks > 0 ? $alanM2 * $taks : 0,
                'maksimum_kat_sayisi' => ($kaks > 0 && $taks > 0) ? ceil($kaks / $taks) : 0,
                'birim_fiyat_m2' => 0,
                'toplam_deger' => 0,
            ];

            // TKGM Sorgulaması (isteğe bağlı)
            $tkgmData = null;
            if ($request->filled(['ada', 'parsel', 'il', 'ilce'])) {
                $tkgmData = $this->tkgmService->parselSorgula(
                    $request->ada,
                    $request->parsel,
                    $request->il,
                    $request->ilce
                );
            }

            // Yatırım potansiyeli analizi
            $investmentScore = $this->calculateInvestmentScore($calculations, $tkgmData);

            return response()->json([
                'success' => true,
                'calculations' => $calculations,
                'tkgm_data' => $tkgmData,
                'investment_score' => $investmentScore,
                'calculated_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Arsa hesaplama hatası', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Hesaplama sırasında bir hata oluştu',
            ], 500);
        }
    }

    /**
     * Tapu/Kadastro Sorgulama
     * POST /admin/api/arsa/tkgm-query
     */
    public function tkgmQuery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ada' => 'required|string',
            'parsel' => 'required|string',
            'il' => 'required|string',
            'ilce' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada, parsel, il ve ilçe bilgileri zorunludur',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->tkgmService->parselSorgula(
                $request->ada,
                $request->parsel,
                $request->il,
                $request->ilce
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('TKGM sorgulama hatası', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Tapu kadastro sorgulaması sırasında bir hata oluştu',
            ], 500);
        }
    }

    /**
     * Birim çevirimi
     * POST /admin/api/arsa/convert
     */
    public function convert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|numeric|min:0',
            'from' => 'required|in:m2,dunum',
            'to' => 'required|in:m2,dunum',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $value = floatval($request->value);
        $from = $request->from;
        $to = $request->to;

        if ($from === $to) {
            $result = $value;
        } elseif ($from === 'm2' && $to === 'dunum') {
            $result = $value / 1000;
        } elseif ($from === 'dunum' && $to === 'm2') {
            $result = $value * 1000;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz birim çevirimi',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'original' => [
                'value' => $value,
                'unit' => $from,
            ],
            'converted' => [
                'value' => round($result, 2),
                'unit' => $to,
            ],
        ]);
    }

    /**
     * Yatırım potansiyeli skorlaması
     */
    private function calculateInvestmentScore($calculations, $tkgmData)
    {
        $score = 0;
        $maxScore = 100;
        $factors = [];

        // Alan skoru (0-20)
        $alanM2 = $calculations['alan_m2'];
        if ($alanM2 >= 1000) {
            $alanScore = 20;
            $factors[] = "✅ Optimal alan ({$alanM2} m²)";
        } elseif ($alanM2 >= 500) {
            $alanScore = 15;
            $factors[] = "✅ İyi alan ({$alanM2} m²)";
        } elseif ($alanM2 >= 250) {
            $alanScore = 10;
            $factors[] = "⚠️ Orta alan ({$alanM2} m²)";
        } else {
            $alanScore = 5;
            $factors[] = "❌ Küçük alan ({$alanM2} m²)";
        }
        $score += $alanScore;

        // KAKS skoru (0-30)
        $kaks = $calculations['kaks'];
        if ($kaks >= 1.5) {
            $kaksScore = 30;
            $factors[] = "✅ Yüksek KAKS ({$kaks}) - Mükemmel inşaat potansiyeli";
        } elseif ($kaks >= 1.0) {
            $kaksScore = 20;
            $factors[] = "✅ İyi KAKS ({$kaks}) - İyi inşaat potansiyeli";
        } elseif ($kaks >= 0.5) {
            $kaksScore = 10;
            $factors[] = "⚠️ Orta KAKS ({$kaks}) - Orta inşaat potansiyeli";
        } else {
            $kaksScore = 0;
            $factors[] = "❌ Düşük KAKS ({$kaks}) - Sınırlı inşaat";
        }
        $score += $kaksScore;

        // TAKS skoru (0-20)
        $taks = $calculations['taks'];
        if ($taks >= 0.30 && $taks <= 0.40) {
            $taksScore = 20;
            $factors[] = "✅ Optimal TAKS ({$taks}) - İdeal taban alanı";
        } elseif ($taks >= 0.20) {
            $taksScore = 15;
            $factors[] = "✅ İyi TAKS ({$taks})";
        } else {
            $taksScore = 5;
            $factors[] = "⚠️ Düşük TAKS ({$taks})";
        }
        $score += $taksScore;

        // İnşaat alanı potansiyeli (0-20)
        $insaatAlani = $calculations['maksimum_insaat_alani'];
        if ($insaatAlani >= 2000) {
            $insaatScore = 20;
            $factors[] = "✅ Yüksek inşaat potansiyeli ({$insaatAlani} m²)";
        } elseif ($insaatAlani >= 1000) {
            $insaatScore = 15;
            $factors[] = "✅ İyi inşaat potansiyeli ({$insaatAlani} m²)";
        } elseif ($insaatAlani >= 500) {
            $insaatScore = 10;
            $factors[] = "⚠️ Orta inşaat potansiyeli ({$insaatAlani} m²)";
        } else {
            $insaatScore = 5;
            $factors[] = "❌ Düşük inşaat potansiyeli ({$insaatAlani} m²)";
        }
        $score += $insaatScore;

        // TKGM veri bonusu (0-10)
        if ($tkgmData && isset($tkgmData['success']) && $tkgmData['success']) {
            $score += 10;
            $factors[] = '✅ TKGM verisi bulundu';
        } else {
            $factors[] = '⚠️ TKGM verisi bulunamadı';
        }

        return [
            'score' => min($score, $maxScore),
            'max_score' => $maxScore,
            'percentage' => round(($score / $maxScore) * 100, 1),
            'factors' => $factors,
            'rating' => $this->getInvestmentRating($score),
        ];
    }

    /**
     * Yatırım rating'i
     */
    private function getInvestmentRating($score)
    {
        if ($score >= 80) {
            return 'Mükemmel';
        }
        if ($score >= 60) {
            return 'İyi';
        }
        if ($score >= 40) {
            return 'Orta';
        }
        if ($score >= 20) {
            return 'Zayıf';
        }

        return 'Çok Zayıf';
    }
}
