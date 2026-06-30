<?php

namespace App\Services\AI;

use App\Services\AI\DealRadarService;
use App\Services\AI\SellerStrategyService;
use App\Services\AI\PortfolioDoctorService;
use App\Services\AI\BuyerMatchQueueService;
use App\Services\AI\OwnerDiscoveryService;
use App\Services\AI\OpportunityEngineService;
use App\Services\Market\MarketIntelligenceService;
use App\Services\AI\OllamaService;
use App\Models\Ilan;
use App\Enums\IlanDurumu;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * 🏢 SAB SEALED
 * AI Conversational Valuation Advisor — Full Orchestration Layer
 *
 * Parses natural language real estate queries, routes them to the appropriate
 * AI engine, normalises the result, and returns a unified report envelope.
 *
 * Supported intents (8):
 *   MARKET_VALUATION | MARKET_INTELLIGENCE | INVESTMENT_OPPORTUNITY |
 *   SELLER_PRICING   | LISTING_DIAGNOSTIC  | OWNER_ACQUISITION      |
 *   BUYER_MATCH      | PORTFOLIO_HEALTH    | UNKNOWN (fallback)
 *
 * Context7 compliant — no forbidden field names used.
 * CQRS rule: write-model mutations are forbidden here.
 */
class ConversationalAdvisorService
{
    public function __construct(
        private MarketValuationService         $valuationService,
        private MarketIntelligenceService      $intelligenceService,
        private DealRadarService               $dealRadarService,
        private SellerStrategyService          $sellerStrategyService,
        private PortfolioDoctorService         $portfolioDoctorService,
        private BuyerMatchQueueService         $buyerMatchQueueService,
        private OwnerDiscoveryService          $ownerDiscoveryService,
        private OpportunityEngineService       $opportunityEngineService,
        private PricingIntelligenceSyncService $pricingSyncService,
        private OllamaService                  $ollamaService,
    ) {}

    // ────────────────────────────────────────────────────────────
    // PUBLIC ENTRY POINT
    // ────────────────────────────────────────────────────────────

    /**
     * Process a natural language query and return a structured report.
     *
     * @param  string  $query
     * @param  array   $context  Optional extra context (e.g. listing_id for LISTING_DIAGNOSTIC)
     * @return array
     */
    public function processQuery(string $query, array $context = [], array $history = []): array
    {
        try {
            $startTime = microtime(true);

            // Önceki konuşmadan listing_id bağlamını taşı
            if (empty($context['listing_id']) && !empty($history)) {
                foreach (array_reverse($history) as $turn) {
                    if (!empty($turn['listing_id'])) {
                        $context['listing_id'] = $turn['listing_id'];
                        break;
                    }
                }
            }

            // B) Entity extraction + entity carryover (önceki turdan eksik entity'leri taşı)
            $entities = $this->extractEntities($query);
            $entities = $this->mergeEntitiesFromHistory($entities, $history);

            // D) Kural tabanlı önce (ücretsiz, <1ms) — sadece UNKNOWN'da LLM devreye girer
            $intent = $this->parseIntent($query);
            if ($intent === 'UNKNOWN') {
                $intent = $this->parseIntentWithLLM($query) ?? 'UNKNOWN';
            }

            $payload = [
                'query'    => $query,
                'intent'   => $intent,
                'entities' => $entities,
                'context'  => $context,
            ];

            $result = $this->routeIntent($payload);

            // Record Telemetry
            $executionTimeMs = (int)((microtime(true) - $startTime) * 1000);
            $this->recordTelemetry($query, $intent, $entities, $result, $executionTimeMs);

            // Record Valuation Signal Loop
            if (in_array($intent, ['MARKET_VALUATION', 'SELLER_PRICING']) && !empty($result['payload'])) {
                $this->recordValuationSignal($entities, $result['payload'], $intent);
                $this->pricingSyncService->recordPricingSignal($result['payload']);
            }

            return [
                'is_success'       => true,
                'intent_detected'  => $intent,
                'entities_parsed'  => $entities,
                'advisor_response' => $result['message'] ?? 'Analiz tamamlandı.',
                'data_payload'     => $result['payload'] ?? [],
                'source_engines'   => $result['source_engines'] ?? [],
            ];

        } catch (\Throwable $e) {
            Log::error('[ConversationalAdvisor] processQuery failed', [
                'hata_mesaji' => $e->getMessage(),
                'query'       => $query,
            ]);

            $this->recordFailure($query, $e->getMessage());

            return [
                'is_success'       => false,
                'intent_detected'  => 'UNKNOWN',
                'entities_parsed'  => [],
                'advisor_response' => 'Danışman motoru şu anda bu sorguyu işleyemiyor. Lütfen tekrar deneyin.',
                'data_payload'     => [],
                'source_engines'   => [],
            ];
        }
    }

