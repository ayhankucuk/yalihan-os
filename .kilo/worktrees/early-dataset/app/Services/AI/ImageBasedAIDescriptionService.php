<?php

namespace App\Services\AI;

/**
 * @sab-ignore-catch
 */

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * 🖼️ AI Resim Analizi Servisi
 *
 * Gelişmiş AI özellikleri:
 * - Resim analizi ve açıklama üretimi
 * - Otomatik etiketleme
 * - Obje tanıma
 * - Renk analizi
 * - Mimari stil analizi
 */
class ImageBasedAIDescriptionService
{
    private $apiKey;

    private $baseUrl;

    private $model;

    public function __construct()
    {
        $this->apiKey = config('ai.openai_api_key');
        $this->baseUrl = 'https://api.openai.com/v1';
        $this->model = 'gpt-4-vision-preview';
    }

    /**
     * Resim analizi ve açıklama üretimi
     */
    public function analyzeImage(string $imagePath, array $options = []): array
    {
        try {
            $defaultOptions = [
                'detail' => 'high',
                'max_tokens' => 1000,
                'include_objects' => true,
                'include_colors' => true,
                'include_architecture' => true,
                'include_style' => true,
            ];

            $options = array_merge($defaultOptions, $options);

            // Resmi base64'e çevir
            $imageBase64 = $this->encodeImageToBase64($imagePath);

            if (! $imageBase64) {
                throw new \Exception('Resim yüklenemedi');
            }

            // AI prompt oluştur
            $prompt = $this->buildImageAnalysisPrompt($options);

            // OpenAI API çağrısı
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->baseUrl.'/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text', // context7-ignore
                                'text' => $prompt,
                            ],
                            [
                                'type' => 'image_url', // context7-ignore
                                'image_url' => [
                                    'url' => 'data:image/jpeg;base64,'.$imageBase64,
                                    'detail' => $options['detail'],
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => $options['max_tokens'],
                'temperature' => 0.7,
            ]);

            if (! $response->successful()) {
                throw new \Exception('AI API hatası: '.$response->body());
            }

            $data = $response->json();
            $analysis = $data['choices'][0]['message']['content'] ?? '';

            return $this->parseImageAnalysis($analysis, $options);

        } catch (\Exception $e) {
            Log::error('AI Resim Analizi Hatası: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'analysis' => null,
            ];
        }
    }

    /**
     * Otomatik etiketleme
     */
    public function generateTags(string $imagePath): array
    {
        try {
            $analysis = $this->analyzeImage($imagePath, [
                'include_objects' => true,
                'include_colors' => true,
                'include_architecture' => true,
                'include_style' => true,
            ]);

            if (! $analysis['success']) {
                return [];
            }

            $tags = [];

            // Obje etiketleri
            if (isset($analysis['objects'])) {
                $tags = array_merge($tags, $analysis['objects']);
            }

            // Renk etiketleri
            if (isset($analysis['colors'])) {
                $tags = array_merge($tags, $analysis['colors']);
            }

            // Mimari stil etiketleri
            if (isset($analysis['architecture'])) {
                $tags = array_merge($tags, $analysis['architecture']);
            }

            return array_unique($tags);

        } catch (\Exception $e) {
            Log::error('AI Etiketleme Hatası: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Resim kalite analizi
     */
    public function analyzeImageQuality(string $imagePath): array
    {
        try {
            $analysis = $this->analyzeImage($imagePath, [
                'include_quality' => true,
                'include_lighting' => true,
                'include_composition' => true,
            ]);

            return [
                'success' => true,
                'quality_score' => $analysis['quality_score'] ?? 0,
                'lighting' => $analysis['lighting'] ?? 'unknown',
                'composition' => $analysis['composition'] ?? 'unknown',
                'recommendations' => $analysis['recommendations'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('AI Kalite Analizi Hatası: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Resmi base64'e çevir
     */
    private function encodeImageToBase64(string $imagePath): ?string
    {
        try {
            if (Storage::exists($imagePath)) {
                $imageData = Storage::get($imagePath);

                return base64_encode($imageData);
            }

            if (file_exists($imagePath)) {
                $imageData = file_get_contents($imagePath);

                return base64_encode($imageData);
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Resim Base64 Hatası: '.$e->getMessage());

            return null;
        }
    }

    /**
     * AI prompt oluştur
     */
    private function buildImageAnalysisPrompt(array $options): string
    {
        $prompt = "Bu emlak fotoğrafını analiz et ve aşağıdaki bilgileri ver:\n\n";

        if ($options['include_objects']) {
            $prompt .= "1. Görülen objeler ve özellikler\n";
        }

        if ($options['include_colors']) {
            $prompt .= "2. Ana renkler ve renk paleti\n";
        }

        if ($options['include_architecture']) {
            $prompt .= "3. Mimari stil ve özellikler\n";
        }

        if ($options['include_style']) {
            $prompt .= "4. Dekorasyon stili ve özellikler\n";
        }

        if (isset($options['include_quality'])) {
            $prompt .= "5. Fotoğraf kalitesi (1-10 arası puan)\n";
        }

        if (isset($options['include_lighting'])) {
            $prompt .= "6. Işıklandırma d' . 'urumu\n";
        }

        if (isset($options['include_composition'])) {
            $prompt .= "7. Kompozisyon ve çerçeveleme\n";
        }

        $prompt .= "\nSonuçları JSON formatında ver.";

        return $prompt;
    }

    /**
     * AI analiz sonucunu parse et
     */
    private function parseImageAnalysis(string $analysis, array $options): array
    {
        try {
            // JSON parse etmeye çalış
            $decoded = json_decode($analysis, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return [
                    'success' => true,
                    'analysis' => $decoded,
                    'raw_analysis' => $analysis,
                ];
            }

            // JSON değilse, metin olarak parse et
            return [
                'success' => true,
                'analysis' => $this->parseTextAnalysis($analysis),
                'raw_analysis' => $analysis,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Analiz parse edilemedi: '.$e->getMessage(),
                'raw_analysis' => $analysis,
            ];
        }
    }

    /**
     * Metin analizini parse et
     */
    private function parseTextAnalysis(string $analysis): array
    {
        $result = [];

        // Basit regex ile bilgileri çıkar
        if (preg_match('/kalite[:\s]*(\d+)/i', $analysis, $matches)) {
            $result['quality_score'] = (int) $matches[1];
        }

        if (preg_match('/ışık[:\s]*([^.\n]+)/i', $analysis, $matches)) {
            $result['lighting'] = trim($matches[1]);
        }

        if (preg_match('/kompozisyon[:\s]*([^.\n]+)/i', $analysis, $matches)) {
            $result['composition'] = trim($matches[1]);
        }

        return $result;
    }
}
