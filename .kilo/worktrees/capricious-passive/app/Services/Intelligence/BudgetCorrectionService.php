<?php

namespace App\Services\Intelligence;

use App\Enums\IlanDurumu;

use App\Models\Kisi;
use App\Models\Talep;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Budget Correction Service
 * Context7: Akıllı Bütçe Düzeltmesi (Budget Correction) için satın alma gücü analizi servisi
 *
 * Müşterinin beyan ettiği bütçe ile gerçek satın alma gücünü karşılaştırır.
 */
class BudgetCorrectionService
{
    /**
     * Gerçek satın alma gücünü hesapla
     *
     * @param Kisi $kisi
     * @param Talep|null $talep
     * @return array
     */
    public function calculateRealPurchasePower(Kisi $kisi, ?Talep $talep = null): array
    {
        $cacheKey = "budget_correction:kisi:{$kisi->id}";

        return Cache::remember($cacheKey, 3600 * 24, function () use ($kisi, $talep) {
            try {
                if (!$talep) {
                    $talep = $kisi->talepler()->whereIn('status', [IlanDurumu::YAYINDA->value, 1, true])->latest()->first(); // context7-ignore
                }

                if (!$talep) {
                    return [
                        'kisi_id' => $kisi->id,
                        'declared_budget' => 0,
                        'real_purchase_power' => 0,
                        'correction_factor' => 0,
                        'recommendation' => 'Talep bulunamadı',
                        'confidence' => 0,
                    ];
                }

                $declaredBudget = (float) ($talep->max_fiyat ?? $talep->min_fiyat ?? 0);
                if ($declaredBudget <= 0) {
                    return [
                        'kisi_id' => $kisi->id,
                        'declared_budget' => 0,
                        'real_purchase_power' => 0,
                        'correction_factor' => 0,
                        'recommendation' => 'Bütçe bilgisi bulunamadı',
                        'confidence' => 0,
                    ];
                }

                // Gelir düzeyi analizi
                $incomeMultiplier = $this->calculateIncomeMultiplier($kisi);

                // Meslek faktörü
                $professionMultiplier = $this->calculateProfessionMultiplier($kisi);

                // Medeni status faktörü
                $maritalStatusMultiplier = $this->calculateMaritalStatusMultiplier($kisi);

                // Lokasyon istikrarı (aynı yerde ne kadar süre yaşamış)
                $locationStabilityMultiplier = $this->calculateLocationStability($kisi);

                // Mevcut mülk analizi (eğer varsa)
                $existingPropertyValue = $this->estimateExistingPropertyValue($kisi);

                // Kredi kapasitesi tahmini
                $creditCapacity = $this->estimateCreditCapacity($kisi, $declaredBudget);

                // Gerçek satın alma gücü hesaplama
                $basePower = $declaredBudget;
                $adjustedPower = $basePower * $incomeMultiplier * $professionMultiplier * $maritalStatusMultiplier * $locationStabilityMultiplier;
                $realPurchasePower = $adjustedPower + $existingPropertyValue + $creditCapacity;

                $correctionFactor = $realPurchasePower > 0 ? ($realPurchasePower / $declaredBudget) : 1;
                $confidence = $this->calculateConfidence($kisi, $talep);

                return [
                    'kisi_id' => $kisi->id,
                    'kisi_adi' => $kisi->tam_ad,
                    'talep_id' => $talep->id,
                    'declared_budget' => round($declaredBudget, 2),
                    'real_purchase_power' => round($realPurchasePower, 2),
                    'correction_factor' => round($correctionFactor, 2),
                    'budget_increase' => round($realPurchasePower - $declaredBudget, 2),
                    'budget_increase_percent' => round((($realPurchasePower - $declaredBudget) / $declaredBudget) * 100, 2),
                    'recommendation' => $this->generateRecommendation($kisi, $declaredBudget, $realPurchasePower, $correctionFactor),
                    'breakdown' => [
                        'income_multiplier' => round($incomeMultiplier, 2),
                        'profession_multiplier' => round($professionMultiplier, 2),
                        'marital_status_multiplier' => round($maritalStatusMultiplier, 2),
                        'location_stability_multiplier' => round($locationStabilityMultiplier, 2),
                        'existing_property_value' => round($existingPropertyValue, 2),
                        'credit_capacity' => round($creditCapacity, 2),
                    ],
                    'confidence' => $confidence,
                    'calculated_at' => now(),
                ];
            } catch (\Exception $e) {
                Log::error('Budget correction calculation error', [
                    'kisi_id' => $kisi->id,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'kisi_id' => $kisi->id,
                    'declared_budget' => 0,
                    'real_purchase_power' => 0,
                    'correction_factor' => 0,
                    'recommendation' => 'Hesaplama hatası: ' . $e->getMessage(),
                    'confidence' => 0,
                ];
            }
        });
    }

    /**
     * Gelir düzeyi çarpanı hesapla
     */
    private function calculateIncomeMultiplier(Kisi $kisi): float
    {
        $gelirDuzeyi = strtolower((string) ($kisi->gelir_duzeyi ?? ''));

        return match (true) {
            str_contains($gelirDuzeyi, 'yüksek') || str_contains($gelirDuzeyi, 'high') => 1.5,
            str_contains($gelirDuzeyi, 'orta') || str_contains($gelirDuzeyi, 'medium') => 1.2,
            str_contains($gelirDuzeyi, 'düşük') || str_contains($gelirDuzeyi, 'low') => 1.0,
            default => 1.1, // Varsayılan
        };
    }

    /**
     * Meslek çarpanı hesapla
     */
    private function calculateProfessionMultiplier(Kisi $kisi): float
    {
        $meslek = strtolower((string) ($kisi->meslek ?? ''));

        // Yüksek gelirli meslekler
        $highIncomeProfessions = ['doktor', 'mühendis', 'avukat', 'mimar', 'yazılım', 'it', 'ceo', 'müdür'];
        foreach ($highIncomeProfessions as $prof) {
            if (str_contains($meslek, $prof)) {
                return 1.3;
            }
        }

        // Orta gelirli meslekler
        $mediumIncomeProfessions = ['öğretmen', 'hemşire', 'memur', 'bankacı'];
        foreach ($mediumIncomeProfessions as $prof) {
            if (str_contains($meslek, $prof)) {
                return 1.1;
            }
        }

        return 1.0; // Varsayılan
    }

    /**
     * Medeni status çarpanı hesapla
     */
    private function calculateMaritalStatusMultiplier(Kisi $kisi): float
    {
        $medeniStatus = strtolower((string) ($kisi->medeni_status ?? ''));

        // Evli çiftler genellikle daha yüksek bütçe kaldırabilir
        if (str_contains($medeniStatus, 'evli') || str_contains($medeniStatus, 'married')) {
            return 1.2;
        }

        return 1.0;
    }

    /**
     * Lokasyon istikrarı çarpanı hesapla
     */
    private function calculateLocationStability(Kisi $kisi): float
    {
        // Eğer aynı il'de uzun süre yaşamışsa, finansal istikrar göstergesi
        // Bu bilgi şu an yok, varsayılan olarak 1.0 döndürüyoruz
        // Gelecekte son_etkilesim veya başka verilerle hesaplanabilir
        return 1.0;
    }

    /**
     * Mevcut mülk değeri tahmini
     */
    private function estimateExistingPropertyValue(Kisi $kisi): float
    {
        // Müşterinin mevcut mülkleri varsa, satış değeri tahmini
        $existingProperties = $kisi->ilanlarAsSahibi()->whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'yayinda'])->get();