    // ────────────────────────────────────────────────────────────
    // INTENT PARSING
    // ────────────────────────────────────────────────────────────

    public function parseIntent(string $query): string
    {
        $q = mb_strtolower($query);

        // SELLER_PRICING — önce kontrol (satış + fiyat karışmasın)
        if ($this->containsAny($q, [
            'kaçtan satmalıyım', 'satış fiyatı', 'ideal fiyat', 'hızlı satmak',
            'evimi sat', 'ne kadara satayım', 'ne kadar istemeli', 'fiyat koy',
            'fiyatlandır', 'satmayı düşünüyorum', 'satmak istiyorum',
        ])) {
            return 'SELLER_PRICING';
        }

        // MARKET_VALUATION
        if ($this->containsAny($q, [
            'fiyat', 'kaç para', 'kaç eder', 'değer', 'eder', 'm2 fiyat',
            'metrekare fiyat', 'ne kadar tutar', 'değerleme', 'piyasa fiyatı',
            'ortalama fiyat', 'kaç tl', 'tahmini değer',
        ])) {
            return 'MARKET_VALUATION';
        }

        // INVESTMENT_OPPORTUNITY
        if ($this->containsAny($q, [
            'fırsat', 'yatırım', 'alınır mı', 'iyi yatırım', 'karlı mı',
            'değer kazanır', 'girmeli miyim', 'mantıklı mı', 'al mı sat mı',
            'getiri', 'roi', 'kira getirisi', 'iyi bir dönem mi', 'şu anda almak',
        ])) {
            return 'INVESTMENT_OPPORTUNITY';
        }

        // LISTING_DIAGNOSTIC
        if ($this->containsAny($q, [
            'neden satılmıyor', 'ilan zayıf', 'overpriced', 'ilan performansı',
            'ilan neden', 'satılmıyor', 'neden ilgi görmüyor', 'görüntülenme az',
        ])) {
            return 'LISTING_DIAGNOSTIC';
        }

        // OWNER_ACQUISITION
        if ($this->containsAny($q, [
            'sahip', 'portföy sahibi', 'hangi sahipler', 'acquisition',
            'portföy al', 'sahip hedef', 'mülk sahibi',
        ])) {
            return 'OWNER_ACQUISITION';
        }

        // BUYER_MATCH
        if ($this->containsAny($q, [
            'alıcı var mı', 'uygun buyer', 'kime satılır', 'alıcı bul',
            'alıcı eşleşme', 'potansiyel alıcı', 'kim alır',
        ])) {
            return 'BUYER_MATCH';
        }

        // PORTFOLIO_HEALTH
        if ($this->containsAny($q, [
            'portföy performansı', 'portföy analizi', 'portföy kalitesi',
            'portföy sağlığı', 'portföy sağlık', 'sağlıklı portföy', 'portföy durumu',
        ])) {
            return 'PORTFOLIO_HEALTH';
        }

        // MARKET_INTELLIGENCE
        if ($this->containsAny($q, [
            'piyasa', 'trend', 'satış hızı', 'talep', 'artıyor mu',
            'düşüyor mu', 'bölge analizi', 'bölgede ne oluyor',
            'bölge raporu', 'piyasa raporu', 'konut fiyatları',
        ])) {
            return 'MARKET_INTELLIGENCE';
        }

        // C) PROPERTY_SEARCH — ilan arama isteği
        if ($this->containsAny($q, [
            'var mı', 'göster', 'bul', 'ara', 'istiyorum', 'arıyorum',
            'bakıyorum', 'arayışındayım', 'ilanları', 'listele', 'öner',
            'kiralık', 'satılık', 'kaç ilan', 'ne buldun',
        ])) {
            return 'PROPERTY_SEARCH';
        }

        return 'UNKNOWN';
    }

    // ────────────────────────────────────────────────────────────
    // ENTITY EXTRACTION
    // ────────────────────────────────────────────────────────────

