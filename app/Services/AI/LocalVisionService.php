<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\Logging\LogService;

/**
 * Local Vision Service v1.0
 *
 * 🏠 YEREL GÖZLER: İlan fotoğraflarını yerel Ollama üzerinden analiz eder.
 * Model: Gemma 3 1B (Multimodal)
 *
 * Context7 Standardı: C7-LOCAL-VISION-SERVICE-2026-01-12
 */
class LocalVisionService
{
    protected string $apiUrl;
    protected string $model = 'gemma3:1b';

    public function __construct()
    {
        $this->apiUrl = config('ai.ollama_api_url', 'http://localhost:11434');
    }

    /**
     * Bir fotoğrafı yerel model ile analiz eder.
     *
     * @param string $imagePath Fotoğrafın tam yolu
     * @param string $prompt Analiz talimatı
     * @return array
     */
    public function analizEt(string $imagePath, string $prompt = 'Describe this property photo in detail. Mention room type, features, and quality.'): array
    {
        try {
            $base64Image = $this->encodeImage($imagePath);

            $response = Http::timeout(30)
                ->post($this->apiUrl . '/api/generate', [
                    'model' => $this->model,
                    'prompt' => $prompt . ' Output strictly in JSON format with fields: room_type, features (array), quality_score (1-10), description (Turkish).',
                    'images' => [$base64Image],
                    'stream' => false,
                    'format' => 'json'
                ]);

            if ($response->successful()) {
                $content = $response->json('response');
                $result = json_decode($content, true);

                if (!$result) {
                    // JSON parse fail olursa ham metni temizlemeye çalış
                    $result = $this->parseFlexibleJson($content);
                }

                LogService::info("LocalVision: Fotoğraf analizi tamamlandı.", [
                    'path' => $imagePath,
                    'model' => $this->model
                ]);

                return $result ?: $this->getFallbackResponse();
            }

            Log::error('Local Vision API Hatası: ' . $response->body());
            return $this->getFallbackResponse();

        } catch (\Exception $e) {
            Log::error('Local Vision Servis Hatası: ' . $e->getMessage());
            return $this->getFallbackResponse();
        }
    }

    /**
     * Fotoğrafı base64 formatına çevirir
     */
    private function encodeImage(string $path): string
    {
        if (Storage::disk('public')->exists($path)) {
            return base64_encode(Storage::disk('public')->get($path));
        }

        if (file_exists($path)) {
            return base64_encode(file_get_contents($path));
        }

        throw new \Exception("Dosya bulunamadı: " . $path);
    }

    /**
     * LLM bazen JSON dışı metin ekleyebilir, onları temizleyip parse eder.
     */
    private function parseFlexibleJson(string $text): ?array
    {
        preg_match('/\{.*\}/s', $text, $matches);
        if (isset($matches[0])) {
            return json_decode($matches[0], true);
        }
        return null;
    }

    /**
     * Hata durumunda güvenli yanıt döner
     */
    private function getFallbackResponse(): array
    {
        return [
            'room_type' => 'Bilinmiyor',
            'features' => [],
            'quality_score' => 0,
            'description' => 'Görsel analiz yapılamadı.'
        ];
    }
}
