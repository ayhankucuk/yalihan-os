<?php

namespace App\Services\Analytics;

/**
 * @sab-ignore-catch
 */

use App\Modules\CRMSatis\Models\Satis;
use App\Modules\Finans\Models\Komisyon;
use App\Services\Logging\LogService;

/**
 * Komisyon Eksikliği Risk Analizi
 *
 * Context7 Standardı: C7-COMMISSION-RISK-ANALYZER-2025-11-25
 *
 * Çift danışman statusunda komisyon kaybı riskini tespit eder
 */
class CommissionRiskAnalyzer
{
    /**
     * Komisyon eksikliği risk analizi
     *
     * @param  int  $year  Analiz edilecek yıl (varsayılan: geçen yıl)
     * @param  float  $simulationPercentage  Simülasyon yüzdesi (varsayılan: 0.30 = %30)
     * @param  bool  $useSimulation  Simülasyon kullanılsın mı? (varsayılan: true)
     * @return array Analiz sonuçları
     */
    public function analyze(?int $year = null, float $simulationPercentage = 0.30, bool $useSimulation = true): array
    {
        try {
            if ($year === null) {
                $year = now()->subYear()->year;
            }

            // Geçen yıl tamamlanmış satışlar
            $completedSales = Satis::where('islem_durumu', 'tamamlandi')
                ->whereYear('satis_tarihi', $year)
                ->with(['ilan', 'musteri', 'danisman'])
                ->get();

            $riskSales = [];
            $totalRiskAmount = 0;
            $simulatedSales = [];

            // Gerçek risk analizi (mevcut verilerle)
            foreach ($completedSales as $sale) {
                $risk = $this->analyzeSaleRisk($sale);

                if ($risk['has_risk']) {
                    $riskSales[] = $risk;
                    $totalRiskAmount += $risk['risk_amount'];
                }
            }

            // Simülasyon: Satışların %30'unda farklı alıcı danışmanı olduğunu varsay
            if ($useSimulation && $completedSales->count() > 0) {
                $simulationResult = $this->simulateMissingBuyerConsultant($completedSales, $simulationPercentage);
                $simulatedSales = $simulationResult['simulated_risks'];
                $totalRiskAmount += $simulationResult['simulated_risk_amount'];
            }

            // Tüm riskli satışları birleştir
            $allRiskSales = array_merge($riskSales, $simulatedSales);

            // Şiddet seviyesine göre sırala
            usort($allRiskSales, function ($a, $b) {
                return $b['risk_amount'] <=> $a['risk_amount'];
            });

            LogService::action(
                'commission_risk_analysis',
                'satis',
                null,
                [
                    'year' => $year,
                    'total_sales' => $completedSales->count(),
                    'real_risk_sales' => count($riskSales),
                    'simulated_risk_sales' => count($simulatedSales),
                    'total_risk_sales' => count($allRiskSales),
                    'total_risk_amount' => $totalRiskAmount,
                    'simulation_percentage' => $simulationPercentage,
                    'use_simulation' => $useSimulation,
                ]
            );

            return [
                'success' => true,
                'year' => $year,
                'total_completed_sales' => $completedSales->count(),
                'real_risk_sales_count' => count($riskSales),
                'simulated_risk_sales_count' => count($simulatedSales),
                'total_risk_sales_count' => count($allRiskSales),
                'total_risk_amount' => round($totalRiskAmount, 2),
                'real_risk_amount' => round(array_sum(array_column($riskSales, 'risk_amount')), 2),
                'simulated_risk_amount' => round(array_sum(array_column($simulatedSales, 'risk_amount')), 2),
                'average_risk_per_sale' => count($allRiskSales) > 0
                    ? round($totalRiskAmount / count($allRiskSales), 2)
                    : 0,
                'risk_sales' => $allRiskSales,
                'summary' => $this->generateSummary($allRiskSales, $totalRiskAmount, $simulatedSales),
                'simulation' => [
                    'aktiflik_durumu' => $useSimulation,
                    'percentage' => $simulationPercentage,
                    'simulated_count' => count($simulatedSales),
                    'simulated_amount' => round(array_sum(array_column($simulatedSales, 'risk_amount')), 2),
                ],
                'metadata' => [
                    'analyzed_at' => now(),
                    'year' => $year,
                    'simulation_percentage' => $simulationPercentage,
                ],
            ];
        } catch (\Exception $e) {
            LogService::error('Komisyon risk analizi hatası', ['error' => $e->getMessage()], $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'risk_sales' => [],
            ];
        }
    }

