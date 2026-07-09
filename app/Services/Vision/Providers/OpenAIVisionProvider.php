<?php

namespace App\Services\Vision\Providers;

use App\DTOs\Vision\VisionAnalysisDTO;
use App\DTOs\Vision\VisionObjectDTO;
use App\Services\Vision\Contracts\VisionProviderContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * OpenAI Vision Provider — Sprint 6.4
 *
 * GPT-4o Vision ile gerçek görüntü analizi.
 */
class OpenAIVisionProvider implements VisionProviderContract
{
    private string $apiKey;
    private string $model;
    private int $maxTokens;
    private float $temperature;

    public function __construct()
    {
        $this->apiKey      = (string) config('vision.openai.api_key', env('OPENAI_API_KEY', ''));
        $this->model       = (string) config('vision.openai.model', 'gpt-4o');
        $this->maxTokens   = (int) config('vision.openai.max_tokens', 1024);
        $this->temperature = (float) config('vision.openai.temperature', 0.3);
    }

    public function analyze(string $imagePath, array $context = []): VisionAnalysisDTO
    {
        $start = microtime(true);

        try {
            $imageUrl = $this->resolveImageUrl($imagePath);
            $response = $this->callVisionApi($imageUrl, $context);
            $parsed   = $this->parseResponse($response);

            $latency = (microtime(true) - $start) * 1000;

            return $this->buildDTO(
                fotografId: $context['fotograf_id'] ?? 0,
                parsed: $parsed,
                provider: 'openai',
                latencyMs: $latency,
                rawResponse: $response,
            );
        } catch (\Throwable $e) {
            Log::error('OpenAIVisionProvider: analysis failed', [
                'image' => $imagePath,
                'error' => $e->getMessage(),
            ]);

            return $this->errorDTO(
                fotografId: $context['fotograf_id'] ?? 0,
                error: $e->getMessage(),
                provider: 'openai',
            );
        }
    }

