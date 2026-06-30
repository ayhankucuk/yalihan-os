<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Hybrid Embedding Service
 *
 * Context7 Standardı: C7-HYBRID-EMBEDDING-2026-01-13
 *
 * Supports both OpenAI and Ollama (Local) embeddings.
 * Used for RAG (Retrieval Augmented Generation) and Semantic Search.
 */
class EmbeddingService
{
    private string $defaultProvider = 'openai';
    private AiCostGuardService $costGuard;

    private array $providers = [
        'openai' => [
            'model' => 'text-embedding-3-small',
            'base_url' => 'https://api.openai.com/v1',
            'dimensions' => 1536
        ],
        'ollama' => [
            'model' => 'nomic-embed-text', // Default local model
            'base_url' => 'http://localhost:11434',
            'dimensions' => 768
        ]
    ];

    public function __construct(AiCostGuardService $costGuard)
    {
        $this->costGuard = $costGuard;
        // Load configs
        $this->providers['openai']['api_key'] = config('services.openai.api_key');
        // Check if we have an Ollama URL config, otherwise use default
        $this->providers['ollama']['base_url'] = config('services.ollama.url', 'http://localhost:11434');

        // Determine active provider
        // Priority: Config > Environment > Default (OpenAI)
        // If we want to force local for privacy, we can set default to ollama
        $this->defaultProvider = config('services.ai.embedding_provider', 'openai');
    }

    /**
     * Get embedding vector for text
     */
    public function getEmbedding(string $text, ?string $cacheKey = null, ?string $provider = null): ?array
    {
        $provider = $provider ?? $this->defaultProvider;
        $config = $this->providers[$provider] ?? $this->providers['openai'];

        // Validate text
        $text = trim($text);
        if (empty($text) || strlen($text) < 3) return null;

        // Check Cache
        if ($cacheKey) {
            $cached = Cache::get($cacheKey . '_' . $provider);
            if ($cached) return $cached;
        }

        // 🛡️ Cost Guard — embedding provider bypass prevention
        $budget = $this->costGuard->checkBudget($provider);
        if (!$budget['allowed']) {
            Log::warning('EmbeddingService: bütçe sınırı, embedding atlandı', ['provider' => $provider, 'hata_mesaji' => $budget['reason'] ?? $provider]);
            return null;
        }

        try {
            if ($provider === 'ollama') {
                $embedding = $this->getOllamaEmbedding($text, $config);
            } else {
                $embedding = $this->getOpenAIEmbedding($text, $config);
            }

            if ($embedding && $cacheKey) {
                // Cache for 30 days
                Cache::put($cacheKey . '_' . $provider, $embedding, 86400 * 30);
            }

            return $embedding;

        } catch (\Exception $e) {
            Log::error("Embedding failed ($provider)", ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function getOpenAIEmbedding(string $text, array $config): ?array
    {
        if (empty($config['api_key'])) return null;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config['api_key'],
            'Content-Type' => 'application/json',
        ])->post($config['base_url'] . '/embeddings', [
            'model' => $config['model'],
            'input' => $this->preprocessText($text),
        ]);

        if ($response->failed()) {
            Log::error('OpenAI Embedding Error', ['body' => $response->body()]);
            return null;
        }

        return $response->json('data.0.embedding');
    }

    private function getOllamaEmbedding(string $text, array $config): ?array
    {
        // Ollama API: POST /api/embeddings
        $url = $config['base_url'] . '/api/embeddings';

        $response = Http::post($url, [
            'model' => $config['model'],
            'prompt' => $text,
        ]);

        if ($response->failed()) {
            Log::error('Ollama Embedding Error', ['body' => $response->body()]);
            return null;
        }

        return $response->json('embedding');
    }

    /**
     * Get embedding for ilan (listing)
     */
    public function getIlanEmbedding(\App\Models\Ilan $ilan): ?array
    {
        // Create a rich text representation of the listing
        $text = implode(' ', array_filter([
            $ilan->baslik,
            $ilan->aciklama,
            $ilan->kategori?->name,
            $ilan->ilce?->ilce_adi,
            $ilan->mahalle?->mahalle_adi,
            "Fiyat: " . number_format($ilan->fiyat, 0, ',', '.') . " " . $ilan->para_birimi,
            "Oda: " . $ilan->oda_sayisi,
            "Metrekare: " . $ilan->brut_m2 . " m2",
        ]));

        return $this->getEmbedding($text, "ilan_{$ilan->id}_embedding");
    }

    /**
     * Get embedding for talep (demand)
     */
    public function getTalepEmbedding(\App\Models\Talep $talep): ?array
    {
        $text = implode(' ', array_filter([
            $talep->baslik,
            $talep->aciklama,
            $talep->ilce?->ilce_adi,
            "Bütçe: " . $talep->min_fiyat . "-" . $talep->max_fiyat,
        ]));

        return $this->getEmbedding($text, "talep_{$talep->id}_embedding");
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    public function cosineSimilarity(array $vec1, array $vec2): float
    {
        $count = count($vec1);
        if ($count !== count($vec2) || $count === 0) return 0.0;

        $dot = 0.0;
        $mag1 = 0.0;
        $mag2 = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $dot += $vec1[$i] * $vec2[$i];
            $mag1 += $vec1[$i] * $vec1[$i];
            $mag2 += $vec2[$i] * $vec2[$i];
        }

        if ($mag1 * $mag2 == 0) return 0.0;

        return $dot / (sqrt($mag1) * sqrt($mag2));
    }

    private function preprocessText(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', $text);
        return substr(trim($text), 0, 30000);
    }
}