    /**
     * Tek bir satış için risk analizi
     *
     * @param  Satis  $sale  Satış kaydı
     * @return array Risk analizi
     */
    private function analyzeSaleRisk(Satis $sale): array
    {
        $hasRisk = false;
        $riskAmount = 0;
        $riskReason = '';
        $simulation = null;

        // İlan danışmanı ile satış danışmanı farklı mı kontrol et
        if ($sale->ilan && $sale->ilan->danisman_id) {
            $ilanDanismanId = $sale->ilan->danisman_id;
            $satisDanismanId = $sale->danisman_id;

            // Eğer farklı danışmanlar varsa ve çift danışman alanları yoksa risk var
            if ($ilanDanismanId != $satisDanismanId) {
                $hasRisk = true;
                $riskReason = 'İlan danışmanı ile satış danışmanı farklı, ancak çift danışman komisyonu hesaplanmamış';

                // Simülasyon: Çift danışman statusunda komisyon nasıl bölüşülürdü
                $simulation = $this->simulateDualCommission($sale, $ilanDanismanId, $satisDanismanId);
                $riskAmount = $simulation['potential_loss'];
            }
        }

        // Müşteri danışmanı kontrolü (eğer müşteri tablosunda danışman bilgisi varsa)
        if ($sale->musteri && $sale->musteri->danisman_id) {
            $musteriDanismanId = $sale->musteri->danisman_id;
            $satisDanismanId = $sale->danisman_id;

            if ($musteriDanismanId != $satisDanismanId && ! $hasRisk) {
                $hasRisk = true;
                $riskReason = 'Müşteri danışmanı ile satış danışmanı farklı, ancak çift danışman komisyonu hesaplanmamış';

                if (! $simulation) {
                    $simulation = $this->simulateDualCommission($sale, $satisDanismanId, $musteriDanismanId);
                    $riskAmount = $simulation['potential_loss'];
                }
            }
        }

        return [
            'has_risk' => $hasRisk,
            'risk_amount' => round($riskAmount, 2),
            'risk_reason' => $riskReason,
            'sale_id' => $sale->id,
            'sale_date' => $sale->satis_tarihi?->format('Y-m-d'),
            'sale_price' => $sale->satis_fiyati,
            'currency' => $sale->para_birimi,
            'current_commission' => $sale->komisyon_tutari,
            'current_commission_rate' => $sale->komisyon_orani,
            'current_danisman_id' => $sale->danisman_id,
            'current_danisman_name' => $sale->danisman ? $sale->danisman->name : 'Bilinmiyor',
            'ilan_id' => $sale->ilan_id,
            'ilan_danisman_id' => $sale->ilan?->danisman_id,
            'ilan_danisman_name' => $sale->ilan && $sale->ilan->danisman
                ? $sale->ilan->danisman->name
                : 'Bilinmiyor',
            'kisi_id' => $sale->kisi_id,
            'musteri_danisman_id' => $sale->musteri?->danisman_id,
            'simulation' => $simulation,
        ];
    }

    /**
     * Çift danışman komisyon simülasyonu
     *
     * @param  Satis  $sale  Satış kaydı
     * @param  int  $saticiDanismanId  Satıcı danışman ID
     * @param  int  $aliciDanismanId  Alıcı danışman ID
     * @return array Simülasyon sonuçları
     */
    private function simulateDualCommission(Satis $sale, int $saticiDanismanId, int $aliciDanismanId): array
    {
        $salePrice = $sale->satis_fiyati ?? 0;
        $currentCommissionRate = $sale->komisyon_orani ?? 3.0; // Varsayılan %3
        $currentCommissionAmount = $sale->komisyon_tutari ?? 0;

        // Çift danışman statusunda komisyon bölüşümü (50-50 veya 60-40)
        // Satıcı danışmanı genelde daha fazla alır (60%)
        $saticiCommissionRate = $currentCommissionRate * 0.6;
        $aliciCommissionRate = $currentCommissionRate * 0.4;

        $saticiCommissionAmount = $salePrice * ($saticiCommissionRate / 100);
        $aliciCommissionAmount = $salePrice * ($aliciCommissionRate / 100);
        $totalDualCommission = $saticiCommissionAmount + $aliciCommissionAmount;

        // Potansiyel kayıp: Eğer çift danışman olsaydı toplam komisyon daha fazla olurdu
        // Ancak mevcut sistemde sadece tek danışmana komisyon verilmiş
        // Risk: Alıcı danışmanına verilmemiş komisyon
        $potentialLoss = $aliciCommissionAmount;

        return [
            'satici_danisman_id' => $saticiDanismanId,
            'satici_danisman_name' => $this->getDanismanName($saticiDanismanId),
            'satici_commission_rate' => round($saticiCommissionRate, 2),
            'satici_commission_amount' => round($saticiCommissionAmount, 2),
            'alici_danisman_id' => $aliciDanismanId,
            'alici_danisman_name' => $this->getDanismanName($aliciDanismanId),
            'alici_commission_rate' => round($aliciCommissionRate, 2),
            'alici_commission_amount' => round($aliciCommissionAmount, 2),
            'total_dual_commission' => round($totalDualCommission, 2),
            'current_single_commission' => round($currentCommissionAmount, 2),
            'potential_loss' => round($potentialLoss, 2),
            'split_ratio' => '60-40', // Satıcı-Alıcı
        ];
    }

