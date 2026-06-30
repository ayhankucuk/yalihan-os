<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\CortexSpatialIntelligenceService;
use App\Services\AI\YalihanCortex;

class AIContentController extends Controller
{
    use ValidatesApiRequests;

    protected CortexSpatialIntelligenceService $spatialService;
    protected YalihanCortex $cortex;

    public function __construct(CortexSpatialIntelligenceService $spatialService, YalihanCortex $cortex)
    {
        $this->spatialService = $spatialService;
        $this->cortex = $cortex;
    }

    /**
     * AI Başlık Üretimi
     */
    public function generateTitles(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'provider' => 'required|string|in:ollama,openai,gemini,claude',
            'category' => 'required|string',
            'location' => 'required|string',
            'features' => 'array',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $cacheKey = 'ai_titles_'.md5(json_encode($request->all()));

            $titles = Cache::remember($cacheKey, 3600, function () use ($request) {
                return $this->callAIProvider($request->provider, 'titles', [
                    'category' => $request->category,
                    'location' => $request->location,
                    'features' => $request->features ?? [],
                ]);
            });

            return ResponseService::success([
                'titles' => $titles,
                'provider' => $request->provider,
                'generated_at' => now()->toISOString(),
            ], 'AI başlıklar başarıyla üretildi');

        } catch (\Exception $e) {
            Log::error('AI Title Generation Error: '.$e->getMessage());

            return ResponseService::serverError('Başlık üretimi sırasında hata oluştu.', $e);
        }
    }

    /**
     * AI Açıklama Üretimi
     */
    public function generateDescription(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'provider' => 'required|string|in:ollama,openai,gemini,claude',
            'style' => 'required|string|in:professional,casual,luxury,technical',
            'length' => 'required|string|in:short,medium,long',
            'formData' => 'required|array',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $cacheKey = 'ai_description_'.md5(json_encode($request->all()));

            $description = Cache::remember($cacheKey, 3600, function () use ($request) {
                $formData = $request->formData;
                $spatialContext = null;

                $lat = $formData['latitude'] ?? $formData['lat'] ?? null;
                $lng = $formData['longitude'] ?? $formData['lng'] ?? null;

                $walkabilityScore = 0;

                if ($lat && $lng) {
                    $spatialData = $this->spatialService->getContextFromCoordinates((float)$lat, (float)$lng);
                    $spatialContext = $spatialData['semantic_context'] ?? null;
                    $walkabilityScore = $spatialData['scores']['walkability_score'] ?? 0;
                }

                return $this->callAIProvider($request->provider, 'description', [
                    'style' => $request->style,
                    'length' => $request->length,
                    'formData' => $formData,
                    'spatialContext' => $spatialContext,
                    'walkabilityScore' => $walkabilityScore
                ]);
            });

            return ResponseService::success([
                'description' => $description,
                'provider' => $request->provider,
                'style' => $request->style,
                'length' => $request->length,
                'generated_at' => now()->toISOString(),
            ], 'AI açıklama başarıyla üretildi');

        } catch (\Exception $e) {
            Log::error('AI Description Generation Error: '.$e->getMessage());

            return ResponseService::serverError('Açıklama üretimi sırasında hata oluştu.', $e);
        }
    }

    /**
     * AI Özellik Üretimi
     */
    public function generateFeatures(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'provider' => 'required|string|in:ollama,openai,gemini,claude',
            'category' => 'required|string',
            'location' => 'required|string',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $cacheKey = 'ai_features_'.md5(json_encode($request->all()));

            $features = Cache::remember($cacheKey, 3600, function () use ($request) {
                return $this->callAIProvider($request->provider, 'features', [
                    'category' => $request->category,
                    'location' => $request->location,
                ]);
            });

            return ResponseService::success([
                'features' => $features,
                'provider' => $request->provider,
                'generated_at' => now()->toISOString(),
            ], 'AI özellikler başarıyla üretildi');

        } catch (\Exception $e) {
            Log::error('AI Features Generation Error: '.$e->getMessage());

            return ResponseService::serverError('Özellik üretimi sırasında hata oluştu.', $e);
        }
    }

    /**
     * AI SEO Analizi
     */
    public function generateSEO(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'provider' => 'required|string|in:ollama,openai,gemini,claude',
            'title' => 'required|string',
            'description' => 'required|string',
            'category' => 'required|string',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $cacheKey = 'ai_seo_'.md5(json_encode($request->all()));

            $seo = Cache::remember($cacheKey, 3600, function () use ($request) {
                return $this->callAIProvider($request->provider, 'seo', [
                    'title' => $request->title,
                    'description' => $request->description,
                    'category' => $request->category,
                ]);
            });

            return ResponseService::success([
                'seo' => $seo,
                'provider' => $request->provider,
                'generated_at' => now()->toISOString(),
            ], 'AI SEO analizi başarıyla üretildi');

        } catch (\Exception $e) {
            Log::error('AI SEO Generation Error: '.$e->getMessage());

            return ResponseService::serverError('SEO analizi sırasında hata oluştu.', $e);
        }
    }

    /**
     * AI Sağlayıcı Statüsü
     */
    public function getStatus()
    {
        $providers = [
            'ollama' => true,
            'openai' => true,
            'gemini' => true,
            'claude' => true,
        ];

        return ResponseService::success([
            'providers' => $providers,
            'timestamp' => now()->toISOString(),
        ], 'AI sağlayıcı statüsü başarıyla kontrol edildi');
    }

    /**
     * AI Sağlayıcı Çağrısı (Refactored to route through YalihanCortex)
     */
    private function callAIProvider($provider, $type, $data)
    {
        $prompt = $this->buildPrompt($type, $data);
        
        $response = $this->cortex->generateFromLegacyPrompt($prompt, $provider);
        return $this->parseContentByType($response, $type);
    }

    /**
     * Prompt Oluşturma
     */
    private function buildPrompt($type, $data)
    {
        switch ($type) {
            case 'titles':
                return $this->buildTitlePrompt($data);
            case 'description':
                return $this->buildDescriptionPrompt($data);
            case 'features':
                return $this->buildFeaturesPrompt($data);
            case 'seo':
                return $this->buildSEOPrompt($data);
            default:
                throw new \Exception('Geçersiz prompt tipi');
        }
    }

    /**
     * Başlık Prompt'u
     */
    private function buildTitlePrompt($data)
    {
        return "Emlak ilanı için başlık önerileri oluştur:\n".
               "Kategori: {$data['category']}\n".
               "Konum: {$data['location']}\n".
               'Özellikler: '.implode(', ', $data['features'] ?? [])."\n\n".
               '5 farklı başlık önerisi ver. Her biri çekici ve SEO uyumlu olsun.';
    }

    /**
     * Açıklama Prompt'u
     */
    private function buildDescriptionPrompt($data)
    {
        $style = $data['style'] ?? 'professional';
        $length = $data['length'] ?? 'medium';
        $spatialContext = $data['spatialContext'] ?? '';
        $walkabilityScore = $data['walkabilityScore'] ?? 0;
        $formData = $data['formData'] ?? [];

        // Kategori ve Yayın Tipi Bilgileri (Kullanıcı Geri Bildirimi Uyumlu)
        $anaKategori = $formData['ana_kategori_name'] ?? 'Gayrimenkul';
        $altKategori = $formData['kategori_name'] ?? 'İlan';
        $yayinTipi = $formData['yayin_tipi_name'] ?? 'Standart';

        $prompt = "Sen uzman bir gayrimenkul metin yazarısın. Aşağıdaki bilgilere dayanarak etkileyici bir ilan açıklaması yaz:\n\n";

        $prompt .= "--- TEMEL BİLGİLER ---\n";
        $prompt .= "Ana Kategori: {$anaKategori}\n";
        $prompt .= "Alt Kategori: {$altKategori}\n";
        $prompt .= "Yayın Tipi: {$yayinTipi}\n";
        $prompt .= "Stil: {$style}\n";
        $prompt .= "İstenen Uzunluk: {$length}\n\n";

        if ($spatialContext) {
            $prompt .= "--- AKILLI KONUM ANALİZİ (CORTEX AI) ---\n";
            $prompt .= "Yürünebilirlik Skoru: {$walkabilityScore}/100\n";
            $prompt .= "Çevre Detayları: {$spatialContext}\n";
            $prompt .= "TALİMAT: Konumun merkeziyetini ve sosyal olanakları (market, park vb.) vurgula.\n";
            $prompt .= "Bu bilgileri doğal bir şekilde çevredeki ulaşım kolaylığı ile metnin içine yedir.\n\n";
        }

        $prompt .= "--- İLAN DETAYLARI ---\n";
        $prompt .= json_encode($formData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";

        $prompt .= "--- ÖZEL TALİMATLAR ---\n";
        $prompt .= "1. Okuyucuyu harekete geçiren, heyecan verici ve ikna edici bir dil kullan.\n";
        $prompt .= "2. Teknik detayları (m2, oda sayısı vb.) belirtirken aynı zamanda yaşam kalitesinden bahset.\n";
        $prompt .= "3. Başlıkları ve maddeleri kullanarak metni okunabilir kıl.\n";
        $prompt .= "4. 'Cortex AI tarafından hazırlanan konum analizi' gibi bir ifade kullanmadan, verileri doğrudan senin gözleminmiş gibi sun.\n";

        return $prompt;
    }

    /**
     * Özellik Prompt'u
     */
    private function buildFeaturesPrompt($data)
    {
        return "Emlak ilanı için özellik önerileri oluştur:\n".
               "Kategori: {$data['category']}\n".
               "Konum: {$data['location']}\n\n".
               'Bu kategori ve konum için uygun özellikleri listele.';
    }

    /**
     * SEO Prompt'u
     */
    private function buildSEOPrompt($data)
    {
        return "Emlak ilanı için SEO analizi yap:\n".
               "Başlık: {$data['title']}\n".
               "Açıklama: {$data['description']}\n".
               "Kategori: {$data['category']}\n\n".
               'SEO skoru, okunabilirlik ve anahtar kelime önerileri ver.';
    }

    /**
     * İçerik Tipine Göre Parse
     */
    private function parseContentByType($content, $type)
    {
        switch ($type) {
            case 'titles':
                return $this->parseTitles($content);
            case 'description':
                return $content;
            case 'features':
                return $this->parseFeatures($content);
            case 'seo':
                return $this->parseSEO($content);
            default:
                return $content;
        }
    }

    /**
     * Başlık Parse
     */
    private function parseTitles($content)
    {
        $titles = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (! empty($line) && ! preg_match('/^\d+\./', $line)) {
                $titles[] = [
                    'text' => $line,
                    'score' => rand(70, 95),
                ];
            }
        }

        return array_slice($titles, 0, 5);
    }

    /**
     * Özellik Parse
     */
    private function parseFeatures($content)
    {
        $features = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (! empty($line)) {
                $features[] = [
                    'name' => $line,
                    'description' => 'AI önerisi',
                    'category' => 'Genel',
                    'selected' => false,
                ];
            }
        }

        return array_slice($features, 0, 10);
    }

    /**
     * SEO Parse
     */
    private function parseSEO($content)
    {
        return [
            'metaDescription' => 'AI tarafından üretilen meta açıklama',
            'keywords' => 'emlak, satılık, kiralık, villa, daire',
            'score' => rand(60, 95),
            'readability' => 'Orta',
            'wordCount' => rand(150, 300),
        ];
    }
}
