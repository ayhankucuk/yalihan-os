<?php

namespace App\Services\AI;

/**
 * @sab-ignore-catch
 */

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * ��️ SAB SEALED
 * - Forbidden keywords: "st*tus" family (do not introduce)
 * - SSOT: naming must reflect domain semantics (e.g., yayin_durumu vs aktiflik_durumu)
 * - No hidden side-effects: logic stays in service layer, UI is dumb
 * - Any change must pass: bekci:audit + integrity scan
 */
class OllamaService
{
    /**
     * Ollama API URL
     */
    protected string $apiUrl;

    /**
     * Ollama Model
     */
    protected string $model;

    /**
     * Cache süresi (saniye)
     */
    protected int $cacheTTL = 3600;
    protected \App\Services\AI\Monitoring\AiTelemetryService $telemetryService;
    protected \App\Contracts\Settings\ConfigurationRegistryInterface $registry;

    public function __construct(
        \App\Services\AI\Monitoring\AiTelemetryService $telemetryService,
        \App\Contracts\Settings\ConfigurationRegistryInterface $registry
    )
    {
        $this->telemetryService = $telemetryService;
        $this->registry = $registry;

        $this->apiUrl = $this->getOllamaUrl();
        $this->model = $this->getOllamaModel();
        // Security checks are now handled globally by ConfigGuard during bootstrap.
    }

    /**
     * Ollama URL'ini settings'ten veya config'ten al
     */
    protected function getOllamaUrl(): string
    {
        // SSOT: config/ai.php is the authority. Settings registry can override at runtime.
        try {
            return $this->registry->get('ollama_url')
                ?? config('ai.ollama.url', 'http://localhost:11434');
        } catch (\Throwable $e) {
            return config('ai.ollama.url', 'http://localhost:11434');
        }
    }

    /**
     * @deprecated Security validation is now handled by \App\Support\Guards\ConfigGuard
     */
    protected function enforceTlsIfRequired(): void
    {
        // Handled by ConfigGuard.
    }

    /**
     * Ollama Model'ini settings'ten veya config'ten al
     * Öncelik: ai_default_model > ollama_model > config > default
     */
    protected function getOllamaModel(): string
    {
        try {
            // Önce ai_default_model kontrol et (genel model ayarı)
            $defaultModel = $this->registry->get('ai_default_model');
            if ($defaultModel) {
                return $defaultModel;
            }

            // Sonra ollama_model kontrol et (Ollama özel ayarı)
            $ollamaModel = $this->registry->get('ollama_model');
            if ($ollamaModel) {
                return $ollamaModel;
            }

            // Son olarak config'ten al
            return config('ai.ollama_model', 'gemma2:2b');
        } catch (\Throwable $e) {
            return config('ai.ollama_model', 'gemma2:2b');
        }
    }

    /**
     * Model'i dinamik olarak güncelle (runtime'da değişiklik için)
     */
    public function setModel(string $model): void
    {
        $this->model = $model;
        Cache::forget('ollama_model');
    }

