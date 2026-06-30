<?php

namespace App\Services\Intelligence;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-catch
 */

use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\Talep;
use App\Modules\Finans\Models\FinansalIslem;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\AI\YalihanCortex;
use App\Services\Intelligence\ActionScoreService;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Cache;

/**
 * Cross-Module Intelligence Service
 * Context7: Modüller Arası Zeka (Cross-Module Intelligence)
 *
 * Modüller arası veri paylaşımı ve akıllı entegrasyon sağlar.
 * CRM ↔ İlan ↔ Finans ↔ Takım arası otomatik bağlantılar kurar.
 */
class CrossModuleIntelligenceService
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private YalihanCortex $cortex,
        private ActionScoreService $actionScore
    ) {}

    /**
     * CRM → İlan: Müşteri taleplerine göre ilan önerileri
     *
     * @param Kisi $kisi
     * @return array
     */
    public function suggestListingsForCustomer(Kisi $kisi): array
    {
        $cacheKey = "cross_module:crm_to_ilan:kisi:{$kisi->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($kisi) {
            try {
                $talep = $kisi->talepler()
                    ->whereIn('status', [IlanDurumu::YAYINDA->value, 1, true]) // context7-ignore
                    ->latest()
                    ->first();

                if (!$talep) {
                    return [
                        'success' => false,
                        'message' => 'Aktif talep bulunamadı',
                        'suggestions' => [],
                    ];
                }

                $matchResult = $this->cortex->matchForSale($talep);
                $matches = $matchResult['matches'] ?? [];

                return [
                    'success' => true,
                    'kisi_id' => $kisi->id,
                    'talep_id' => $talep->id,
                    'suggestions' => array_slice($matches, 0, 5),
                    'total_matches' => count($matches),
                    'calculated_at' => now(),
                ];
            } catch (\Exception $e) {
                LogService::error('CRM → İlan önerisi hatası', [
                    'kisi_id' => $kisi->id,
                    'error' => $e->getMessage(),
                ], $e);

                return [
                    'success' => false,
                    'message' => 'Öneri oluşturulamadı',
                    'suggestions' => [],
                ];
            }
        });
    }

    /**
     * İlan → Finans: İlan satışından otomatik komisyon hesaplama
     *
     * @param Ilan $ilan
     * @param float|null $salePrice Satış fiyatı
     * @return array
     */
    public function calculateCommissionFromSale(Ilan $ilan, ?float $salePrice = null): array
    {
        $salePrice = $salePrice ?? (float) $ilan->fiyat;
        $cacheKey = "cross_module:ilan_to_finans:ilan:{$ilan->id}:price:{$salePrice}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($ilan, $salePrice) {
            try {
                $commissionRate = config('finans.commission_rate', 0.03);
                $commission = $salePrice * $commissionRate;

                return [
                    'success' => true,
                    'ilan_id' => $ilan->id,
                    'sale_price' => $salePrice,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => round($commission, 2),
                    'danisman_id' => $ilan->danisman_id,
                    'calculated_at' => now(),
                ];
            } catch (\Exception $e) {
                LogService::error('İlan → Finans komisyon hesaplama hatası', [
                    'ilan_id' => $ilan->id,
                    'error' => $e->getMessage(),
                ], $e);

                return [
                    'success' => false,
                    'message' => 'Komisyon hesaplanamadı',
                    'commission_amount' => 0,
                ];
            }
        });
    }

    /**
     * Finans → Takım: Komisyon beklentisine göre görev önceliklendirme
     *
     * @param FinansalIslem $islem
     * @return array
     */
    public function prioritizeTaskByCommission(FinansalIslem $islem): array
    {
        $cacheKey = "cross_module:finans_to_takim:islem:{$islem->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($islem) {
            try {
                $commission = (float) $islem->miktar;
                $priority = $this->determinePriorityByAmount($commission);

                return [
                    'success' => true,
                    'islem_id' => $islem->id,
                    'commission_amount' => $commission,
                    'priority' => $priority,
                    'recommendation' => $this->getPriorityRecommendation($priority, $commission),
                    'calculated_at' => now(),
                ];
            } catch (\Exception $e) {
                LogService::error('Finans → Takım önceliklendirme hatası', [
                    'islem_id' => $islem->id,
                    'error' => $e->getMessage(),
                ], $e);

                return [
                    'success' => false,
                    'priority' => 'normal',
                    'message' => 'Önceliklendirme yapılamadı',
                ];
            }
        });
    }

    /**
     * Takım → CRM: Görev tamamlanma oranına göre müşteri skorlama
     *
     * @param Kisi $kisi
     * @return array
     */
    public function scoreCustomerByTaskCompletion(Kisi $kisi): array
    {
        $cacheKey = "cross_module:takim_to_crm:kisi:{$kisi->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($kisi) {
            try {
                $tasks = Gorev::where('kisi_id', $kisi->id)->get();
                $totalTasks = $tasks->count();
                $completedTasks = $tasks->where('gorev_durumu', 'tamamlandi')->count();

                $completionRate = $totalTasks > 0
                    ? round(($completedTasks / $totalTasks) * 100, 2)
                    : 0;

                $score = $this->calculateCustomerScore($completionRate, $totalTasks);

                return [
                    'success' => true,
                    'kisi_id' => $kisi->id,
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'completion_rate' => $completionRate,
                    'customer_score' => $score,
                    'recommendation' => $this->getCustomerRecommendation($score, $completionRate),
                    'calculated_at' => now(),
                ];
            } catch (\Exception $e) {
                LogService::error('Takım → CRM skorlama hatası', [
                    'kisi_id' => $kisi->id,
                    'error' => $e->getMessage(),
                ], $e);

                return [
                    'success' => false,
                    'customer_score' => 0,
                    'message' => 'Skorlama yapılamadı',
                ];
            }
        });
    }

    /**
     * Tüm modüllerden veri toplayarak unified intelligence oluştur
     *
     * @param int $kisiId
     * @return array
     */
    public function getUnifiedIntelligence(int $kisiId): array
    {
        $cacheKey = "cross_module:unified:kisi:{$kisiId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($kisiId) {
            try {
                $kisi = Kisi::findOrFail($kisiId);

                $actionScore = $this->actionScore->calculateActionScore($kisi);
                $listingSuggestions = $this->suggestListingsForCustomer($kisi);
                $taskScore = $this->scoreCustomerByTaskCompletion($kisi);

                return [
                    'success' => true,
                    'kisi_id' => $kisiId,
                    'action_score' => $actionScore,
                    'listing_suggestions' => $listingSuggestions,
                    'task_score' => $taskScore,
                    'unified_score' => $this->calculateUnifiedScore($actionScore, $taskScore),
                    'recommendations' => $this->generateUnifiedRecommendations($actionScore, $listingSuggestions, $taskScore),
                    'calculated_at' => now(),
                ];
            } catch (\Exception $e) {
                LogService::error('Unified intelligence hatası', [
                    'kisi_id' => $kisiId,
                    'error' => $e->getMessage(),
                ], $e);

                return [
                    'success' => false,
                    'message' => 'Intelligence oluşturulamadı',
                ];
            }
        });
    }

    /**
     * Cache temizleme
     */
    public function clearCache(?int $kisiId = null, ?int $ilanId = null): void
    {
        if ($kisiId) {
            Cache::forget("cross_module:crm_to_ilan:kisi:{$kisiId}");
            Cache::forget("cross_module:takim_to_crm:kisi:{$kisiId}");
            Cache::forget("cross_module:unified:kisi:{$kisiId}");
        }

        if ($ilanId) {
            Cache::forget("cross_module:ilan_to_finans:ilan:{$ilanId}:*");
        }
    }

    /**
     * Tutar bazlı öncelik belirleme
     */
    private function determinePriorityByAmount(float $amount): string
    {
        if ($amount >= 100000) {
            return 'kritik';
        } elseif ($amount >= 50000) {
            return 'yuksek';
        } elseif ($amount >= 20000) {
            return 'normal';
        }

        return 'dusuk';
    }

    /**
     * Öncelik önerisi
     */
    private function getPriorityRecommendation(string $priority, float $amount): string
    {
        return match ($priority) {
            'kritik' => "₺" . number_format($amount, 0) . " komisyon beklentisi - Acil takip gerekli",
            'yuksek' => "₺" . number_format($amount, 0) . " komisyon beklentisi - Öncelikli takip",
            'normal' => "₺" . number_format($amount, 0) . " komisyon beklentisi - Normal takip",
            default => "Düşük komisyon beklentisi",
        };
    }

    /**
     * Müşteri skoru hesaplama
     */
    private function calculateCustomerScore(float $completionRate, int $totalTasks): float
    {
        $baseScore = $completionRate;
        $taskBonus = min($totalTasks * 2, 20);

        return min($baseScore + $taskBonus, 100);
    }

    /**
     * Müşteri önerisi
     */
    private function getCustomerRecommendation(float $score, float $completionRate): string
    {
        if ($score >= 80) {
            return "Mükemmel müşteri - %{$completionRate} görev tamamlama oranı";
        } elseif ($score >= 60) {
            return "İyi müşteri - %{$completionRate} görev tamamlama oranı";
        } elseif ($score >= 40) {
            return "Orta müşteri - %{$completionRate} görev tamamlama oranı - Takip gerekli";
        }

        return "Düşük skor - %{$completionRate} görev tamamlama oranı - Acil müdahale gerekli";
    }

    /**
     * Unified score hesaplama
     */
    private function calculateUnifiedScore(array $actionScore, array $taskScore): float
    {
        $action = (float) ($actionScore['action_score'] ?? 0);
        $task = (float) ($taskScore['customer_score'] ?? 0);

        return round(($action * 0.7) + ($task * 0.3), 2);
    }

    /**
     * Unified öneriler oluşturma
     */
    private function generateUnifiedRecommendations(array $actionScore, array $listingSuggestions, array $taskScore): array
    {
        $recommendations = [];

        if (($actionScore['action_score'] ?? 0) >= 80) {
            $recommendations[] = "Yüksek Action Score - Acil satış fırsatı";
        }

        if (($listingSuggestions['total_matches'] ?? 0) > 0) {
            $recommendations[] = count($listingSuggestions['suggestions'] ?? []) . " ilan önerisi mevcut";
        }

        if (($taskScore['completion_rate'] ?? 0) < 50) {
            $recommendations[] = "Düşük görev tamamlama oranı - Müşteri takibi gerekli";
        }

        return $recommendations;
    }
}

