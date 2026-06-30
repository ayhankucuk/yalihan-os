<?php

namespace App\Services\AI\Vision\Providers;

use App\Services\AI\Vision\Contracts\VisionAnalysisContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🤖 OpenAI GPT-4o Vision Implementation
 * Phase 8: Real visual inference
 */
class OpenAIVisionClient implements VisionAnalysisContract
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('vision.openai.api_key', '');
        $this->model = (string) config('vision.openai.model', 'gpt-4o');
    }

    public function analyze(array $imageUrls, array $context = []): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('OpenAI API Key is missing');
        }

        // 1. Prepare Prompt with context (allowed slugs)
        $allowedSlugs = $context['allowed_slugs'] ?? [];
        $prompt = $this->buildPrompt($allowedSlugs);

        // 2. Prepare Images (URLs or Base64)
        $messages = [
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt] // context7-ignore
                ]
            ]
        ];

        foreach ($imageUrls as $url) {
            $messages[0]['content'][] = [
                'type' => 'image_url', // context7-ignore
                'image_url' => [
                    'url' => $url,
                    'detail' => config('vision.openai.detail')
                ]
            ];
        }

        // 3. Request OpenAI Vision API
        $response = Http::withToken($this->apiKey)
            ->timeout(config('vision.limits.timeout_seconds'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => config('vision.openai.max_tokens'),
                'temperature' => config('vision.openai.temperature'),
                'response_format' => ['type' => 'json_object'] // context7-ignore
            ]);

        if ($response->failed()) {
            throw new \Exception('OpenAI Vision API error: ' . $response->body());
        }

        $data = $response->json();
        $content = json_decode($data['choices'][0]['message']['content'], true);

        return [
            'suggestions' => $this->formatSuggestions($content['features'] ?? []),
            'signals' => $content['signals'] ?? [],
            'cost_estimate' => $this->calculateCost($data['usage'] ?? [])
        ];
    }

    protected function buildPrompt(array $allowedSlugs): string
    {
        $slugList = !empty($allowedSlugs) ? implode(', ', $allowedSlugs) : 'any relevant real estate features';
        
        return "Analyze these real estate photos and identify features from this list: [{$slugList}].
                Return strictly a JSON object with this format:
                {
                    \"features\": [
                        {\"slug\": \"slug-name\", \"confidence\": 0.95, \"reason\": \"User-facing reason\"}
                    ],
                    \"signals\": {\"style\": \"modern\", \"brightness\": \"high\", ...}
                }";
    }

    protected function formatSuggestions(array $features): array
    {
        return array_map(function($f) {
            return [
                'slug' => $f['slug'],
                'confidence' => (float) $f['confidence'],
                'reason' => $f['reason'],
                'source' => 'image',
                'source_reference' => 'OpenAI Vision'
            ];
        }, $features);
    }

    protected function calculateCost(array $usage): float
    {
        // Simple heuristic: $0.01 per 1k prompt tokens, $0.03 per 1k completion (GPT-4o est)
        $prompt = $usage['prompt_tokens'] ?? 0;
        $completion = $usage['completion_tokens'] ?? 0;
        
        return ($prompt * 0.00001) + ($completion * 0.00003);
    }
}