    /**
     * Danışman adını al
     *
     * @param  int  $danismanId  Danışman ID
     * @return string Danışman adı
     */
    private function getDanismanName(int $danismanId): string
    {
        try {
            $user = \App\Models\User::find($danismanId);

            return $user ? $user->name : 'Bilinmiyor';
        } catch (\Exception $e) {
            return 'Bilinmiyor';
        }
    }

    /**
     * Simülasyon: Eksik alıcı danışmanı olan satışları simüle et
     *
     * @param  \Illuminate\Support\Collection  $completedSales  Tamamlanmış satışlar
     * @param  float  $percentage  Simülasyon yüzdesi (örn: 0.30 = %30)
     * @return array Simülasyon sonuçları
     */
    private function simulateMissingBuyerConsultant($completedSales, float $percentage): array
    {
        $totalSales = $completedSales->count();
        $simulationCount = (int) ceil($totalSales * $percentage);

        // Rastgele satışları seç (mevcut danışmanından farklı olacak şekilde)
        $selectedSales = $completedSales->random(min($simulationCount, $totalSales));

        // Danışman listesini al (sadece danışman rolüne sahip kullanıcılar)
        $danismans = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('name', 'danisman');
        })->orWhereHas('role', function ($query) {
            $query->where('name', 'danishan');
        })->get();

        if ($danismans->isEmpty()) {
            // Fallback: Tüm aktif kullanıcıları al
            $danismans = \App\Models\User::where('aktiflik_durumu', 1)->get();
        }

        $simulatedRisks = [];
        $simulatedRiskAmount = 0;

        foreach ($selectedSales as $sale) {
            // Mevcut danışmandan farklı bir alıcı danışmanı seç
            $currentDanismanId = $sale->danisman_id;
            $availableDanismans = $danismans->filter(function ($danisman) use ($currentDanismanId) {
                return $danisman->id != $currentDanismanId;
            });

            if ($availableDanismans->isEmpty()) {
                continue;
            }

            $buyerDanisman = $availableDanismans->random();

            // Simülasyon: Çift danışman statusunda komisyon kaybı
            $simulation = $this->simulateDualCommission($sale, $currentDanismanId, $buyerDanisman->id);

            $simulatedRisks[] = [
                'has_risk' => true,
                'risk_amount' => round($simulation['potential_loss'], 2),
                'risk_reason' => 'Simülasyon: Satışların %'.($percentage * 100).'\'unda farklı alıcı danışmanı olması gerektiği varsayıldı',
                'is_simulated' => true,
                'sale_id' => $sale->id,
                'sale_date' => $sale->satis_tarihi?->format('Y-m-d'),
                'sale_price' => $sale->satis_fiyati,
                'currency' => $sale->para_birimi,
                'current_commission' => $sale->komisyon_tutari,
                'current_commission_rate' => $sale->komisyon_orani,
                'current_danisman_id' => $currentDanismanId,
                'current_danisman_name' => $sale->danisman ? $sale->danisman->name : 'Bilinmiyor',
                'simulated_buyer_danisman_id' => $buyerDanisman->id,
                'simulated_buyer_danisman_name' => $buyerDanisman->name,
                'ilan_id' => $sale->ilan_id,
                'kisi_id' => $sale->kisi_id,
                'simulation' => $simulation,
            ];

            $simulatedRiskAmount += $simulation['potential_loss'];
        }

        return [
            'simulated_risks' => $simulatedRisks,
            'simulated_risk_amount' => $simulatedRiskAmount,
            'simulation_count' => count($simulatedRisks),
        ];
    }

    /**
     * Özet rapor oluştur
     *
     * @param  array  $riskSales  Riskli satışlar
     * @param  float  $totalRiskAmount  Toplam risk tutarı
     * @param  array  $simulatedSales  Simüle edilmiş satışlar
     * @return array Özet
     */
    private function generateSummary(array $riskSales, float $totalRiskAmount, array $simulatedSales = []): array
    {
        $riskByReason = [];
        $affectedDanismans = [];
        $realRiskCount = 0;
        $simulatedRiskCount = 0;
        $realRiskAmount = 0;
        $simulatedRiskAmount = 0;

        foreach ($riskSales as $risk) {
            $reason = $risk['risk_reason'];
            if (! isset($riskByReason[$reason])) {
                $riskByReason[$reason] = [
                    'count' => 0,
                    'total_amount' => 0,
                    'simulated_count' => 0,
                    'simulated_amount' => 0,
                ];
            }

            $isSimulated = $risk['is_simulated'] ?? false;

            if ($isSimulated) {
                $simulatedRiskCount++;
                $simulatedRiskAmount += $risk['risk_amount'];
                $riskByReason[$reason]['simulated_count']++;
                $riskByReason[$reason]['simulated_amount'] += $risk['risk_amount'];
            } else {
                $realRiskCount++;
                $realRiskAmount += $risk['risk_amount'];
            }

            $riskByReason[$reason]['count']++;
            $riskByReason[$reason]['total_amount'] += $risk['risk_amount'];

            if (isset($risk['ilan_danisman_id']) && $risk['ilan_danisman_id']) {
                $affectedDanismans[] = $risk['ilan_danisman_id'];
            }
            if (isset($risk['current_danisman_id']) && $risk['current_danisman_id']) {
                $affectedDanismans[] = $risk['current_danisman_id'];
            }
            if (isset($risk['simulated_buyer_danisman_id']) && $risk['simulated_buyer_danisman_id']) {
                $affectedDanismans[] = $risk['simulated_buyer_danisman_id'];
            }
        }

        return [
            'total_risk_sales' => count($riskSales),
            'real_risk_sales' => $realRiskCount,
            'simulated_risk_sales' => $simulatedRiskCount,
            'total_risk_amount' => round($totalRiskAmount, 2),
            'real_risk_amount' => round($realRiskAmount, 2),
            'simulated_risk_amount' => round($simulatedRiskAmount, 2),
            'average_risk_per_sale' => count($riskSales) > 0
                ? round($totalRiskAmount / count($riskSales), 2)
                : 0,
            'risk_by_reason' => $riskByReason,
            'affected_danismans_count' => count(array_unique($affectedDanismans)),
            'recommendations' => $this->generateRecommendations($riskSales, $totalRiskAmount, $simulatedRiskAmount),
        ];
    }

    /**
     * Öneriler oluştur
     *
     * @param  array  $riskSales  Riskli satışlar
     * @param  float  $totalRiskAmount  Toplam risk tutarı
     * @param  float  $simulatedRiskAmount  Simüle edilmiş risk tutarı
     * @return array Öneriler
     */
    private function generateRecommendations(array $riskSales, float $totalRiskAmount, float $simulatedRiskAmount = 0): array
    {
        $recommendations = [];
        $realRiskCount = count(array_filter($riskSales, fn ($r) => ! ($r['is_simulated'] ?? false)));
        $simulatedRiskCount = count(array_filter($riskSales, fn ($r) => $r['is_simulated'] ?? false));

        if ($realRiskCount > 0) {
            $recommendations[] = $realRiskCount.' satışta gerçek komisyon eksikliği tespit edildi.';
        }

        if ($simulatedRiskCount > 0) {
            $recommendations[] = $simulatedRiskCount
                .' satışta simüle edilmiş komisyon eksikliği tespit edildi'
                .' (satışların %30\'unda farklı alıcı danışmanı olduğu varsayıldı).';
            $recommendations[] = 'Simüle edilmiş risk tutarı: '.number_format($simulatedRiskAmount, 2).' TL.';
        }

        if ($totalRiskAmount > 100000) {
            $recommendations[] = 'Toplam risk tutarı çok yüksek ('.number_format($totalRiskAmount, 2).' TL). Acil çift danışman komisyon sistemi kurulmalı.';
        } elseif ($totalRiskAmount > 50000) {
            $recommendations[] = 'Toplam risk tutarı yüksek ('.number_format($totalRiskAmount, 2).' TL). Çift danışman komisyon sistemi planlanmalı.';
        } elseif ($totalRiskAmount > 20000) {
            $recommendations[] = 'Orta seviyede risk tutarı var ('.number_format($totalRiskAmount, 2).' TL). Çift danışman komisyon sistemi değerlendirilmeli.';
        }

        $recommendations[] = 'Satış kayıtlarına `satici_danisman_id` ve `alici_danisman_id` alanları eklenmeli.';
        $recommendations[] = 'Komisyon hesaplama sistemi çift danışman statusunu desteklemeli.';
        $recommendations[] = 'Gelecekteki satışlarda alıcı danışmanı bilgisi mutlaka kaydedilmeli.';

        return $recommendations;
    }
}
