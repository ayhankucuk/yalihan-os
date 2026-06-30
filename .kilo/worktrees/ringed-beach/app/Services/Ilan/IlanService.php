<?php

namespace App\Services\Ilan;

use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\IlanPriceHistory;
use App\Models\Kisi;
use App\Models\User;
use App\Models\Il;
use App\Models\Etiket;
use App\Models\Ulke;
use App\Models\Site;
use App\Models\YayinTipiSablonu;
use App\Models\OzellikKategori;
use App\Enums\IlanDurumu;
use App\Services\Cache\CacheHelper;
use App\Services\IlanReferansService;
use App\Services\Logging\LogService;
use App\Services\Utility\NumberToTextConverter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Ilan Service
 *
 * Context7 Standardı: C7-ILAN-SERVICE-2025-12-23
 *
 * Business logic for Ilan CRUD operations
 * Separated from IlanController for better maintainability
 *
 * @package App\Services\Ilan
 */
class IlanService
{
    /**
     * Cache TTL (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Cache key prefix
     */
    private const CACHE_PREFIX = 'ilan';

    /**
     * Get listings with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getListings(array $filters = [], int $perPage = 20)
    {
        $cacheKey = self::CACHE_PREFIX . ':list:' . md5(json_encode($filters) . $perPage);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters, $perPage) {
            $query = Ilan::with([
                'ilanSahibi:id,ad,soyad',
                'userDanisman:id,name',
                'kategori:id,name',
                'il:id,il_adi',
                'ilce:id,ilce_adi',
            ]);

            // Apply filters
            if (isset($filters['yayin_durumu'])) {
                $query->where('yayin_durumu', $filters['yayin_durumu']);
            }

            if (isset($filters['kategori_id'])) {
                $query->where('kategori_id', $filters['kategori_id']);
            }

            if (isset($filters['il_id'])) {
                $query->where('il_id', $filters['il_id']);
            }

            if (isset($filters['ilce_id'])) {
                $query->where('ilce_id', $filters['ilce_id']);
            }

            if (isset($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('baslik', 'like', "%{$search}%")
                        ->orWhere('aciklama', 'like', "%{$search}%");
                });
            }

            // Sort
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder); // context7-ignore

            return $query->paginate($perPage);
        });
    }

    /**
     * ✅ Thin Controller: Get listings with admin-specific stats and filters.
     *
     * @param array $filters
     * @return array
     */
    public function getAdminListingsWithStats(array $filters): array
    {
        $query = Ilan::with(['kategori', 'il', 'danisman'])->latest();

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('baslik', 'like', "%{$search}%")
                    ->orWhere('ilan_no', 'like', "%{$search}%")
                    ->orWhere('referans_no', 'like', "%{$search}%");
            });
        }

        // Yayin durumu filter
        if (!empty($filters['yayin_durumu'])) {
            $durum = \App\Enums\IlanDurumu::normalize($filters['yayin_durumu']);
            if ($durum) {
                $query->where('yayin_durumu', $durum->value);
            }
        }

        // Tab mapping — Context7 standardized UI contract
        $tabMapping = [
            'active'  => 'yayinda', // context7-ignore
            'passive' => 'pasif', // context7-ignore
            'drafts'  => 'taslak', // context7-ignore
            'expired' => 'arsiv', // context7-ignore
            'office'  => 'beklemede', // context7-ignore
        ];

        $activeTab = $filters['tab'] ?? '';
        if ($activeTab && isset($tabMapping[$activeTab])) {
            $query->where('yayin_durumu', $tabMapping[$activeTab]);
        }

        $ilanlar = $query->paginate(20);

        // Tab counts — bypass model global scopes for aggregate queries
        $statusCounts = Ilan::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->selectRaw("yayin_durumu, count(*) as cnt")
            ->groupBy('yayin_durumu')
            ->pluck('cnt', 'yayin_durumu');

        $tabCounts = [
            'active'  => $statusCounts->get('yayinda', 0), // context7-ignore
            'passive' => $statusCounts->get('pasif', 0), // context7-ignore
            'drafts'  => $statusCounts->get('taslak', 0), // context7-ignore
            'expired' => $statusCounts->get('arsiv', 0), // context7-ignore
            'office'  => $statusCounts->get('beklemede', 0), // context7-ignore
            'deleted' => Ilan::withoutGlobalScopes()->whereNotNull('deleted_at')->count(),
        ];

        $stats = [
            'total'     => Ilan::withoutGlobalScopes()->whereNull('deleted_at')->count(),
            'active'    => $statusCounts->get('yayinda', 0), // context7-ignore
            'this_month' => Ilan::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'pending'   => $statusCounts->get('beklemede', 0),
        ];

        $kategoriler = IlanKategori::active()->whereNull('parent_id')->orderBy('name')->get(); // context7-ignore

        return [
            'ilanlar' => $ilanlar,
            'tabCounts' => $tabCounts,
            'stats' => $stats,
            'kategoriler' => $kategoriler,
        ];
    }

    /**
     * ✅ Thin Controller: Store a new listing and perform quality check.
     *
     * @param array $data
     * @return array
     */
    public function storeListing(array $data): array
    {
        $ilanCrudService = app(IlanCrudService::class);
        $ilan = $ilanCrudService->store($data);

        $cortex = app(\App\Services\AI\YalihanCortex::class);
        $qualityCheck = $cortex->checkIlanQuality($ilan);

        $warning = null;
        if (!$qualityCheck['passed']) {
            $warning = $qualityCheck['message'];
            if (!empty($qualityCheck['missing_fields'])) {
                $missingLabels = array_map(fn($f) => $f['label'], $qualityCheck['missing_fields']);
                $warning .= ' Eksik alanlar: ' . implode(', ', $missingLabels) . '.';
            }
        }

        return [
            'success' => true,
            'ilan' => $ilan,
            'id' => $ilan->id,
            'message' => 'İlan başarıyla oluşturuldu.',
            'warning' => $warning,
            'quality_check' => $qualityCheck,
        ];
    }

    /**
     * ✅ Thin Controller: Update a listing and perform quality check.
     *
     * @param Ilan $ilan
     * @param array $data
     * @return array
     */
    public function updateListing(Ilan $ilan, array $data): array
    {
        $ilanCrudService = app(IlanCrudService::class);
        $updatedIlan = $ilanCrudService->update($ilan, $data);

        $cortex = app(\App\Services\AI\YalihanCortex::class);
        $qualityCheck = $cortex->checkIlanQuality($updatedIlan);

        $warning = null;
        if (!$qualityCheck['passed']) {
            $warning = $qualityCheck['message'];
            if (!empty($qualityCheck['missing_fields'])) {
                $missingLabels = array_map(fn($f) => $f['label'], $qualityCheck['missing_fields']);
                $warning .= ' Eksik alanlar: ' . implode(', ', $missingLabels) . '.';
            }
        }

        return [
            'success' => true,
            'ilan' => $updatedIlan,
            'id' => $updatedIlan->id,
            'message' => 'İlan başarıyla güncellendi.',
            'warning' => $warning,
            'quality_check' => $qualityCheck,
        ];
    }

    /**
     * ✅ Thin Controller: Get owner and contact person details.
     *
     * @param Ilan $ilan
     * @return array
     */
    public function getOwnerPrivateDetails(Ilan $ilan): array
    {
        $ilan->loadMissing(['ilanSahibi', 'ilgiliKisi']);

        return [
            'success' => true,
            'data' => [
                'ilan_id' => $ilan->id,
                'ilan_sahibi' => [
                    'ad' => optional($ilan->ilanSahibi)->ad,
                    'soyad' => optional($ilan->ilanSahibi)->soyad,
                    'telefon' => optional($ilan->ilanSahibi)->telefon,
                    'email' => optional($ilan->ilanSahibi)->email,
                ],
                'ilgili_kisi' => [
                    'ad' => optional($ilan->ilgiliKisi)->ad,
                    'soyad' => optional($ilan->ilgiliKisi)->soyad,
                    'telefon' => optional($ilan->ilgiliKisi)->telefon,
                    'email' => optional($ilan->ilgiliKisi)->email,
                ],
            ],
        ];
    }

    /**
     * ✅ Thin Controller: Update portal IDs for a listing.
     *
     * @param Ilan $ilan
     * @param array $portalIds
     * @return array
     */
    public function updateListingPortalIds(Ilan $ilan, array $portalIds): array
    {
        $ilanCrudService = app(IlanCrudService::class);
        $updatedColumns = $ilanCrudService->updatePortalIds($ilan, $portalIds);

        return [
            'success' => true,
            'updated_columns' => $updatedColumns,
        ];
    }

    /**
     * ✅ Thin Controller: Delete a listing.
     *
     * @param Ilan $ilan
     * @return bool
     */
    public function deleteListing(Ilan $ilan): bool
    {
        $ilanCrudService = app(IlanCrudService::class);
        return $ilanCrudService->destroy($ilan);
    }

    /**
     * Invalidate list cache
     *
     * @return void
     */
    public function invalidateListCache(): void
    {
        // Note: Pattern-based cache clearing
        Cache::flush();
    }

    /**
     * Get listing by ID
     *
     * @param int $id
     * @return Ilan
     */
    public function getListingById(int $id): Ilan
    {
        $cacheKey = self::CACHE_PREFIX . ':show:' . $id;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return Ilan::with([
                'ilanSahibi',
                'ilgiliKisi',
                'userDanisman',
                'kategori',
                'parentKategori',
                'il',
                'ilce',
                'mahalle',
                'fotograflar',
                'features',
            ])->findOrFail($id);
        });
    }

    /**
     * Get social media metadata for listing
     * Phase 8.0: Pazarlama ve Sosyal Medya Motoru
     *
     * @param Ilan $ilan
     * @return array
     */
    public function getSocialMediaMetadata(Ilan $ilan): array
    {
        $cacheKey = self::CACHE_PREFIX . ':social_meta:' . $ilan->id;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($ilan) {
            $cortex = app(\App\Services\AI\YalihanCortex::class);
            $roiEngine = app(\App\Services\CortexROIEngine::class);

            // ROI hesaplama
            $roiData = $this->calculateROI($ilan, $roiEngine);

            // Badge hesaplama
            $badge = $this->calculateBadge($ilan, $roiData);

            // Amortisman hesaplama
            $amortization = $this->calculateAmortization($ilan);

            return [
                'ilan_id' => $ilan->id,
                'baslik' => $ilan->baslik,
                'fiyat' => $ilan->fiyat,
                'para_birimi' => $ilan->para_birimi,
                'location' => [
                    'il' => $ilan->il?->il_adi,
                    'ilce' => $ilan->ilce?->ilce_adi,
                    'mahalle' => $ilan->mahalle?->mahalle_adi,
                ],
                'roi' => $roiData,
                'badge' => $badge,
                'amortization' => $amortization,
                'image_url' => $ilan->kapak_fotografi?->url ?? null,
                'generated_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Calculate badge for listing
     *
     * @param Ilan $ilan
     * @param array $roiData
     * @return array
     */
    private function calculateBadge(Ilan $ilan, array $roiData): array
    {
        $badges = [];

        // ROI badge
        if (($roiData['roi_percentage'] ?? 0) > 15) {
            $badges[] = [
                'tip' => 'high_roi',
                'label' => 'Yüksek ROI',
                'color' => 'green',
                'icon' => '📈',
            ];
        }

        // Price badge
        $avgPrice = $this->getAveragePriceForCategory($ilan->kategori_id);
        if ($avgPrice && $ilan->fiyat < $avgPrice * 0.9) {
            $badges[] = [
                'tip' => 'good_deal',
                'label' => 'İyi Fırsat',
                'color' => 'blue',
                'icon' => '💰',
            ];
        }

        // Location badge
        if ($ilan->il?->il_adi === 'İstanbul' || $ilan->il?->il_adi === 'Ankara') {
            $badges[] = [
                'tip' => 'premium_location',
                'label' => 'Premium Lokasyon',
                'color' => 'gold',
                'icon' => '⭐',
            ];
        }

        return [
            'badges' => $badges,
            'primary_badge' => $badges[0] ?? null,
        ];
    }

    /**
     * Calculate amortization for listing
     *
     * @param Ilan $ilan
     * @return array
     */
    private function calculateAmortization(Ilan $ilan): array
    {
        // Basit amortisman hesaplama
        // Gerçek hesaplama için finansal servis kullanılmalı
        $years = 20; // Varsayılan amortisman süresi
        $annualAmortization = $ilan->fiyat / $years;

        return [
            'years' => $years,
            'annual_amortization' => round($annualAmortization, 2),
            'monthly_amortization' => round($annualAmortization / 12, 2),
            'currency' => $ilan->para_birimi,
        ];
    }

    /**
     * Calculate ROI for listing
     *
     * @param Ilan $ilan
     * @param \App\Services\CortexROIEngine $roiEngine
     * @return array
     */
    private function calculateROI(Ilan $ilan, \App\Services\CortexROIEngine $roiEngine): array
    {
        // Kategoriye göre ROI hesaplama
        $kategoriSlug = $ilan->kategori?->slug ?? '';

        if (str_contains($kategoriSlug, 'arsa') || str_contains($kategoriSlug, 'arazi')) {
            return $roiEngine->calculateArsaROI($ilan);
        } elseif (str_contains($kategoriSlug, 'yazlik') || str_contains($kategoriSlug, 'turizm')) {
            return $roiEngine->calculateTurizmROI($ilan);
        }

        // Default ROI calculation
        return [
            'roi_percentage' => 0,
            'roi_category' => 'unknown',
            'calculation_date' => now()->toIso8601String(),
        ];
    }

    /**
     * Get average price for category
     *
     * @param int|null $kategoriId
     * @return float|null
     */
    private function getAveragePriceForCategory(?int $kategoriId): ?float
    {
        if (!$kategoriId) {
            return null;
        }

        $cacheKey = self::CACHE_PREFIX . ':avg_price:' . $kategoriId;

        return Cache::remember($cacheKey, self::CACHE_TTL * 24, function () use ($kategoriId) {
            return Ilan::where('alt_kategori_id', $kategoriId)
                ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
                ->avg('fiyat');
        });
    }

    /**
     * ✅ Thin Controller: Get data needed for listing wizard, including template validation.
     *
     * @param array $input
     * @return array
     */
    public function getWizardFormData(array $input = []): array
    {
        // SAB §4: Template mapping validation if junction_id provided
        if (!empty($input['junction_id'])) {
            $this->wizardGate->dogrulaWizardGirisi(
                (int) $input['junction_id'],
                !empty($input['kategori_id']) ? (int) $input['kategori_id'] : null,
            );
        }

        $anaKategoriler = IlanKategori::whereNull('parent_id')
            ->with('children')
            ->orderBy('display_order') // context7-ignore
            ->get();

        $danismanlar = User::whereHas('roles', function ($q) {
            $q->where('name', 'danisman');
        })
            ->where('aktiflik_durumu', 1)
            ->select(['id', 'name', 'email'])
            ->get();

        $iller = Il::orderBy('il_adi')->select(['id', 'il_adi'])->get();

        return [
            'anaKategoriler' => $anaKategoriler,
            'kategoriler'    => $anaKategoriler, // Blade step-1-category.blade.php uyumu
            'danismanlar'    => $danismanlar,
            'iller'          => $iller,
            'durumSecenekleri' => IlanDurumu::options(),
            'autoSave'       => $this->getAutoSaveData(),
        ];
    }

    /**
     * ✅ Thin Controller: Get all necessary data for editing a listing.
     *
     * @param Ilan $ilan
     * @return array
     */
    public function getEditFormData(Ilan $ilan): array
    {
        $ilan->loadMissing([
            'ilanSahibi',
            'kategori',
            'parentKategori',
            'il',
            'ilce',
            'mahalle',
            'fotograflar',
            'features',
        ]);

        $kategoriler = IlanKategori::whereNull('parent_id')
            ->with('children')
            ->orderBy('display_order') // context7-ignore
            ->get();

        $danismanlar = User::whereHas('roles', function ($q) {
            $q->where('name', 'danisman');
        })
            ->where('aktiflik_durumu', 1)
            ->select(['id', 'name', 'email'])
            ->get();

        $iller = Il::orderBy('il_adi')->select(['id', 'il_adi'])->get();

        $ulkeler = Ulke::select(['id', 'ulke_adi', 'ulke_kodu'])
            ->orderBy('ulke_adi') // context7-ignore
            ->get();

        $kisiler = Kisi::active()
            ->select(['id', 'ad', 'soyad', 'telefon', 'email'])
            ->orderBy('ad') // context7-ignore
            ->orderBy('soyad') // context7-ignore
            ->get();

        $sites = Site::active()
            ->select(['id', 'name'])
            ->orderBy('name') // context7-ignore
            ->get();

        $autoSaveData = $this->getAutoSaveData();

        return [
            'kategoriler' => $kategoriler,
            'danismanlar' => $danismanlar,
            'iller' => $iller,
            'durumSecenekleri' => $durumSecenekleri,
            'taslak' => false,
            'etiketler' => $etiketler,
            'ulkeler' => $ulkeler,
            'kisiler' => $kisiler,
            'sites' => $sites,
            'autoSaveData' => $autoSaveData,
            'ilanId' => null,
        ];
    }

    /**
     * Get auto-save data for the current user.
     *
     * @return array
     */
    private function getAutoSaveData(): array
    {
        $userId = Auth::id();
        $formId = 'ilan_create_' . $userId;
        $cacheKey = "context7_autosave_{$formId}";

        if (config('cache.default') === 'redis') {
            return Cache::get($cacheKey, []);
        }

        return session($cacheKey, []);
    }

    /**
     * ✅ Thin Controller: Get detailed analysis and insights for a listing.
     *
     * @param Ilan $ilan
     * @return array
     */
    public function getDetailedListingAnalysis(Ilan $ilan): array
    {
        // getListingById already handles caching and loading relations
        $ilan = $this->getListingById($ilan->id);

        // Market Analysis Integration
        $marketData = null;
        $priceAdvice = null;

        try {
            $analysisService = app(\App\Services\Market\MarketAnalysisService::class);
            $marketData = $analysisService->analyze($ilan); // Cached 12h

            $cortex = app(\App\Services\AI\YalihanCortex::class);
            $priceAdvice = $cortex->generatePriceAdvice($marketData);
        } catch (\Exception $e) {
            Log::warning('Market analysis failed for ilan ' . $ilan->id, ['error' => $e->getMessage()]);
        }

        // MIE v1 Alpha: Pricing Insight
        $pricingInsight = null;

        try {
            $pricingService = app(\App\Services\MarketIntelligence\PricingPositionService::class);
            $pricingInsight = $pricingService->analyze($ilan);
        } catch (\Exception $e) {
            Log::warning('MIE pricing insight failed for ilan ' . $ilan->id, ['error' => $e->getMessage()]);
        }

        // MIE v4: Location Intelligence
        $locationInsight = null;

        try {
            $locationService = app(\App\Services\MarketIntelligence\LocationIntelligenceService::class);
            $locationInsight = $locationService->analyze($ilan->lat, $ilan->lng);
        } catch (\Exception $e) {
            Log::warning('MIE location insight failed for ilan ' . $ilan->id, ['error' => $e->getMessage()]);
        }

        // MIE v3: AI Advisor Insight
        $advisorInsight = null;

        if ($pricingInsight && ! $pricingInsight->insufficient_data) {
            try {
                $advisorService = app(\App\Services\MarketIntelligence\AdvisorAssistantService::class);

                // V1.4/V1.5 sinyallerini zenginleştir
                $advisorPayload = $pricingInsight->toArray();

                $priorityService = app(\App\Services\MarketIntelligence\PortfolioPrioritizationService::class);
                $priority = $priorityService->evaluateListing($advisorPayload);
                $advisorPayload['priority_score'] = $priority['priority_score'] ?? 0;
                $advisorPayload['priority_label'] = $priority['priority_label'] ?? 'LOW';

                $workflowService = app(\App\Services\MarketIntelligence\WorkflowDecisionService::class);
                $decision = $workflowService->decide($advisorPayload);
                $advisorPayload['queue_type'] = $decision['queue_type'] ?? 'NO_ACTION';

                // days_on_market: ilan yayın tarihi varsa hesapla
                if ($ilan->yayin_tarihi) {
                    $advisorPayload['days_on_market'] = now()->diffInDays($ilan->yayin_tarihi);
                }

                // MIE v4: Location sinyalini advisor payload'a ekle
                if ($locationInsight && ! $locationInsight->isInsufficient()) {
                    $advisorPayload['location_signal_score'] = $locationInsight->location_signal_score;
                    $advisorPayload['location_confidence_label'] = $locationInsight->confidence_label;
                    $advisorPayload['location_demand_modifier'] = $locationInsight->demand_modifier;
                    $advisorPayload['location_top_groups'] = array_slice(
                        array_map(fn($g) => $g['label'], $locationInsight->top_nearby_groups),
                        0,
                        3,
                    );
                }

                $advisorInsight = $advisorService->generate($advisorPayload);
            } catch (\Exception $e) {
                Log::warning('MIE advisor insight failed for ilan ' . $ilan->id, ['error' => $e->getMessage()]);
            }
        }

        // Context7: Demand Matching (Reverse)
        $potentialBuyers = [];

        try {
            $demandMatcher = app(\App\Services\Matching\DemandMatchingEngine::class);
            $potentialBuyers = $demandMatcher->findPotentialBuyers($ilan, 70); // min score 70
        } catch (\Exception $e) {
            Log::warning('Demand matching failed for ilan ' . $ilan->id, ['error' => $e->getMessage()]);
        }

        // MIE v5: Action Mode — Composite Decision Engine
        $actionMode = null;
        $trustBreakdown = [];

        try {
            $actionEngine = app(\App\Services\MarketIntelligence\ActionModeEngine::class);
            $actionMode = $actionEngine->evaluate($locationInsight, $pricingInsight);
            $trustBreakdown = $actionMode->toTrustBreakdown();
        } catch (\Exception $e) {
            Log::warning('MIE action mode failed for ilan ' . $ilan->id, ['error' => $e->getMessage()]);
        }

        return [
            'ilan' => $ilan,
            'marketData' => $marketData,
            'priceAdvice' => $priceAdvice,
            'potentialBuyers' => $potentialBuyers,
            'pricingInsight' => $pricingInsight,
            'advisorInsight' => $advisorInsight,
            'locationInsight' => $locationInsight,
            'actionMode' => $actionMode,
            'trustBreakdown' => $trustBreakdown,
        ];
    }

    /**
     * ✅ Thin Controller: Get AI price analysis for listing parameters.
     *
     * @param array $validated
     * @return array
     */
    public function getAiPriceAnalysis(array $validated): array
    {
        $marketAnalysis = app(\App\Services\Revenue\MarketAnalysisService::class);

        $ilId = isset($validated['il_id']) ? (int) $validated['il_id'] : null;
        $ilceId = isset($validated['ilce_id']) ? (int) $validated['ilce_id'] : null;
        $kategoriId = (int) $validated['ana_kategori_id'];
        $currentPrice = (float) ($validated['fiyat'] ?? 0);

        if (!$ilId || !$ilceId) {
            return [
                'ai_price_recommendation' => $currentPrice,
                'market_confidence_score' => 20,
                'trend' => 'stable',
                'market_avg' => null,
                'analysis_date' => now()->toDateTimeString(),
                'veri_durumu' => 'yetersiz_lokasyon',
            ];
        }

        $marketData = $marketAnalysis->getMarketData($ilId, $ilceId, $kategoriId);
        $trend = $marketAnalysis->calculateTrend($marketData);
        $confidence = $currentPrice > 0
            ? $marketAnalysis->calculateConfidence($marketData, $currentPrice)
            : 30;
        $recommendedPrice = $marketAnalysis->calculateRecommendedPrice(
            $marketData,
            $currentPrice ?: (float) collect($marketData)->avg('avg_price')
        );

        return [
            'ai_price_recommendation' => $recommendedPrice,
            'market_confidence_score' => $confidence,
            'trend' => $trend,
            'market_avg' => collect($marketData)->avg('avg_price'),
            'analysis_date' => now()->toDateTimeString(),
        ];
    }

    /**
     * ✅ Thin Controller: Get type-specific configuration for wizard.
     *
     * @param int $yayinTipiId
     * @return array
     */
    public function getTypeConfiguration(int $yayinTipiId): array
    {
        $yayinTipi = YayinTipiSablonu::findOrFail($yayinTipiId);

        $config = [
            'template_id' => $yayinTipi->id,
            'template_name' => $yayinTipi->isim ?? 'Varsayılan',
            'feature_groups' => [],
            'required_fields' => ['baslik', 'ana_kategori_id', 'fiyat'],
            'optional_fields' => [],
            'hidden_fields' => [],
            'poi_config' => [
                'radius' => 1000,
                'categories' => ['all'],
                'auto_fetch' => true,
            ],
            'confidence_score' => 100,
            'ai_model_version' => 'v1.0',
        ];

        // Load feature groups for this yayin tipi
        $featureGroups = OzellikKategori::with(['ozellikler' => function ($q) {
            $q->active()->orderBy('display_order'); // Context7: Standardized scope // context7-ignore
        }])
            ->active() // context7-ignore
            ->orderBy('display_order') // context7-ignore
            ->get()
            ->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'slug' => $group->slug,
                    'features' => $group->ozellikler->map(function ($feature) {
                        return [
                            'id' => $feature->id,
                            'name' => $feature->name,
                            'slug' => $feature->slug,
                            'icon' => $feature->icon,
                        ];
                    }),
                ];
            });

        $config['feature_groups'] = $featureGroups;

        return $config;
    }
}