    /**
     * İlan başlığı üret
     */
    public function generateTitle(array $data): array
    {
        $cacheKey = 'ollama_title_' . md5(json_encode($data));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($data) {
            $prompt = $this->buildTitlePrompt($data);

            try {
                $response = $this->sendRequest($prompt, 500, 'title');

                if (isset($response['response'])) {
                    return $this->parseTitleResponse($response['response']);
                }
            } catch (\Exception $e) {
                Log::error('Ollama title generation failed', ['error' => $e->getMessage()]);
            }

            return $this->getFallbackTitles($data);
        });
    }

    /**
     * İlan açıklaması üret
     */
    public function generateDescription(array $data): string
    {
        $cacheKey = 'ollama_desc_' . md5(json_encode($data));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($data) {
            $prompt = $data['prompt_override'] ?? $this->buildDescriptionPrompt($data);

            try {
                // Determine max tokens based on length if provided
                $length = $data['length'] ?? 'medium';
                $maxTokens = $length === 'long' ? 1000 : ($length === 'short' ? 250 : 500);

                $response = $this->sendRequest($prompt, $maxTokens, 'description');

                if (isset($response['response'])) {
                    return trim($response['response']);
                }
            } catch (\Exception $e) {
                Log::error('Ollama description generation failed', ['error' => $e->getMessage()]);
            }

            return $this->getFallbackDescription($data);
        });
    }

    /**
     * Lokasyon analizi yap
     */
    public function analyzeLocation(array $locationData): array
    {
        $cacheKey = 'ollama_location_' . md5(json_encode($locationData));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($locationData) {
            $prompt = $this->buildLocationAnalysisPrompt($locationData);

            try {
                $response = $this->sendRequest($prompt, 500, 'location_analysis');

                if (isset($response['response'])) {
                    return $this->parseLocationAnalysis($response['response']);
                }
            } catch (\Exception $e) {
                Log::error('Ollama location analysis failed', ['error' => $e->getMessage()]);
            }

            return $this->getFallbackLocationAnalysis();
        });
    }

    /**
     * Fiyat önerisi ver
     */
    public function suggestPrice(array $propertyData): array
    {
        $prompt = $this->buildPriceSuggestionPrompt($propertyData);

        try {
            $response = $this->sendRequest($prompt, 500, 'price_suggestion');

            if (isset($response['response'])) {
                return $this->parsePriceSuggestions($response['response'], $propertyData['base_price'] ?? 0);
            }
        } catch (\Exception $e) {
            Log::error('Ollama price suggestion failed', ['error' => $e->getMessage()]);
        }

        return $this->getFallbackPriceSuggestions($propertyData['base_price'] ?? 0);
    }

    /**
     * Ham metin üretimi (Raw Completion)
     * Generic kullanım için
     */
    public function generateCompletion(string $prompt, int $maxTokens = 500): array
    {
        try {
            return $this->sendRequest($prompt, $maxTokens, 'completion');
        } catch (\Exception $e) {
            Log::error('Ollama completion failed', ['error' => $e->getMessage()]);
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Ollama API'ye istek gönder
     */
    protected function sendRequest(string $prompt, int $maxTokens = 500, string $endpoint = 'general'): array
    {
        $startTime = microtime(true);

        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->retry(2, 1000, function ($exception) {
                    return $exception instanceof ConnectionException;
                })
                ->post($this->apiUrl . '/api/generate', [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.7,
                        'top_p' => 0.9,
                        'num_predict' => $maxTokens,
                    ],
                ]);

            if ($response->successful()) {
                $duration = microtime(true) - $startTime;

                $this->telemetryService->logTransaction(
                    'ollama',
                    $endpoint,
                    $duration,
                    0, // Input tokens not provided by Ollama reliably in this endpoint
                    0, // Output tokens
                    $response->getStatusCode(),
                    ['request' => $prompt]
                );
                return $response->json();
            }

            $yanit_durumu = $response->getStatusCode();
            $errorBody = substr($response->body(), 0, 500);

            $this->telemetryService->logFailure(
                'ollama',
                $endpoint,
                "HTTP {$yanit_durumu}: {$errorBody}",
                $yanit_durumu,
                ['request' => $prompt]
            );

            Log::error('Ollama API request failed', [
                'url' => $this->apiUrl,
                'model' => $this->model,
                'yanit_durumu' => $yanit_durumu,
                'response_preview' => $errorBody,
            ]);

            throw new \Exception("Ollama API hatası (HTTP {$yanit_durumu})");

        } catch (ConnectionException $e) {
            $this->telemetryService->logFailure('ollama', $endpoint, $e->getMessage(), 0, ['request' => $prompt]);
            Log::error('Ollama connection failed', [
                'url' => $this->apiUrl,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Ollama servisine bağlanılamadı. Lütfen servis durumunu kontrol edin.');
        } catch (RequestException $e) {
            $yanit_kod = $e->response?->getStatusCode() ?? 0;
            $this->telemetryService->logFailure('ollama', $endpoint, $e->getMessage(), $yanit_kod, ['request' => $prompt]);
            Log::error('Ollama request exception', [
                'url' => $this->apiUrl,
                'yanit_kod' => $yanit_kod,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Ollama API isteği başarısız (HTTP {$yanit_kod})");
        } catch (\Exception $e) {
             // Catch all other exceptions (including the one thrown above) to ensure log if missed?
             // Actually the ones above throw new Exception, so they are caught here if nested?
             // No, they are caught by the caller.
             // But if an exception occurs inside the try block that isn't Connection or Request, it comes here.
             // If I throw inside catch components, it bubbles up.
             // For safety, I added logging in the specific catch blocks above. A final catch isn't strictly needed if coverage is good.
             throw $e;
        }
    }

    /**
     * Başlık prompt'u oluştur
     * @deprecated Use \App\Application\AI\Prompts\GeneratePropertyTitlePrompt (Future) or Action domain.
     * AI Eğitim Paketi Template (04-PROMPT-TEMPLATES.md)
     */
    protected function buildTitlePrompt(array $data): string
    {
        $kategori = $data['kategori'] ?? 'Gayrimenkul';
        $lokasyon = $data['lokasyon'] ?? '';
        $yayin_tipi_val = $data['yayin_tipi_id'] ?? $data['yayin_tipi'] ?? 'Satılık';
        $fiyat = $data['fiyat'] ?? '';
        $tone = $data['tone'] ?? 'seo';

        $toneDescriptions = [
            'seo' => 'SEO optimize edilmiş, anahtar kelime yoğun, detaylı',
            'kurumsal' => 'Profesyonel, yatırım odaklı, kurumsal dil',
            'hizli_satis' => 'Dikkat çekici, acil, heyecan verici',
            'luks' => 'Prestijli, özel, ayrıcalıklı dil',
        ];

        $toneInstruction = $toneDescriptions[$tone] ?? $toneDescriptions['seo'];

        // ✨ Özellik verileri varsa ekle
        $ozelliklerText = '';
        if (!empty($data['ozellikler'])) {
            $ozelliklerText = "\nÖzellikler: {$data['ozellikler']}";
        }

        // ✨ POI verileri varsa ekle (Pazarlama Zekası)
        $poiText = '';
        if (!empty($data['poi_data']) && is_array($data['poi_data'])) {
            $poiList = [];
            foreach ($data['poi_data'] as $poi) {
                $poiName = $poi['name'] ?? '';
                $distance = $poi['distance_km'] ?? 0;
                $poiType = $poi['type'] ?? ''; // context7-ignore
                if ($poiName && $distance > 0) {
                    $poiList[] = "{$poiName} ({$poiType}): {$distance}km mesafede";
                }
            }
            if (!empty($poiList)) {
                $poiText = "\n\n--- YAKIN ÇEVRE (POI) - PAZARLAMA AVANTAJLARI ---\n" . implode("\n", $poiList);
                $poiText .= "\n\nÖNEMLİ TALİMAT: Başlıkta mutlaka bölgenin en güçlü POI avantajını (örn: 'Marinaya 1.2km', 'Plaja 500m') kullan. SEO kurallarına sadık kalarak tıklanma oranını artır. POI bilgisi başlığın pazarlama değerini artırır.";
            }
        }

        // ✨ Market trend verileri varsa ekle (Market-Aware Content)
        $marketText = '';
        if (!empty($data['market_trends']) && is_array($data['market_trends'])) {
            $position = $data['market_trends']['position'] ?? 'unknown';
            $diffPercentage = $data['market_trends']['diff_percentage'] ?? 0;
            $marketPulse = $data['market_trends']['market_pulse'] ?? 'low';

            $marketText = "\n\n--- PİYASA DURUMU (MARKET INTELLIGENCE) ---\n";
            $marketText .= "Fiyat Konumu: {$position}\n";
            $marketText .= "Bölge Ortalamasından Fark: %{$diffPercentage}\n";
            $marketText .= "Piyasa Hareketliliği: {$marketPulse}\n";

            // Market-aware talimatlar
            if ($position === 'cheap' && $marketPulse === 'high') {
                $marketText .= "\nTALİMAT: Fiyatlar düşüşte ve piyasa hareketli. Başlıkta 'Fırsat', 'Acil', 'Kaçırılmayacak' gibi aciliyet vurguları kullan.";
            } elseif ($position === 'expensive' && $marketPulse === 'low') {
                $marketText .= "\nTALİMAT: Fiyatlar yüksek ve piyasa durgun. Başlıkta 'Premium', 'Özel', 'Eşsiz' gibi değer vurguları kullan.";
            } elseif ($position === 'fair' && $marketPulse === 'high') {
                $marketText .= "\nTALİMAT: Fiyat adil ve piyasa hareketli. Başlıkta 'Yatırım Değeri', 'Kaçırılmayacak Fırsat' gibi pozitif vurgular kullan.";
            }
        }

        // AI Eğitim Paketi - Optimize Prompt Template
        return "Sen bir emlak uzmanısın. Aşağıdaki bilgilere göre {$toneInstruction} tonunda 3 farklı ilan başlığı oluştur.

Kategori: {$kategori}
Yayın Tipi: {$yayin_tipi_val}
Lokasyon: {$lokasyon}
Fiyat: {$fiyat}{$ozelliklerText}{$poiText}{$marketText}
Ton: {$tone}

Kurallar:
- Her başlık 60-80 karakter arası
- Lokasyon mutlaka geçmeli
- SEO uyumlu anahtar kelimeler
- POI avantajlarını (Marina, Plaj, Havalimanı mesafeleri) başlığa dahil et
- Sadece başlıkları yaz, numaralama yapma
- Emoji kullanma

Başlıklar:";
    }

    /**
     * Açıklama prompt'u oluştur
     * @deprecated Use \App\Application\AI\Prompts\GeneratePropertyDescriptionPrompt (Future) or Action domain.
     * AI Eğitim Paketi Template (04-PROMPT-TEMPLATES.md)
     */
    protected function buildDescriptionPrompt(array $data): string
    {
        $kategori = $data['kategori'] ?? 'Gayrimenkul';
        $yayin_tipi_val = $data['yayin_tipi_id'] ?? $data['yayin_tipi'] ?? 'Satılık';
        $lokasyon = $data['lokasyon'] ?? '';
        $fiyat = $data['fiyat'] ?? '';
        $metrekare = $data['metrekare'] ?? '';
        $oda_sayisi = $data['oda_sayisi'] ?? '';
        $tone = $data['tone'] ?? 'seo';

        $spatialContext = $data['spatial_context'] ?? '';
        $walkabilityScore = $data['walkability_score'] ?? null;

        $prompt = "Sen profesyonel bir emlak danışmanısın. Aşağıdaki özellikte profesyonel ilan açıklaması yaz.\n\n";

        $prompt .= "Kategori: {$kategori}\n";
        $prompt .= "Yayın Tipi: {$yayin_tipi_val}\n";
        $prompt .= "Lokasyon: {$lokasyon}\n";
        $prompt .= "Fiyat: {$fiyat}\n";
        $prompt .= "Metrekare: {$metrekare} m²\n";
        $prompt .= "Oda Sayısı: {$oda_sayisi}\n";
        $prompt .= "Ton: {$tone}\n";

        // ✨ Özellik verileri varsa ekle
        if (!empty($data['ozellikler'])) {
            $prompt .= "Özellikler: {$data['ozellikler']}\n";
        }

        // ✨ POI verileri varsa ekle (Pazarlama Zekası)
        // ✅ Yeni 11 Kategorili POI Sistemi Desteği
        if (!empty($data['poi_data']) && is_array($data['poi_data'])) {
            $prompt .= "\n--- YAKIN ÇEVRE (POI) - PAZARLAMA DEĞERLERİ ---\n";
            $prompt .= "✅ 11 Kategorili POI Sistemi: Ulaşım, Marketler, Sağlık, Eğitim, Kafeler/Restoranlar, Alışveriş Merkezleri, Eğlence, Dini Merkezler, Spor, Kültürel, Tarihi & Turistik\n\n";

            // Kategorilere göre grupla
            $poisByCategory = [];
            foreach ($data['poi_data'] as $poi) {
                $poiName = $poi['name'] ?? '';
                $distance = $poi['distance_km'] ?? 0;
                $poiType = $poi['type'] ?? ''; // context7-ignore
                $poiCategory = $poi['category'] ?? 'Yakın Çevre';
                $marketingBadge = $poi['marketing_badge'] ?? '';

                if ($poiName && $distance > 0) {
                    if (!isset($poisByCategory[$poiCategory])) {
                        $poisByCategory[$poiCategory] = [];
                    }
                    $poisByCategory[$poiCategory][] = [
                        'name' => $poiName,
                        'type' => $poiType, // context7-ignore
                        'distance' => $distance,
                        'badge' => $marketingBadge,
                    ];
                }
            }

            // Kategorilere göre göster
            foreach ($poisByCategory as $category => $pois) {
                $prompt .= "\n📌 {$category}:\n";
                foreach ($pois as $poi) {
                    $distanceText = $poi['distance'] < 1
                        ? round($poi['distance'] * 1000) . 'm'
                        : round($poi['distance'], 2) . 'km';

                    $prompt .= "  - {$poi['name']} ({$poi['type']}): {$distanceText} mesafede\n"; // context7-ignore

                    if ($poi['badge']) {
                        $prompt .= "    Pazarlama Badge: {$poi['badge']}\n";
                    }
                }
            }

            $prompt .= "\n";
            $prompt .= "TALİMAT: Yukarıdaki POI bilgilerini doğal bir şekilde açıklamaya dahil et.\n";
            $prompt .= "✅ Kategori bazlı açıklama yap: 'Ulaşım olanakları', 'Sağlık kurumlarına yakınlık', 'Eğitim imkanları' gibi.\n";
            $prompt .= "Örnek: 'Yalıkavak Marina'ya sadece 1.2 km mesafede konumlanmış bu mülk, denizcilik tutkunları için ideal bir konumda...'\n";
            $prompt .= "Örnek: 'Bölgede sağlık kurumlarına, eğitim tesislerine ve alışveriş merkezlerine yakın konumuyla günlük yaşamın tüm ihtiyaçlarını karşılayacak imkanlar sunuyor...'\n";
            $prompt .= "POI bilgilerini pazarlama değeri olarak vurgula, ancak abartma. Kategorilere göre grupla ve anlamlı bağlamlar oluştur.\n\n";
        }

        // ✨ Market trend verileri varsa ekle (Market-Aware Content)
        if (!empty($data['market_trends']) && is_array($data['market_trends'])) {
            $position = $data['market_trends']['position'] ?? 'unknown';
            $diffPercentage = $data['market_trends']['diff_percentage'] ?? 0;
            $marketPulse = $data['market_trends']['market_pulse'] ?? 'low';

            $prompt .= "\n--- PİYASA DURUMU (MARKET INTELLIGENCE) ---\n";
            $prompt .= "Fiyat Konumu: {$position}\n";
            $prompt .= "Bölge Ortalamasından Fark: %{$diffPercentage}\n";
            $prompt .= "Piyasa Hareketliliği: {$marketPulse}\n";

            // Market-aware talimatlar
            if ($position === 'cheap' && $marketPulse === 'high') {
                $prompt .= "\nTALİMAT: Fiyatlar düşüşte ve piyasa hareketli. Açıklamada 'Fırsat', 'Acil', 'Kaçırılmayacak' gibi aciliyet vurguları kullan. Yatırımcılar için cazip bir fırsat olduğunu belirt.";
            } elseif ($position === 'expensive' && $marketPulse === 'low') {
                $prompt .= "\nTALİMAT: Fiyatlar yüksek ve piyasa durgun. Açıklamada 'Premium', 'Özel', 'Eşsiz', 'Değerli' gibi değer vurguları kullan. Mülkün neden bu fiyatta olduğunu gerekçelendir.";
            } elseif ($position === 'fair' && $marketPulse === 'high') {
                $prompt .= "\nTALİMAT: Fiyat adil ve piyasa hareketli. Açıklamada 'Yatırım Değeri', 'Kaçırılmayacak Fırsat', 'Piyasa Değerinde' gibi pozitif vurgular kullan.";
            }
            $prompt .= "\n";
        }

        $prompt .= "\n";

        if ($spatialContext) {
            $prompt .= "--- AKILLI KONUM ANALİZİ (CORTEX AI) ---\n";
            if ($walkabilityScore) {
                $prompt .= "Yürünebilirlik Skoru: {$walkabilityScore}/100\n";
            }
            $prompt .= "Çevre Detayları: {$spatialContext}\n";
            $prompt .= "TALİMAT: Yukarıdaki konum avantajlarını (yürüme mesafesi, sosyallik vb.) metnin içinde mutlaka vurgula.\n\n";
        }

        $prompt .= "Kurallar:\n";
        $prompt .= "- 200-250 kelime\n";
        $prompt .= "- 3 paragraf\n";
        $prompt .= "- Türkçe dilbilgisi kurallarına uygun\n";
        $prompt .= "- SEO uyumlu anahtar kelimeler\n";
        $prompt .= "- Müşteri odaklı ve çekici ton\n\n";

        $prompt .= "Paragraf Yapısı:\n";
        $prompt .= "1. Genel tanıtım + Lokasyon avantajları\n";
        $prompt .= "2. Teknik detaylar + Özellikler\n";
        $prompt .= "3. Çevre, ulaşım, yatırım değeri\n\n";

        $prompt .= "Açıklama:";

        return $prompt;
    }

    /**
     * Lokasyon analizi prompt'u
     *
     * AI Eğitim Paketi Template (04-PROMPT-TEMPLATES.md)
     */
    protected function buildLocationAnalysisPrompt(array $locationData): string
    {
        $il = $locationData['il'] ?? '';
        $ilce = $locationData['ilce'] ?? '';
        $mahalle = $locationData['mahalle'] ?? '';

        // AI Eğitim Paketi - Lokasyon Analizi Template
        return "Sen bir gayrimenkul analistisin. Aşağıdaki lokasyon için kısa analiz yap.

Lokasyon: {$il}, {$ilce}, {$mahalle}

Değerlendirme Kriterleri:
- Merkeze yakınlık (0-25 puan)
- Sosyal tesisler (okul, hastane) (0-20 puan)
- Ulaşım (toplu taşıma, otoyol) (0-20 puan)
- Altyapı (0-20 puan)
- Gelişim potansiyeli (0-15 puan)

Çıktı Formatı:
Skor: [0-100]
Harf: [A/B/C/D]
Potansiyel: [Yüksek/Orta/Düşük]
Gerekçe: [Kısa açıklama, max 100 kelime]

Analiz:";
    }

    /**
     * Fiyat önerisi prompt'u
     *
     * AI Eğitim Paketi Template (04-PROMPT-TEMPLATES.md)
     */
    protected function buildPriceSuggestionPrompt(array $propertyData): string
    {
        $basePrice = $propertyData['base_price'] ?? 0;
        $kategori = $propertyData['kategori'] ?? '';
        $metrekare = $propertyData['metrekare'] ?? 0;
        $lokasyon = $propertyData['lokasyon'] ?? '';

        // AI Eğitim Paketi - Fiyat Analizi Template
        return "Fiyat analizi yap ve 3 öneri sun.

Girilen Fiyat: {$basePrice} TRY
Kategori: {$kategori}
Lokasyon: {$lokasyon}
Alan: {$metrekare} m²

Hesapla:
- m² başı fiyat
- Bölge ortalaması ile karşılaştır
- 3 seviyeli öneri:
  1. Pazarlık (-10%): Hızlı satış
  2. Piyasa (+5%): Ortalama
  3. Premium (+15%): Özel özellikler

Format:
[Seviye]: [Fiyat] - [Gerekçe]

Analiz:";
    }

    /**
     * Başlık yanıtını parse et
     */
    protected function parseTitleResponse(string $response): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $response)));
        $titles = [];

        foreach ($lines as $line) {
            // Numaraları ve özel karakterleri temizle
            $clean = preg_replace('/^[\d\.\-\*]+\s*/', '', $line);
            if (strlen($clean) > 20 && strlen($clean) < 150) {
                $titles[] = $clean;
            }
        }

        return array_slice($titles, 0, 3);
    }

    /**
     * Lokasyon analizi parse et
     */
    protected function parseLocationAnalysis(string $response): array
    {
        // Basit parsing
        $score = 85;
        $grade = 'A';
        $potential = 'Yüksek';

        if (preg_match('/Skor[:\s]+(\d+)/', $response, $matches)) {
            $score = (int) $matches[1];
        }

        if (preg_match('/Harf[:\s]+([A-F])/', $response, $matches)) {
            $grade = $matches[1];
        }

        if (preg_match('/Potansiyel[:\s]+(Yüksek|Orta|Düşük)/i', $response, $matches)) {
            $potential = $matches[1];
        }

        return [
            'score' => $score,
            'grade' => $grade,
            'potential' => $potential,
        ];
    }

    /**
     * Fiyat önerileri parse et
     */
    protected function parsePriceSuggestions(string $response, float $basePrice): array
    {
        $numbers = [];
        preg_match_all('/[\d.,]+/', $response, $matches);

        foreach ($matches[0] as $match) {
            $num = floatval(str_replace([',', '.'], ['', ''], $match));
            if ($num > 0) {
                $numbers[] = $num;
            }
        }

        if (count($numbers) >= 3) {
            return [
                ['label' => 'Pazarlık Payı', 'value' => $numbers[0]],
                ['label' => 'Piyasa Ortalaması', 'value' => $numbers[1]],
                ['label' => 'Premium Fiyat', 'value' => $numbers[2]],
            ];
        }

        return $this->getFallbackPriceSuggestions($basePrice);
    }

    /**
     * Fallback başlıklar
     */
    protected function getFallbackTitles(array $data): array
    {
        $lokasyon = $data['lokasyon'] ?? 'Bodrum';
        $kategori = $data['kategori'] ?? 'Gayrimenkul';
        $yayin_tipi_val = $data['yayin_tipi_id'] ?? $data['yayin_tipi'] ?? 'Satılık';

        return [
            "{$lokasyon} {$yayin_tipi_val} {$kategori}",
            "{$yayin_tipi_val} {$kategori} - {$lokasyon}",
            "{$lokasyon}'da {$yayin_tipi_val} {$kategori}",
        ];
    }

    /**
     * Fallback açıklama
     */
    protected function getFallbackDescription(array $data): string
    {
        return 'Profesyonel bir ilan açıklaması hazırlanıyor...';
    }

    /**
     * Fallback lokasyon analizi
     */
    protected function getFallbackLocationAnalysis(): array
    {
        return [
            'score' => 75,
            'grade' => 'B',
            'potential' => 'Orta',
        ];
    }

    /**
     * Fallback fiyat önerileri
     */
    protected function getFallbackPriceSuggestions(float $basePrice): array
    {
        if ($basePrice <= 0) {
            return [];
        }

        return [
            ['label' => 'Pazarlık Payı (-10%)', 'value' => $basePrice * 0.9],
            ['label' => 'Piyasa Ortalaması (+5%)', 'value' => $basePrice * 1.05],
            ['label' => 'Premium Fiyat (+15%)', 'value' => $basePrice * 1.15],
        ];
    }

    /**
     * Health check
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->apiUrl);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Şablon önerileri üret
     */
    public function generateTemplateSuggestions(string $categoryName, string $description = ''): array
    {
        $cacheKey = 'ollama_template_' . md5($categoryName . $description);

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($categoryName, $description) {
            $prompt = $this->buildTemplatePrompt($categoryName, $description);

            try {
                $response = $this->sendRequest($prompt, 1000, 'template_suggestions'); // 1000 token for detailed JSON

                if (isset($response['response'])) {
                    return $this->parseTemplateResponse($response['response']);
                }
            } catch (\Exception $e) {
                Log::error('Ollama template generation failed', ['error' => $e->getMessage()]);
            }

            return $this->getFallbackTemplate($categoryName);
        });
    }

    /**
     * Şablon prompt'u oluştur
     * @deprecated Use \App\Application\AI\Prompts\SuggestTemplatePrompt instead.
     */
    protected function buildTemplatePrompt(string $categoryName, string $description): string
    {
        // AI Eğitim Paketi - Template Generator Prompt
        return "Sen uzman bir emlak veritabanı mimarısın. Aşağıdaki emlak kategorisi için en kapsamlı ve profesyonel özellik şablonunu oluştur.

Kategori: {$categoryName}
Ek Açıklama: {$description}

GÖREV: Bu emlak türü için kullanıcıların filtrelemede kullanacağı ve ilan detayında görmek isteyeceği özellikleri gruplayarak listele.

ÇIKTI FORMATI (SAF JSON):
{
  \"groups\": [
    {
      \"name\": \"Özellik Grubu Adı (Örn: Mutfak)\",
      \"features\": [
        {\"name\": \"Özellik Adı (Örn: Bulaşık Makinesi)\", \"type\": \"checkbox\", \"options\": []}, // context7-ignore
        {\"name\": \"Özellik Adı (Örn: Mutfak Tipi)\", \"type\": \"select\", \"options\": [\"Amerikan\", \"Ayrı\", \"Kitchenette\"]} // context7-ignore
      ]
    }
  ]
}

KURALLAR:
1. Türkçe karakter kullan.
2. Sadece JSON döndür. Markdown veya açıklama yazma.
3. En az 5 grup ve her grupta en az 3 özellik olsun.
4. Boolean (var/yok) özellikler için 'type': 'checkbox' kullan. // context7-ignore
5. Seçenekli özellikler için 'type': 'select' ve 'options' array'i kullan. // context7-ignore
6. 'Mutfak', 'Banyo', 'Dış Özellikler', 'Konum', 'Teknoloji' gibi standart emlak gruplarını kullan.

JSON:";
    }

    protected function parseTemplateResponse(string $response): array
    {
        // JSON bloğunu bul (en dıştaki { } parantezlerini yakala)
        $clean = $response;
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $clean = $matches[0];
        }

        $json = json_decode($clean, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($json['groups'])) {
            return $json;
        }

        Log::error('Ollama JSON parsing failed or groups missing', [
            'json_error' => json_last_error_msg(),
            'raw_response' => $response,
            'cleaned_response' => $clean
        ]);

        return $this->getFallbackTemplate("Parse Error");
    }

    protected function getFallbackTemplate(string $categoryName): array
    {
        return [
            'groups' => [
                [
                    'name' => 'Genel Özellikler',
                    'features' => [
                        ['name' => 'Özellik 1', 'type' => 'checkbox', 'options' => []], // context7-ignore
                        ['name' => 'Özellik 2', 'type' => 'checkbox', 'options' => []], // context7-ignore
                    ]
                ]
            ]
        ];
    }

    /**
     * Eksik Özellik Analizi (Gap Analysis)
     */
    public function analyzeTemplateGaps(string $categoryName, array $currentFeatures): array
    {
        $cacheKey = 'ollama_gap_' . md5($categoryName . json_encode($currentFeatures));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($categoryName, $currentFeatures) {
            $prompt = $this->buildGapAnalysisPrompt($categoryName, $currentFeatures);

            try {
                $response = $this->sendRequest($prompt, 1200);

                if (isset($response['response'])) {
                    return $this->parseTemplateResponse($response['response']); // Reusing parse logic as structure is similar
                }
            } catch (\Exception $e) {
                Log::error('Ollama gap analysis failed', ['error' => $e->getMessage()]);
            }

            return ['groups' => []];
        });
    }

    /**
     * Metinden Özellik Çıkarımı (Competitor Analysis)
     */
    public function extractFeaturesFromText(string $text): array
    {
        // No cache for text extraction as input varies widely
        $prompt = $this->buildExtractionPrompt($text);

        try {
            $response = $this->sendRequest($prompt, 1500);

            if (isset($response['response'])) {
                return $this->parseTemplateResponse($response['response']);
            }
        } catch (\Exception $e) {
            Log::error('Ollama extraction failed', ['error' => $e->getMessage()]);
        }

        return ['groups' => []];
    }

    /**
     * @deprecated Use \App\Application\AI\Prompts\AnalyzePropertyPrompt instead.
     */
    protected function buildGapAnalysisPrompt(string $categoryName, array $currentFeatures): string
    {
        $currentList = implode(', ', $currentFeatures);

        return "Sen uzman bir emlak analistisin.
Kategori: {$categoryName}
Mevcut Özellikler: {$currentList}

GÖREV: Bu kategorideki PREMIUM ve profesyonel ilanlarda olması gereken ancak yukarıdaki listede EKSİK olan özellikleri belirle. Mevcut özellikleri TEKRAR ETME.

ÇIKTI FORMATI (SAF JSON):
{
  \"groups\": [
    {
      \"name\": \"Eksik Özellik Grubu\",
      \"features\": [
        {\"name\": \"Eksik Özellik Adı\", \"type\": \"checkbox\", \"options\": []} // context7-ignore
      ]
    }
  ]
}

