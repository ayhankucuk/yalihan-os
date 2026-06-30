<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\Logging\LogService;

/**
 * Cortex Vision Service v1.0
 * 
 * 👁️ KAPTAN'IN GÖZLERİ: İlan fotoğraflarını analiz eder.
 * GPT-4o Vision API entegrasyonu.
 * 
 * @sealed 2025-12-31
 */
class VisionService
{
    protected string $apiKey;
    protected string $model = 'gpt-4o';

    public function __construct()
    {
        $this->apiKey = config('ai.api_key');
    }

    /**
     * Bir fotoğrafı analiz eder.
     * 
     * @param string $imagePath Fotoğrafın tam yolu (storage/public altındaki yol)
     * @param array $context İlan bağlamı
     * @return array
     */
    public function analizEt(string $imagePath, array $context = []): array
    {
        try {
            $base64Image = $this->encodeImage($imagePath);
            
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Sen bir gayrimenkul uzmanı ve görsel analiz asistanısın. Verilen fotoğrafı analiz et ve JSON formatında yanıt dön. Yanıt şu alanları içermeli: quality_score (1-10), detected_features (array), is_verified (boolean), room_type (string), aesthetic_rating (1-10).'
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text', // context7-ignore
                                    'text' => "Bu fotoğrafı şu ilan bağlamında analiz et: " . json_encode($context)
                                ],
                                [
                                    'type' => 'image_url', // context7-ignore
                                    'image_url' => [
                                        'url' => "data:image/jpeg;base64,{$base64Image}"
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'response_format' => ['type' => 'json_object'], // context7-ignore
                    'max_tokens' => 500
                ]);

            if ($response->successful()) {
                $result = json_decode($response->json('choices.0.message.content'), true);
                
                LogService::info("VisionEngine: Fotoğraf analizi tamamlandı.", [
                    'path' => $imagePath,
                    'result' => $result
                ]);

                return $result;
            }

            Log::error('Cortex Vision API Hatası: ' . $response->body());
            return $this->getFallbackResponse();

        } catch (\Exception $e) {
            Log::error('Cortex Vision Servis Hatası: ' . $e->getMessage());
            return $this->getFallbackResponse();
        }
    }

    /**
     * Fotoğrafı base64 formatına çevirir
     */
    private function encodeImage(string $path): string
    {
        // Eğer path bir URL ise (bazı durumlarda dış kaynak olabilir)
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return base64_encode(file_get_contents($path));
        }

        // Storage'dan oku
        if (Storage::disk('public')->exists($path)) {
            return base64_encode(Storage::disk('public')->get($path));
        }

        // Doğrudan dosya sistemi yolu ise
        if (file_exists($path)) {
            return base64_encode(file_get_contents($path));
        }

        throw new \Exception("Dosya bulunamadı: " . $path);
    }

    /**
     * Hata durumunda güvenli yanıt döner
     */
    private function getFallbackResponse(): array
    {
        return [
            'quality_score' => 0,
            'detected_features' => [],
            'is_verified' => false,
            'room_type' => 'Bilinmiyor',
            'aesthetic_rating' => 0
        ];
    }
}