    public function extractEntities(string $query): array
    {
        $entities = [];
        $q = mb_strtolower($query);

        // Location: İlçe — DB'den cache'li lookup (hardcoded yerine)
        $ilceler = Cache::remember('ai_ilce_list', 3600, function () {
            return DB::table('ilceler')
                ->orderByDesc(DB::raw('LENGTH(ilce_adi)')) // uzun isimler önce (Göltürkbükü > Türkbükü)
                ->pluck('ilce_adi')
                ->map(fn ($n) => mb_strtolower($n))
                ->toArray();
        });
        foreach ($ilceler as $ilce) {
            if (mb_strlen($ilce) >= 3 && str_contains($q, $ilce)) {
                $entities['location_ilce'] = mb_convert_case($ilce, MB_CASE_TITLE, 'UTF-8');
                break;
            }
        }

        // Location: Mahalle — DB'den cache'li lookup
        $mahalleler = Cache::remember('ai_mahalle_list', 3600, function () {
            return DB::table('mahalleler')
                ->orderByDesc(DB::raw('LENGTH(mahalle_adi)'))
                ->pluck('mahalle_adi')
                ->map(fn ($n) => mb_strtolower($n))
                ->toArray();
        });
        foreach ($mahalleler as $mah) {
            if (mb_strlen($mah) >= 4 && str_contains($q, $mah)) {
                $entities['location_mahalle'] = mb_convert_case($mah, MB_CASE_TITLE, 'UTF-8');
                if (!isset($entities['location_ilce'])) {
                    $entities['location_ilce'] = 'Bodrum';
                }
                break;
            }
        }

        // Asset type
        foreach (['tarla', 'arsa', 'daire', 'villa', 'konut', 'bağ', 'bahçe', 'yazlık'] as $type) {
            if (str_contains($q, $type)) {
                $entities['asset_type'] = $type;
                break;
            }
        }

        // Room count (e.g. 2+1, 3+1)
        if (preg_match('/(\d)\+(\d)/', $query, $m)) {
            $entities['room_count'] = $m[0];
        }

        // Area — dönüm
        if (preg_match('/(\d+[\.,]?\d*)\s*dönüm/i', $query, $m)) {
            $donum = (float) str_replace(',', '.', $m[1]);
            $entities['area_donum'] = $donum;
            $entities['m2_brut']    = (int)($donum * 1000);
        }

        // Area — m2 / metrekare (only if dönüm not found)
        if (!isset($entities['m2_brut'])) {
            if (preg_match('/(\d+)\s*(m2|metrekare|m²)/i', $query, $m)) {
                $entities['m2_brut'] = (int)$m[1];
            }
        }

        // C) Bütçe / fiyat üst sınırı — "10M", "5 milyon", "10 milyon TL altı"
        if (preg_match('/(\d+[\.,]?\d*)\s*(milyon|m)\b/iu', $query, $m)) {
            $entities['budget_max'] = (int)((float) str_replace(',', '.', $m[1]) * 1_000_000);
        } elseif (preg_match('/(\d{4,})\s*(tl|₺)/iu', $query, $m)) {
            $entities['budget_max'] = (int) str_replace(['.', ','], '', $m[1]);
        }

        // C) İşlem tipi — kiralık / satılık
        if ($this->containsAny($q, ['kiralık', 'kiralama', 'kira', 'kiraya'])) {
            $entities['islem_tipi'] = 'kiralik';
        } elseif ($this->containsAny($q, ['satılık', 'satış', 'satmak'])) {
            $entities['islem_tipi'] = 'satilik';
        }

        return $entities;
    }

    // ────────────────────────────────────────────────────────────
    // CENTRAL ROUTER
    // ────────────────────────────────────────────────────────────

    public function routeIntent(array $intentPayload): array
    {
        $intent   = $intentPayload['intent'];
        $entities = $intentPayload['entities'];
        $context  = $intentPayload['context'] ?? [];

        return match ($intent) {
            'MARKET_VALUATION'       => $this->handleValuation($entities),
            'MARKET_INTELLIGENCE'    => $this->handleMarketIntelligence($entities),
            'INVESTMENT_OPPORTUNITY' => $this->handleInvestmentOpportunity($entities),
            'SELLER_PRICING'         => $this->handleSellerPricing($context),
            'LISTING_DIAGNOSTIC'     => $this->handleListingDiagnostic($context),
            'OWNER_ACQUISITION'      => $this->handleOwnerAcquisition(),
            'BUYER_MATCH'            => $this->handleBuyerMatch($context),
            'PORTFOLIO_HEALTH'       => $this->handlePortfolioHealth(),
            'PROPERTY_SEARCH'        => $this->handlePropertySearch($entities),  // A) Yeni
            default                  => $this->handleUnknown($entities),          // geliştirildi
        };
    }

