<?php

namespace App\Services\Intelligence;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-catch
 */

use App\Models\Ilan;
use App\Services\Integrations\TKGMService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Contract Guard Service
 * Context7: Otomatik Hukuki Kontrol (Contract Guard) için risk analizi servisi
 *
 * Sözleşme oluşturulurken riskleri anında tespit eder.
 */
class ContractGuardService
{
    public function __construct(
        private TKGMService $tkgmService
    ) {}

    /**
     * İlan için hukuki risk analizi
     *
     * @param Ilan $ilan
     * @param float|null $contractPrice Sözleşme fiyatı (opsiyonel)
     * @return array
     */
    public function analyzeLegalRisks(Ilan $ilan, ?float $contractPrice = null): array
    {
        $contractPrice = $contractPrice ?? (float) $ilan->fiyat;
        $cacheKey = "contract_guard:ilan:{$ilan->id}:price:{$contractPrice}";

        return Cache::remember($cacheKey, 3600 * 24, function () use ($ilan, $contractPrice) {
            try {
                $risks = [];
                $riskScore = 0;
                $maxRiskScore = 100;

                // 1. TKGM İmar Durumu Kontrolü
                $zoningRisk = $this->checkZoningRisk($ilan);
                if ($zoningRisk['has_risk']) {
                    $risks[] = $zoningRisk;
                    $riskScore += $zoningRisk['risk_level'];
                }

                // 2. Vergi Riski Analizi
                $taxRisk = $this->checkTaxRisk($ilan, $contractPrice);
                if ($taxRisk['has_risk']) {
                    $risks[] = $taxRisk;
                    $riskScore += $taxRisk['risk_level'];
                }

                // 3. Tapu Durumu Kontrolü
                $deedRisk = $this->checkDeedRisk($ilan);
                if ($deedRisk['has_risk']) {
                    $risks[] = $deedRisk;
                    $riskScore += $deedRisk['risk_level'];
                }

                // 4. Yasal Durum Kontrolü
                $legalRisk = $this->checkLegalStatus($ilan);
                if ($legalRisk['has_risk']) {
                    $risks[] = $legalRisk;
                    $riskScore += $legalRisk['risk_level'];
                }

                // Genel risk seviyesi
                $overallRiskLevel = $this->determineRiskLevel($riskScore, $maxRiskScore);

                return [
                    'ilan_id' => $ilan->id,
                    'contract_price' => $contractPrice,
                    'risk_score' => min($riskScore, $maxRiskScore),
                    'max_risk_score' => $maxRiskScore,
                    'risk_level' => $overallRiskLevel,
                    'risks' => $risks,
                    'recommendations' => $this->generateRecommendations($risks, $overallRiskLevel),
                    'is_safe' => $overallRiskLevel === 'LOW',
                    'calculated_at' => now(),
                ];
            } catch (\Exception $e) {
                Log::error('Contract guard analysis error', [
                    'ilan_id' => $ilan->id,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'ilan_id' => $ilan->id,
                    'contract_price' => $contractPrice,
                    'risk_score' => 0,
                    'risk_level' => 'UNKNOWN',
                    'risks' => [],
                    'recommendations' => ['Hesaplama hatası: ' . $e->getMessage()],
                    'is_safe' => false,
                ];
            }
        });
    }

    /**
     * İmar durumu riski kontrolü
     */
    private function checkZoningRisk(Ilan $ilan): array
    {
        // Arsa ise TKGM'den imar durumunu kontrol et
        if ($ilan->ada_no && $ilan->parsel_no) {
            try {
                // TKGMService parselSorgula metodu il/ilce string bekliyor
                $ilAdi = $ilan->il ? $ilan->il->adi : '';
                $ilceAdi = $ilan->ilce ? $ilan->ilce->adi : '';
                $mahalleAdi = $ilan->mahalle ? $ilan->mahalle->adi : null;

                $tkgmData = $this->tkgmService->parselSorgula(
                    $ilan->ada_no,
                    $ilan->parsel_no,
                    $ilAdi,
                    $ilceAdi,
                    $mahalleAdi
                );

                if ($tkgmData['success'] ?? false) {
                    $imarDurumu = $tkgmData['data']['imar_durumu'] ?? $tkgmData['data']['imar_durumu'] ?? '';

                    if (stripos($imarDurumu, 'İmar Dışı') !== false || stripos($imarDurumu, 'İmarlı Değil') !== false) {
                        return [
                            'type' => 'zoning', // context7-ignore
                            'title' => 'İmar Durumu Riski',
                            'has_risk' => true,
                            'risk_level' => 40,
                            'message' => '⚠️ İmar dışı arsa - Yapılaşma riski var',
                            'details' => "İmar durumu: {$imarDurumu}",
                            'recommendation' => 'İmar durumunu TKGM\'den doğrulayın ve müşteriyi bilgilendirin.',
                        ];
                    } elseif (stripos($imarDurumu, 'Plan') !== false) {
                        return [
                            'type' => 'zoning', // context7-ignore
                            'title' => 'İmar Planı Uyarısı',
                            'has_risk' => true,
                            'risk_level' => 20,
                            'message' => '🟡 Plan içinde - İmara açılma süreci devam ediyor',
                            'details' => "İmar durumu: {$imarDurumu}",
                            'recommendation' => 'İmar planı onay sürecini takip edin.',
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('TKGM zoning check failed', [
                    'ilan_id' => $ilan->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'type' => 'zoning', // context7-ignore
            'title' => 'İmar Durumu',
            'has_risk' => false,
            'risk_level' => 0,
            'message' => '✅ İmar durumu kontrol edilemedi veya risk yok',
        ];
    }

    /**
     * Vergi riski kontrolü
     */
    private function checkTaxRisk(Ilan $ilan, float $contractPrice): array
    {
        // TKGM'den tapu değeri al
        $tapuDegeri = null;
        if ($ilan->ada_no && $ilan->parsel_no) {
            try {
                // TKGMService parselSorgula metodu il/ilce string bekliyor
                $ilAdi = $ilan->il ? $ilan->il->adi : '';
                $ilceAdi = $ilan->ilce ? $ilan->ilce->adi : '';
                $mahalleAdi = $ilan->mahalle ? $ilan->mahalle->adi : null;

                $tkgmData = $this->tkgmService->parselSorgula(
                    $ilan->ada_no,
                    $ilan->parsel_no,
                    $ilAdi,
                    $ilceAdi,
                    $mahalleAdi
                );

                if ($tkgmData['success'] ?? false) {
                    $tapuDegeri = $tkgmData['data']['tapu_degeri'] ?? $tkgmData['data']['deger'] ?? null;
                }
            } catch (\Exception $e) {
                // Hata statusunda devam et
            }
        }

        if ($tapuDegeri && $tapuDegeri > 0) {
            $fark = $contractPrice - $tapuDegeri;
            $farkPercent = ($fark / $tapuDegeri) * 100;

            // %30'dan fazla fark varsa risk
            if ($farkPercent > 30) {
                return [
                    'type' => 'tax', // context7-ignore
                    'title' => 'Vergi Riski',
                    'has_risk' => true,
                    'risk_level' => 50,
                    'message' => "🔴 Yüksek vergi riski - Fiyat farkı çok yüksek",
                    'details' => sprintf(
                        "Sözleşme Fiyatı: ₺%s | Tapu Değeri: ₺%s | Fark: ₺%s (%%%.2f)",
                        number_format($contractPrice, 0, ',', '.'),
                        number_format($tapuDegeri, 0, ',', '.'),
                        number_format($fark, 0, ',', '.'),
                        $farkPercent
                    ),
                    'recommendation' => sprintf(
                        '₺%s gözleme tabi olabilir. Vergi danışmanına danışın.',
                        number_format($fark, 0, ',', '.')
                    ),
                ];
            } elseif ($farkPercent > 15) {
                return [
                    'type' => 'tax', // context7-ignore
                    'title' => 'Vergi Uyarısı',
                    'has_risk' => true,
                    'risk_level' => 25,
                    'message' => "🟡 Orta vergi riski - Fiyat farkı var",
                    'details' => sprintf(
                        "Sözleşme Fiyatı: ₺%s | Tapu Değeri: ₺%s | Fark: ₺%s (%%%.2f)",
                        number_format($contractPrice, 0, ',', '.'),
                        number_format($tapuDegeri, 0, ',', '.'),
                        number_format($fark, 0, ',', '.'),
                        $farkPercent
                    ),
                    'recommendation' => 'Vergi riski değerlendirmesi yapın.',
                ];
            }
        }

        return [
            'type' => 'tax', // context7-ignore
            'title' => 'Vergi Riski',
            'has_risk' => false,
            'risk_level' => 0,
            'message' => '✅ Vergi riski düşük veya tapu değeri bilinmiyor',
        ];
    }

    /**
     * Tapu durumu kontrolü
     */
    private function checkDeedRisk(Ilan $ilan): array
    {
        // Arsa ise ada/parsel kontrolü
        if ($ilan->ada_no && $ilan->parsel_no) {
            // Ada/parsel bilgisi var, risk yok
            return [
                'type' => 'deed', // context7-ignore
                'title' => 'Tapu Durumu',
                'has_risk' => false,
                'risk_level' => 0,
                'message' => '✅ Ada/Parsel bilgisi mevcut',
            ];
        }

        // Ada/parsel yoksa risk
        return [
            'type' => 'deed', // context7-ignore
            'title' => 'Tapu Bilgisi Eksik',
            'has_risk' => true,
            'risk_level' => 30,
            'message' => '⚠️ Ada/Parsel bilgisi eksik',
            'details' => 'Arsa ilanları için ada/parsel bilgisi zorunludur.',
            'recommendation' => 'Ada ve parsel numaralarını ekleyin.',
        ];
    }

    /**
     * Yasal durum kontrolü
     */
    private function checkLegalStatus(Ilan $ilan): array
    {
        // İlan durumu kontrolü
        $yayinDurumu = $ilan->yayin_durumu;
        $isActive = $yayinDurumu === true || $yayinDurumu === IlanDurumu::YAYINDA->value || $yayinDurumu === 1;

        if (!$isActive) {
            return [
                'type' => 'legal', // context7-ignore
                'title' => 'İlan Durumu',
                'has_risk' => true,
                'risk_level' => 20,
                'message' => '⚠️ İlan aktif değil',
                'details' => "İlan durumu: {$yayinDurumu}",
                'recommendation' => 'Sözleşme öncesi ilan durumunu kontrol edin.',
            ];
        }

        return [
            'type' => 'legal', // context7-ignore
            'title' => 'Yasal Durum',
            'has_risk' => false,
            'risk_level' => 0,
            'message' => '✅ İlan aktif ve yasal durum uygun',
        ];
    }

    /**
     * Risk seviyesi belirle
     */
    private function determineRiskLevel(int $riskScore, int $maxRiskScore): string
    {
        $percentage = ($riskScore / $maxRiskScore) * 100;

        return match (true) {
            $percentage >= 70 => 'CRITICAL',
            $percentage >= 50 => 'HIGH',
            $percentage >= 30 => 'MEDIUM',
            $percentage >= 10 => 'LOW',
            default => 'LOW',
        };
    }

    /**
     * Öneriler oluştur
     */
    private function generateRecommendations(array $risks, string $riskLevel): array
    {
        $recommendations = [];

        if ($riskLevel === 'CRITICAL') {
            $recommendations[] = '🔴 KRİTİK: Sözleşme öncesi tüm riskleri gözden geçirin. Hukuk danışmanına danışın.';
        } elseif ($riskLevel === 'HIGH') {
            $recommendations[] = '🟠 YÜKSEK: Riskler var. Detaylı inceleme yapın.';
        } elseif ($riskLevel === 'MEDIUM') {
            $recommendations[] = '🟡 ORTA: Bazı riskler var. Dikkatli ilerleyin.';
        } else {
            $recommendations[] = '🟢 DÜŞÜK: Risk seviyesi düşük. Normal süreç devam edebilir.';
        }

        foreach ($risks as $risk) {
            if (isset($risk['recommendation'])) {
                $recommendations[] = $risk['recommendation'];
            }
        }

        return $recommendations;
    }

    /**
     * Cache'i temizle
     */
    public function clearCache(int $ilanId): void
    {
        Cache::forget("contract_guard:ilan:{$ilanId}:price:" . (float) 0);
    }
}
