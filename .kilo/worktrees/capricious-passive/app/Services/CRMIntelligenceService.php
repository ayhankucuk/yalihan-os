<?php

namespace App\Services;

/**
 * @sab-ignore-catch
 */

use App\Models\Lead;
use App\Models\Kisi;
use App\Models\Ilan;
use App\Models\LeadEmbedding;
use App\Services\AI\SemanticSearchService;
use App\Services\AI\EmbeddingService;
use Illuminate\Support\Facades\DB;
use App\Services\Logging\LogService;

/**
 * CRM Intelligence Service
 *
 * Bridging CRM data (Leads/Contacts) with AI power for smart matching and scoring.
 * Context7 Standardı: C7-CRM-INTELLIGENCE-SERVICE-2026-01-13
 */
class CRMIntelligenceService
{
    protected SemanticSearchService $semanticSearch;
    protected EmbeddingService $embeddingService;

    public function __construct(SemanticSearchService $semanticSearch, EmbeddingService $embeddingService)
    {
        $this->semanticSearch = $semanticSearch;
        $this->embeddingService = $embeddingService;
    }

    /**
     * Sync lead or person to intelligence system (generate embedding)
     */
    public function syncLead(Lead|Kisi $target): bool
    {
        try {
            $text = $this->prepareTextForLead($target);
            $embedding = $this->semanticSearch->generateEmbedding($text);

            if (!$embedding) {
                return false;
            }

            $idField = $target instanceof Lead ? 'lead_id' : 'kisi_id';

            LeadEmbedding::updateOrInsert(
                [$idField => $target->id],
                [
                    'embedding' => json_encode($embedding),
                    'model_name' => 'nomic-embed-text',
                    'dimensions' => count($embedding),
                    'updated_at' => now(),
                    'created_at' => now(),
                    'aktiflik_durumu' => 1
                ]
            );

            LogService::info(($target instanceof Lead ? 'Lead' : 'Kisi') . ' semantic sync completed', ['id' => $target->id]);
            return true;
        } catch (\Exception $e) {
            LogService::error('Intelligence sync failed', ['error' => $e->getMessage()], $e);
            return false;
        }
    }

    /**
     * Get recommended listings for a lead or person using semantic similarity
     */
    public function getRecommendedListings(Lead|Kisi $target, int $limit = 5): array
    {
        $targetEmbedding = $target->embedding;

        if (!$targetEmbedding || !$targetEmbedding->embedding) {
            // Auto-sync if missing
            $this->syncLead($target);
            $target->load('embedding');
            $targetEmbedding = $target->embedding;
        }

        if (!$targetEmbedding) return [];

        $queryEmbedding = is_array($targetEmbedding->embedding)
            ? $targetEmbedding->embedding
            : json_decode($targetEmbedding->embedding, true);

        // Fetch all active ilan embeddings
        $ilanEmbeddings = DB::table('ilan_embeddings')
            ->where('aktiflik_durumu', 1)
            ->get();

        $recommendations = [];
        foreach ($ilanEmbeddings as $ilanEmb) {
            $vector = json_decode($ilanEmb->embedding, true);
            $similarity = $this->calculateSimilarity($queryEmbedding, $vector);

            if ($similarity > 0.65) { // Intelligence threshold
                $recommendations[] = [
                    'ilan_id' => $ilanEmb->ilan_id,
                    'score' => $similarity,
                    'is_semantic' => true
                ];
            }
        }

        // Sort by similarity score
        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Calculate a priority score for the lead or person (0-100)
     */
    public function calculateLeadPriority(Lead|Kisi $target): int
    {
        $score = 0;

        // 1. Completeness Score (0-30)
        if ($target instanceof Lead) {
            if ($target->email) $score += 10;
            if ($target->phone) $score += 10;
            if ($target->interested_property_type) $score += 10;
        } else {
            if ($target->email) $score += 10;
            if ($target->telefon) $score += 10;
            if ($target->kisi_tipi) $score += 10;
        }

        // 2. Intent/Budget Context (0-30)
        if ($target instanceof Lead) {
            if ($target->budget_max > 10000000) $score += 30;
            elseif ($target->budget_max > 5000000) $score += 20;
        } else {
            // Check latest talep for Kisi
            $latestTalep = $target->talepler()->latest()->first();
            if ($latestTalep) {
                if ($latestTalep->max_fiyat > 10000000) $score += 30;
                elseif ($latestTalep->max_fiyat > 5000000) $score += 20;
            }
        }

        // 3. Match Potential Score (0-40)
        $matches = $this->getRecommendedListings($target, 3);
        if (count($matches) > 0) {
            $score += min(count($matches) * 13, 40);
        }

        return min($score, 100);
    }

    /**
     * Prepare descriptive text from data
     */
    protected function prepareTextForLead(Lead|Kisi $target): string
    {
        if ($target instanceof Lead) {
            $parts = [
                $target->intent,
                $target->interested_property_type,
                $target->notes,
                $target->first_message,
                "Bütçe: " . $target->budget_min . " - " . $target->budget_max,
            ];
        } else {
            $parts = [
                $target->tam_ad,
                $target->kisi_tipi instanceof \BackedEnum ? $target->kisi_tipi->value : $target->kisi_tipi,
                $target->notlar,
            ];

            // Add latest demands if available
            $target->talepler->take(3)->each(function ($talep) use (&$parts) {
                $parts[] = "Talep: " . $talep->baslik . " " . $talep->aciklama;
            });
        }

        return implode("\n", array_filter($parts));
    }

    /**
     * Internal similarity bridge
     */
    protected function calculateSimilarity(array $vec1, array $vec2): float
    {
        // Simple cosine similarity implementation
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