    public function providerName(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    // ─── Private ───────────────────────────────────────────────────────────────

    private function resolveImageUrl(string $path): string
    {
        // Already a URL
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        // Public storage URL
        if (Str::startsWith($path, '/storage/')) {
            return url($path);
        }

        // Convert relative to absolute
        $publicPath = public_path(ltrim($path, '/'));
        if (file_exists($publicPath)) {
            return url('/' . ltrim($path, '/'));
        }

        throw new \RuntimeException("Cannot resolve image path: {$path}");
    }

    private function callVisionApi(string $imageUrl, array $context): array
    {
        $prompt = $this->buildPrompt($context);

        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $imageUrl,
                                'detail' => 'high',
                            ],
                        ],
                    ],
                ],
            ],
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
        ];

        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if ($response->failed()) {
            throw new \RuntimeException('OpenAI API error: ' . $response->body());
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '';

        return json_decode($content, true) ?? [];
    }

    private function buildPrompt(array $context): string
    {
        $roomHint = $context['room_hint'] ?? 'unknown';

        return <<<PROMPT
You are an expert luxury real estate photo analyst. Analyze this property photo and respond ONLY with valid JSON.

**Room type detected by rule engine:** {$roomHint}

Respond with this exact JSON structure (no markdown, no explanation):
{
  "room": {
    "type": "string (one of: living_room, kitchen, bedroom, bathroom, dining_room, pool, garden, terrace, exterior, view, other)",
    "label": "string (human readable label in Turkish)",
    "confidence": 0.0-1.0
  },
  "objects": [
    {"slug": "string", "label": "string (Turkish)", "confidence": 0.0-1.0, "reason": "string"}
  ],
  "furniture": [
    {"slug": "string", "label": "string (Turkish)", "confidence": 0.0-1.0, "reason": "string"}
  ],
  "amenities": [
    {"slug": "string", "label": "string (Turkish)", "confidence": 0.0-1.0, "reason": "string"}
  ],
  "luxury_features": [
    {"slug": "string", "label": "string (Turkish)", "confidence": 0.0-1.0, "reason": "string"}
  ],
  "views": [
    {"slug": "string", "label": "string (Turkish)", "confidence": 0.0-1.0, "reason": "string"}
  ],
  "architectural_style": {
    "type": "string",
    "label": "string (Turkish)",
    "confidence": 0.0-1.0
  },
  "ai_quality": {
    "composition": 0-100,
    "luxury_appeal": 0-100,
    "marketability": 0-100,
    "professional_quality": 0-100
  }
}
PROMPT;
    }

    private function parseResponse(array $response): array
    {
        return [
            'room'                  => $response['room'] ?? [],
            'objects'              => $response['objects'] ?? [],
            'furniture'            => $response['furniture'] ?? [],
            'amenities'            => $response['amenities'] ?? [],
            'luxury_features'      => $response['luxury_features'] ?? [],
            'views'                => $response['views'] ?? [],
            'architectural_style'  => $response['architectural_style'] ?? [],
            'ai_quality'           => $response['ai_quality'] ?? [],
        ];
    }

    private function buildDTO(
        int $fotografId,
        array $parsed,
        string $provider,
        float $latencyMs,
        array $rawResponse,
    ): VisionAnalysisDTO {
        $rooms    = $this->mapObjects($parsed['room'], 'oda', $provider);
        $objects  = $this->mapObjects($parsed['objects'], 'ozellik', $provider);
        $furniture = $this->mapObjects($parsed['furniture'], 'mobilya', $provider);
        $amenities = $this->mapObjects($parsed['amenities'], 'amenity', $provider);
        $luxury   = $this->mapObjects($parsed['luxury_features'], 'lüks', $provider);
        $views    = $this->mapObjects($parsed['views'], 'manzara', $provider);
        $styles   = $this->mapObjects($parsed['architectural_style'], 'stil', $provider);

        $quality = $parsed['ai_quality'] ?? [];
        $aiQualityScore = $this->calcAiQualityScore($quality);

        $allObjects = array_merge($rooms, $objects, $furniture, $amenities, $luxury, $views);
        $overallConfidence = empty($allObjects) ? 0.0 : $this->calcOverallConfidence($allObjects);

        return new VisionAnalysisDTO(
            fotograf_id: $fotografId,
            objects: $objects,
            rooms: $rooms,
            furniture: $furniture,
            amenities: $amenities,
            luxuryFeatures: $luxury,
            views: $views,
            architecturalStyles: $styles,
            ai_quality_score: $aiQualityScore,
            ai_quality_breakdown: [
                'composition'        => (int) ($quality['composition'] ?? 0),
                'luxury_appeal'     => (int) ($quality['luxury_appeal'] ?? 0),
                'marketability'     => (int) ($quality['marketability'] ?? 0),
                'professional_quality' => (int) ($quality['professional_quality'] ?? 0),
            ],
            overall_confidence: $overallConfidence,
            provider: $provider,
            final_room_type: $rooms[0]->label ?? null,
            fusion_confidence: $overallConfidence,
            raw_response: $rawResponse,
        );
    }

    /** @return VisionObjectDTO[] */
    private function mapObjects(array $items, string $type, string $provider): array
    {
        if (empty($items)) {
            return [];
        }

        // Single item (like architectural_style)
        if (isset($items['type']) || isset($items['slug'])) {
            return [new VisionObjectDTO(
                type: $type,
                label: $items['label'] ?? $items['slug'] ?? '',
                confidence: (float) ($items['confidence'] ?? 0.0),
                provider: $provider,
                reason: $items['reason'] ?? '',
                metadata: array_diff_key($items, array_flip(['type', 'label', 'confidence', 'reason', 'slug'])),
            )];
        }

        return array_map(fn($item) => new VisionObjectDTO(
            type: $type,
            label: $item['label'] ?? $item['slug'] ?? '',
            confidence: (float) ($item['confidence'] ?? 0.0),
            provider: $provider,
            reason: $item['reason'] ?? '',
            metadata: array_diff_key($item, array_flip(['type', 'label', 'confidence', 'reason', 'slug'])),
        ), $items);
    }

    private function calcAiQualityScore(array $quality): int
    {
        if (empty($quality)) {
            return 0;
        }

        $scores = array_values($quality);
        if (empty($scores)) {
            return 0;
        }

        return (int) min(100, round(array_sum($scores) / count($scores)));
    }

    private function calcOverallConfidence(array $objects): float
    {
        if (empty($objects)) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($objects as $obj) {
            $sum += $obj->confidence;
        }

        return round($sum / count($objects), 3);
    }

    private function errorDTO(int $fotografId, string $error, string $provider): VisionAnalysisDTO
    {
        return new VisionAnalysisDTO(
            fotograf_id: $fotografId,
            objects: [],
            rooms: [],
            furniture: [],
            amenities: [],
            luxuryFeatures: [],
            views: [],
            architecturalStyles: [],
            ai_quality_score: 0,
            ai_quality_breakdown: [],
            overall_confidence: 0.0,
            provider: $provider,
            final_room_type: null,
            fusion_confidence: 0.0,
            error: $error,
        );
    }
}