    // ────────────────────────────────────────────────────────────
    // INTENT HANDLERS
    // ────────────────────────────────────────────────────────────

    private function handleValuation(array $entities): array
    {
        $filters = array_filter([
            'ilce'    => $entities['location_ilce'] ?? null,
            'mahalle' => $entities['location_mahalle'] ?? null,
            'm2'      => $entities['m2_brut'] ?? null,
        ]);

        try {
            $report = $this->valuationService->evaluateQuery($filters);
        } catch (\Throwable $e) {
            Log::warning('[ConversationalAdvisor] valuationService failed', ['hata_mesaji' => $e->getMessage()]);
            return $this->engineUnavailable('MarketValuationService');
        }

        if (!($report['is_success'] ?? false)) {
            return [
                'message'        => 'Yeterli karşılaştırmalı veri bulunamadığı için değerleme yapılamadı.',
                'payload'        => [],
                'source_engines' => ['market_valuation'],
            ];
        }

        $data  = $report['data'] ?? [];
        $price = number_format($data['estimated_value'] ?? 0, 0, ',', '.');
        $low   = number_format($data['price_range_low'] ?? 0, 0, ',', '.');
        $high  = number_format($data['price_range_high'] ?? 0, 0, ',', '.');

        return [
            'message'        => "Tahmini piyasa değeri: **{$price} TL** (Beklenen aralık: {$low} – {$high} TL). Güven skoru: %{$data['confidence_score']}",
            'payload'        => $data,
            'source_engines' => ['market_valuation'],
        ];
    }

    private function handleMarketIntelligence(array $entities): array
    {
        $locationData = array_filter([
            'ilce'    => $entities['location_ilce'] ?? null,
            'mahalle' => $entities['location_mahalle'] ?? null,
        ]);

        try {
            $result = $this->intelligenceService->calculateMarketValue($locationData);
        } catch (\Throwable $e) {
            Log::warning('[ConversationalAdvisor] intelligenceService failed', ['hata_mesaji' => $e->getMessage()]);
            return $this->engineUnavailable('MarketIntelligenceService');
        }

        return [
            'message'        => 'Bölgeye ait piyasa analizi tamamlandı. Talep ve fiyat trendi raporu hazır.',
            'payload'        => $result,
            'source_engines' => ['market_intelligence'],
        ];
    }

    private function handleInvestmentOpportunity(array $entities): array
    {
        $filters = array_filter([
            'location_ilce'    => $entities['location_ilce'] ?? null,
            'location_mahalle' => $entities['location_mahalle'] ?? null,
        ]);

        try {
            $listings = $this->dealRadarService->getRadarListings($filters);
        } catch (\Throwable $e) {
            Log::warning('[ConversationalAdvisor] dealRadarService failed', ['hata_mesaji' => $e->getMessage()]);
            return $this->engineUnavailable('DealRadarService');
        }

        $count = count($listings['listings'] ?? []);
        $top   = array_slice($listings['listings'] ?? [], 0, 3);

        // Her deal kartına tıklanabilir URL ekle
        $top = array_map(function ($deal) {
            try {
                $deal['url']            = route('ilanlar.show', $deal['listing_id']);
                $deal['fiyat_formatted'] = number_format($deal['price'] ?? 0, 0, ',', '.');
            } catch (\Throwable $e) {
                $deal['url'] = null;
            }
            return $deal;
        }, $top);

        $lokasyon = $filters['location_ilce'] ?? 'seçilen bölge';
        return [
            'message'        => "{$lokasyon} bölgesinde **{$count}** fırsat tespit edildi. En yüksek deal-score'lu ilanlar aşağıda.",
            'payload'        => ['total_opportunities' => $count, 'top_deals' => $top],
            'source_engines' => ['deal_radar'],
        ];
    }

