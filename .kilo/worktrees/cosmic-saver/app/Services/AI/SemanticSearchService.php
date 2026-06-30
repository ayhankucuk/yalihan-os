<?php

namespace App\Services\AI;

/**
 * @sab-ignore-catch
 */

use App\Models\Ilan;
use App\Models\IlanEmbedding; // Wait, I need to create this model too
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

/**
 * Semantic Search Service
 * 
 * Verilerin vektörel embeddinglerini oluşturur ve saklar.
 * Context7 Standardı: C7-SEMANTIC-SEARCH-SERVICE-2026-01-12
 */
class SemanticSearchService
{
    protected string $apiUrl;
    protected string $model = 'nomic-embed-text';

    public function __construct()
    {
        $this->apiUrl = config('ai.ollama_api_url', 'http://localhost:11434');
    }

    /**
     * Bir ilan için embedding oluşturur ve günceller
     */
    public function syncIlan(Ilan $ilan): bool
    {
        try {
            $text = $this->prepareTextForEmbedding($ilan);
            $embedding = $this->generateEmbedding($text);

            if (!$embedding) {
                return false;
            }

            // DB kaydı (Model kullanmadan direkt DB ile yapalım şimdilik)
            DB::table('ilan_embeddings')->updateOrInsert(
                ['ilan_id' => $ilan->id],
                [
                    'embedding' => json_encode($embedding),
                    'model_name' => $this->model,
                    'dimensions' => count($embedding),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            LogService::info('Semantic sync completed', ['ilan_id' => $ilan->id]);
            return true;

        } catch (\Exception $e) {
            LogService::error('Semantic sync failed', ['error' => $e->getMessage()], $e);
            return false;
        }
    }

    /**
     * Embedding oluşturur
     */
    public function generateEmbedding(string $text): ?array
    {
        try {
            $response = Http::timeout(30)->post($this->apiUrl . '/api/embeddings', [
                'model' => $this->model,
                'prompt' => $text,
            ]);

            if ($response->successful()) {
                return $response->json('embedding');
            }

            return null;
        } catch (\Exception $e) {
            LogService::error('Embedding generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * İlan verisinden anlamlı metin oluşturur
     */
    protected function prepareTextForEmbedding(Ilan $ilan): string
    {
        $parts = [
            $ilan->baslik,
            $ilan->aciklama,
            $ilan->kategori?->name,
            $ilan->il?->name,
            $ilan->ilce?->name,
            $ilan->mahalle?->name,
            $ilan->fiyat . ' ' . $ilan->para_birimi,
            // Özellikler eklenebilir
        ];

        return implode("\n", array_filter($parts));
    }

    /**
     * Semantik arama yapar (Cosine Similarity)
     * Not: MySQL'de bu işlem büyük veri setlerinde yavaştır. 
     * Local prototype için PHP tarafında similarity hesaplayacağız.
     */
    public function search(string $query, int $limit = 10): array
    {
        $queryEmbedding = $this->generateEmbedding($query);
        if (!$queryEmbedding) return [];

        $allEmbeddings = DB::table('ilan_embeddings')
            ->where('aktiflik_durumu', 1)
            ->get();

        $scores = [];
        foreach ($allEmbeddings as $row) {
            $vector = json_decode($row->embedding, true);
            $similarity = $this->cosineSimilarity($queryEmbedding, $vector);
            
            if ($similarity > 0.6) { // Threshold
                $scores[] = [
                    'ilan_id' => $row->ilan_id,
                    'score' => $similarity
                ];
            }
        }

        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
        
        return array_slice($scores, 0, $limit);
    }

    /**
     * Cosine Similarity calculation
     */
    protected function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        foreach ($vec1 as $i => $val) {
            if (!isset($vec2[$i])) continue;
            $dotProduct += $val * $vec2[$i];
            $normA += $val * $val;
            $normB += $vec2[$i] * $vec2[$i];
        }

        if ($normA == 0 || $normB == 0) return 0;
        
        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