        if ($existingProperties->isEmpty()) {
            return 0;
        }

        // Toplam mülk değerinin %70'i (satış sonrası net değer)
        $totalValue = $existingProperties->sum('fiyat');
        return $totalValue * 0.7;
    }

    /**
     * Kredi kapasitesi tahmini
     */
    private function estimateCreditCapacity(Kisi $kisi, float $declaredBudget): float
    {
        // Basit kredi kapasitesi tahmini
        // Gerçek uygulamada banka API'leri veya daha detaylı hesaplama yapılabilir

        $gelirDuzeyi = strtolower((string) ($kisi->gelir_duzeyi ?? ''));

        // Yüksek gelirli müşteriler için daha yüksek kredi kapasitesi
        if (str_contains($gelirDuzeyi, 'yüksek') || str_contains($gelirDuzeyi, 'high')) {
            return $declaredBudget * 0.5; // Bütçenin %50'si kadar kredi
        } elseif (str_contains($gelirDuzeyi, 'orta') || str_contains($gelirDuzeyi, 'medium')) {
            return $declaredBudget * 0.3; // Bütçenin %30'u kadar kredi
        }

        return $declaredBudget * 0.2; // Varsayılan: %20
    }

    /**
     * Güvenilirlik skoru hesapla
     */
    private function calculateConfidence(Kisi $kisi, Talep $talep): int
    {
        $confidence = 50; // Başlangıç skoru

        // Gelir düzeyi bilgisi varsa +20
        if ($kisi->gelir_duzeyi) {
            $confidence += 20;
        }

        // Meslek bilgisi varsa +15
        if ($kisi->meslek) {
            $confidence += 15;
        }

        // Medeni status bilgisi varsa +10
        if ($kisi->medeni_status) {
            $confidence += 10;
        }

        // Mevcut mülk bilgisi varsa +5
        if ($kisi->ilanlarAsSahibi()->exists()) {
            $confidence += 5;
        }

        return min(100, $confidence);
    }

    /**
     * Öneri mesajı oluştur
     */
    private function generateRecommendation(Kisi $kisi, float $declaredBudget, float $realPurchasePower, float $correctionFactor): string
    {
        $increase = $realPurchasePower - $declaredBudget;
        $increasePercent = (($realPurchasePower - $declaredBudget) / $declaredBudget) * 100;

        if ($correctionFactor >= 1.3) {
            return sprintf(
                "🔴 Bütçeniz ₺%s'de görülüyor ama verilerinize göre ₺%s kadar kaldırabilirsiniz (%%%.0f artış). Daha yüksek fiyatlı mülklere bakabilirsiniz.",
                number_format($declaredBudget, 0, ',', '.'),
                number_format($realPurchasePower, 0, ',', '.'),
                $increasePercent
            );
        } elseif ($correctionFactor >= 1.1) {
            return sprintf(
                "🟠 Bütçeniz ₺%s'de görülüyor ama ₺%s kadar kaldırabilirsiniz (%%%.0f artış). Biraz daha yüksek fiyatlı mülklere bakabilirsiniz.",
                number_format($declaredBudget, 0, ',', '.'),
                number_format($realPurchasePower, 0, ',', '.'),
                $increasePercent
            );
        } elseif ($correctionFactor <= 0.9) {
            return sprintf(
                "🟡 Bütçeniz ₺%s'de görülüyor. Gerçekçi satın alma gücünüz ₺%s civarında olabilir. Bütçenizi gözden geçirmenizi öneririz.",
                number_format($declaredBudget, 0, ',', '.'),
                number_format($realPurchasePower, 0, ',', '.')
            );
        } else {
            return sprintf(
                "🟢 Bütçeniz ₺%s gerçekçi görünüyor. Bu bütçe aralığında uygun mülkler bulabilirsiniz.",
                number_format($declaredBudget, 0, ',', '.')
            );
        }
    }

    /**
     * Cache'i temizle
     */
    public function clearCache(int $kisiId): void
    {
        Cache::forget("budget_correction:kisi:{$kisiId}");
    }
}