    private function handleSellerPricing(array $context): array
    {
        $listingId = $context['listing_id'] ?? null;

        if (!$listingId) {
            return [
                'message'        => 'Satış fiyatı önerisi için ilan ID gereklidir. Lütfen hangi ilanı kastettiğinizi belirtin.',
                'payload'        => [],
                'source_engines' => ['seller_strategy'],
            ];
        }

        try {
            $strategy = $this->sellerStrategyService->generateSellerStrategy((int)$listingId);
        } catch (\Throwable $e) {
            Log::warning('[ConversationalAdvisor] sellerStrategyService failed', ['hata_mesaji' => $e->getMessage()]);
            return $this->engineUnavailable('SellerStrategyService');
        }

        return [
            'message'        => $strategy['advisor_recommendation'] ?? 'Satış stratejisi üretildi.',
            'payload'        => $strategy,
            'source_engines' => ['seller_strategy'],
        ];
    }

    private function handleListingDiagnostic(array $context): array
    {
        $filters = [];
        if (!empty($context['listing_id'])) {
            $filters['listing_id'] = $context['listing_id'];
        }

        try {
            $diagnosis = $this->portfolioDoctorService->analyzePortfolio($filters);
        } catch (\Throwable $e) {
            Log::warning('[ConversationalAdvisor] portfolioDoctorService failed', ['hata_mesaji' => $e->getMessage()]);
            return $this->engineUnavailable('PortfolioDoctorService');
        }

        $total = $diagnosis['summary']['total_listings_analyzed'] ?? 0;

        return [
            'message'        => "İlan diagnostic tamamlandı. {$total} ilan analiz edildi.",
            'payload'        => $diagnosis,
            'source_engines' => ['portfolio_doctor'],
        ];
    }

    private function handleOwnerAcquisition(): array
    {
        try {
            $opportunities = $this->ownerDiscoveryService->generateOwnerOpportunityList();
            $list = is_object($opportunities) ? $opportunities->take(5)->toArray() : array_slice((array)$opportunities, 0, 5);
        } catch (\Throwable $e) {
            Log::warning('[ConversationalAdvisor] ownerDiscoveryService failed', ['hata_mesaji' => $e->getMessage()]);
            return $this->engineUnavailable('OwnerDiscoveryService');
        }

        return [
            'message'        => 'Portföy sahip analizi tamamlandı. En yüksek acquisition skoru olan sahipler listelendi.',
            'payload'        => ['top_owners' => $list],
            'source_engines' => ['owner_discovery'],
        ];
    }

    private function handleBuyerMatch(array $context): array
    {
        if (empty($context['listing_id'])) {
            return [
                'message'        => 'Alıcı eşleştirmesi için ilan ID gereklidir. Hangi ilanı sorgulamak istediğinizi belirtin.',
                'payload'        => [],
                'source_engines' => ['buyer_match'],
            ];
        }

        try {
            $ilan = \App\Models\Ilan::findOrFail((int)$context['listing_id']);
            $matches = $this->buyerMatchQueueService->getMatchesForQueue($ilan, 10);
        } catch (\Throwable $e) {
            Log::warning('[ConversationalAdvisor] buyerMatchQueueService failed', ['hata_mesaji' => $e->getMessage()]);
            return $this->engineUnavailable('BuyerMatchQueueService');
        }

        $count = count($matches['matches'] ?? []);

        try {
            $ilanUrl  = route('ilanlar.show', (int)$context['listing_id']);
            $ilanLink = "<a href=\"{$ilanUrl}\">#" . $context['listing_id'] . "</a>";
        } catch (\Throwable $e) {
            $ilanLink = '#' . ($context['listing_id'] ?? '?');
        }

        return [
            'message'        => "İlan {$ilanLink} için **{$count}** potansiyel alıcı eşleşmesi bulundu.",
            'payload'        => $matches,
            'source_engines' => ['buyer_match'],
        ];
    }

    private function handlePortfolioHealth(): array
    {
        try {
            $analysis = $this->portfolioDoctorService->analyzePortfolio([]);
        } catch (\Throwable $e) {
            Log::warning('[ConversationalAdvisor] portfolioDoctorService (health) failed', ['hata_mesaji' => $e->getMessage()]);
            return $this->engineUnavailable('PortfolioDoctorService');
        }

        $summary = $analysis['summary'] ?? [];

        return [
            'message'        => "Portföy sağlık analizi tamamlandı. {$summary['total_listings_analyzed']} ilan incelendi.",
            'payload'        => $analysis,
            'source_engines' => ['portfolio_doctor'],
        ];
    }