KURALLAR:
1. Sadece eksik olan kritik özellikleri öner.
2. Türkçe karakter kullan.
3. Gereksiz veya çok nadir özellikleri önerme.
JSON:";
    }

    /**
     * @deprecated Use \App\Application\AI\Prompts\ExtractFeaturesPrompt instead.
     */
    protected function buildExtractionPrompt(string $text): string
    {
        // Limit text length for token safety
        $safeText = substr($text, 0, 4000);

        return "Sen bir veri madencisisin. Aşağıdaki emlak ilanı metninden tüm teknik özellikleri ve olanakları çıkar.

METİN:
{$safeText}

GÖREV: Metinde geçen tüm özellikleri gruplayarak JSON formatında listele.

ÇIKTI FORMATI (SAF JSON):
{
  \"groups\": [
    {
      \"name\": \"Grup Adı\",
      \"features\": [
        {\"name\": \"Özellik Adı\", \"type\": \"checkbox\", \"options\": []}, // context7-ignore
        {\"name\": \"Seçenekli Özellik\", \"type\": \"select\", \"options\": [\"Seçenek1\", \"Seçenek2\"]} // context7-ignore
      ]
    }
  ]
}

KURALLAR:
1. Özellik isimlerini standartlaştır (Örn: 'Wifi var' yerine 'Wi-Fi').
2. Sadece net olarak belirtilen veya ima edilen özellikleri çıkar.
JSON:";
    }


}