    private function handleUnknown(array $entities = []): array
    {
        // A) Entity varsa DB araması yap, boş sonuç yerine ilan öner
        if (!empty($entities['location_ilce']) || !empty($entities['asset_type'])) {
            $search = $this->handlePropertySearch($entities);
            if (!empty($search['payload']['listings'])) {
                $search['message'] = 'Sorunuzu tam anlayamadım, ama kriterlerinize uyan ilanları buldum:';
                return $search;
            }
        }

        return [
            'message'        => 'Sorunuzu tam olarak anlayamadım. Şunları deneyebilirsiniz: "Bodrum Bitez 1000m2 arsa kaç TL eder?", "Yalıkavak kiralık villa var mı?", "Bu bölgede yatırım fırsatı var mı?"',
            'payload'        => [],
            'source_engines' => [],
        ];
    }

    /**
     * A) PROPERTY_SEARCH handler — DB'den eşleşen ilanları çek.
     * Filtreler: ilçe, mahalle, asset_type, islem_tipi, budget_max, m2_brut
     */
    private function handlePropertySearch(array $entities): array
    {
        $query = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->with(['ilce:id,ilce_adi', 'anaKategori:id,name,slug']);

        // Lokasyon filtresi
        if (!empty($entities['location_ilce'])) {
            $query->whereHas('ilce', fn($q) =>
                $q->whereRaw('LOWER(ilce_adi) LIKE ?', ['%' . mb_strtolower($entities['location_ilce']) . '%'])
            );
        }
        if (!empty($entities['location_mahalle'])) {
            $query->whereHas('mahalle', fn($q) =>
                $q->whereRaw('LOWER(mahalle_adi) LIKE ?', ['%' . mb_strtolower($entities['location_mahalle']) . '%'])
            );
        }

        // Kategori filtresi (arsa, villa, daire…)
        if (!empty($entities['asset_type'])) {
            $query->whereHas('anaKategori', fn($q) =>
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($entities['asset_type']) . '%'])
            );
        }

        // Bütçe filtresi
        if (!empty($entities['budget_max'])) {
            $query->where('fiyat', '<=', $entities['budget_max']);
        }

        // Alan filtresi (±%40 tolerans)
        if (!empty($entities['m2_brut'])) {
            $margin = (int)($entities['m2_brut'] * 0.4);
            $query->whereBetween('alan', [
                max(1, $entities['m2_brut'] - $margin),
                $entities['m2_brut'] + $margin,
            ]);
        }

        $ilanlar = $query->orderByDesc('created_at')->take(5)->get();

        if ($ilanlar->isEmpty()) {
            return [
                'message'        => 'Arama kriterlerinize uygun aktif ilan bulunamadı. Filtreleri genişletmeyi deneyin.',
                'payload'        => ['listings' => [], 'total' => 0],
                'source_engines' => ['property_search'],
            ];
        }

        $listings = $ilanlar->map(function ($ilan) {
            $url = null;
            try { $url = route('ilanlar.show', $ilan->id); } catch (\Throwable) {}
            return [
                'listing_id'      => $ilan->id,
                'baslik'          => $ilan->baslik,
                'price'           => $ilan->fiyat ?? 0,
                'fiyat_formatted' => '₺ ' . number_format($ilan->fiyat ?? 0, 0, ',', '.'),
                'alan'            => $ilan->alan,
                'ilce'            => is_object($ilan->ilce) ? ($ilan->ilce->ilce_adi ?? null) : null,
                'kategori'        => $ilan->anaKategori?->name,
                'url'             => $url,
                // DealRadar ile uyumlu alan adları
                'title'           => $ilan->baslik,
                'deal_score'      => null,
            ];
        })->toArray();

        $lokasyon = $entities['location_mahalle'] ?? $entities['location_ilce'] ?? 'Bodrum bölgesi';
        $tip      = $entities['asset_type'] ?? 'gayrimenkul';
        $count    = count($listings);

        return [
            'message'        => "**{$lokasyon}** bölgesinde {$count} {$tip} ilanı bulundu.",
            'payload'        => ['listings' => $listings, 'total' => $count],
            'source_engines' => ['property_search'],
        ];
    }

    // ────────────────────────────────────────────────────────────
    // HELPERS
    // ────────────────────────────────────────────────────────────

    // ────────────────────────────────────────────────────────────
    // B) ENTITY CARRYOVER — önceki turdan eksik entity'leri taşı
    // ────────────────────────────────────────────────────────────

    /**
     * Mevcut sorguda bulunmayan entity'leri history'nin son turundaki entity'lerden tamamla.
     * Sadece şu keyleri taşır: location_ilce, location_mahalle, asset_type, islem_tipi
     */
    private function mergeEntitiesFromHistory(array $currentEntities, array $history): array
    {
        if (empty($history)) {
            return $currentEntities;
        }

        $carryoverKeys = ['location_ilce', 'location_mahalle', 'asset_type', 'islem_tipi'];

        foreach (array_reverse($history) as $turn) {
            $prevEntities = $turn['entities'] ?? [];
            if (empty($prevEntities)) {
                continue;
            }
            foreach ($carryoverKeys as $key) {
                if (empty($currentEntities[$key]) && !empty($prevEntities[$key])) {
                    $currentEntities[$key] = $prevEntities[$key];
                }
            }
            break; // Sadece en son turdan al
        }

        return $currentEntities;
    }

    // ────────────────────────────────────────────────────────────
    // D) OLLAMA LLM INTENT PARSING — rule-based fallback
    // ────────────────────────────────────────────────────────────

    /**
     * LLM intent parsing — sadece rule-based UNKNOWN döndürdüğünde çağrılır.
     *
     * Pipeline:
     *   1. Cache kontrolü (md5 hash, 30dk TTL) → hit ise direkt dön
     *   2. DeepSeek V3 (deepseek-chat) — birincil, ~0.002 TL/sorgu
     *   3. Ollama (opsiyonel fallback) — sunucuda varsa devreye girer
     *   4. null → UNKNOWN kalır
     */
    private function parseIntentWithLLM(string $query): ?string
    {
        $validIntents = [
            'MARKET_VALUATION', 'INVESTMENT_OPPORTUNITY', 'PROPERTY_SEARCH',
            'SELLER_PRICING', 'MARKET_INTELLIGENCE', 'LISTING_DIAGNOSTIC',
            'PORTFOLIO_HEALTH', 'OWNER_ACQUISITION', 'BUYER_MATCH',
        ];

        // 1. Cache — aynı sorgu tekrar gelirse API çağrısı yapma
        $cacheKey = 'ai_intent_' . md5(mb_strtolower(trim($query)));
        $cached   = Cache::get($cacheKey);
        if ($cached && in_array($cached, $validIntents, true)) {
            Log::debug('[ConversationalAdvisor] intent cache hit', ['intent' => $cached]);
            return $cached;
        }

        $systemPrompt = 'Sen bir Türk gayrimenkul AI asistanısın. Kullanıcı sorgusunu TAM OLARAK aşağıdaki intentlerden birine sınıflandır. Sadece intent adını yaz, başka hiçbir şey yazma.';

        $userPrompt = <<<PROMPT
İntentler:
- MARKET_VALUATION: Fiyat, değer, m2 fiyatı, tahmini değer, ne kadar tutar
- INVESTMENT_OPPORTUNITY: Yatırım, fırsat, karlılık, girmeli miyim, mantıklı mı, al mı sat mı
- PROPERTY_SEARCH: İlan ara, bul, göster, var mı, kiralık, satılık, istiyorum, arıyorum
- SELLER_PRICING: Ne kadara satayım, satış fiyatı, hızlı satmak, fiyat koy
- MARKET_INTELLIGENCE: Piyasa trendi, talep, artıyor mu, bölge analizi, piyasa raporu
- LISTING_DIAGNOSTIC: Neden satılmıyor, ilan performansı, görüntülenme az
- PORTFOLIO_HEALTH: Portföy sağlığı, genel portföy analizi, portföy durumu
- OWNER_ACQUISITION: Mülk sahibi bulma, portföy akizisyonu
- BUYER_MATCH: Alıcı eşleştirme, kime satılır, potansiyel alıcı

Sorgu: "{$query}"
PROMPT;

        $resolved = null;

        // 2. DeepSeek V3 — birincil LLM (ucuz, hızlı, güvenilir)
        $deepseekKey = config('services.deepseek.api_key');
        if ($deepseekKey) {
            try {
                $response = \Illuminate\Support\Facades\Http::withToken($deepseekKey)
                    ->baseUrl(config('services.deepseek.base_url', 'https://api.deepseek.com'))
                    ->timeout(8)
                    ->post('/chat/completions', [
                        'model'       => 'deepseek-chat',
                        'messages'    => [
                            ['role' => 'system',  'content' => $systemPrompt],
                            ['role' => 'user',    'content' => $userPrompt],
                        ],
                        'max_tokens'  => 15,
                        'temperature' => 0,
                    ]);

                if ($response->successful()) {
                    $text = strtoupper(trim(
                        $response->json('choices.0.message.content') ?? ''
                    ));
                    foreach ($validIntents as $intent) {
                        if (str_contains($text, $intent)) {
                            $resolved = $intent;
                            Log::debug('[ConversationalAdvisor] DeepSeek intent', ['intent' => $intent, 'query' => $query]);
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::debug('[ConversationalAdvisor] DeepSeek unavailable, trying Ollama', ['error' => $e->getMessage()]);
            }
        }

        // 3. Ollama — opsiyonel fallback (sunucuda yüklüyse çalışır)
        if (!$resolved) {
            try {
                $response = $this->ollamaService->generateCompletion(
                    $systemPrompt . "\n\n" . $userPrompt,
                    15
                );
                $text = strtoupper(trim($response['response'] ?? ''));
                foreach ($validIntents as $intent) {
                    if (str_contains($text, $intent)) {
                        $resolved = $intent;
                        Log::debug('[ConversationalAdvisor] Ollama intent fallback', ['intent' => $intent]);
                        break;
                    }
                }
            } catch (\Throwable $e) {
                Log::debug('[ConversationalAdvisor] Ollama also unavailable', ['error' => $e->getMessage()]);
            }
        }

        // 4. Cache'e yaz (30dk) — sonraki aynı sorgu ücretsiz
        if ($resolved) {
            Cache::put($cacheKey, $resolved, now()->addMinutes(30));
        }

        return $resolved; // null ise UNKNOWN kalır
    }

    private function containsAny(string $subject, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($subject, $needle)) {
                return true;
            }
        }
        return false;
    }

    private function engineUnavailable(string $engineName): array
    {
        return [
            'message'        => "{$engineName} şu anda yanıt veremiyor. Lütfen birkaç dakika içinde tekrar deneyin.",
            'payload'        => [],
            'source_engines' => [mb_strtolower(str_replace('Service', '', $engineName))],
        ];
    }

    // ────────────────────────────────────────────────────────────
    // TELEMETRY & MONITORING
    // ────────────────────────────────────────────────────────────

    private function recordTelemetry(string $query, string $intent, array $entities, array $result, int $executionTimeMs): void
    {
        try {
            DB::table('ai_query_telemetry')->insert([
                'query_text'        => $query,
                'intent_detected'   => $intent,
                'location_il'       => 'Muğla', // Default to Bodrum area
                'location_ilce'     => $entities['location_ilce'] ?? null,
                'location_mahalle'  => $entities['location_mahalle'] ?? null,
                'asset_type'        => $entities['asset_type'] ?? null,
                'area_m2'           => $entities['m2_brut'] ?? null,
                'confidence_score'  => $result['payload']['confidence_score'] ?? null,
                'engine_called'     => !empty($result['source_engines']) ? implode(',', $result['source_engines']) : null,
                'execution_time_ms' => $executionTimeMs,
                'metadata'          => json_encode(['entities' => $entities, 'is_success' => $result['is_success'] ?? true]),
                'created_at'        => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[ConversationalAdvisor] recordTelemetry failed', ['error' => $e->getMessage()]);
        }
    }

    private function recordValuationSignal(array $entities, array $payload, string $source): void
    {
        try {
            DB::table('valuation_signal_logs')->insert([
                'region_key'        => ($entities['location_ilce'] ?? 'Bodrum') . '_' . ($entities['location_mahalle'] ?? 'Genel'),
                'asset_type'        => $entities['asset_type'] ?? 'konut',
                'area_m2'           => $entities['m2_brut'] ?? 100,
                'estimated_value'   => $payload['estimated_value'] ?? 0,
                'confidence_score'  => $payload['confidence_score'] ?? 0,
                'liquidity_score'   => $payload['liquidity_score'] ?? null,
                'trend_percent'     => $payload['trend_percent'] ?? null,
                'source_engine'     => $source,
                'created_at'        => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[ConversationalAdvisor] recordValuationSignal failed', ['error' => $e->getMessage()]);
        }
    }

    private function recordFailure(string $query, string $reason): void
    {
        try {
            DB::table('ai_query_failures')->insert([
                'query_text'     => $query,
                'failure_reason' => $reason,
                'error_context'  => json_encode(['trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)]),
                'created_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[ConversationalAdvisor] recordFailure failed', ['error' => $e->getMessage()]);
        }
    }
}
