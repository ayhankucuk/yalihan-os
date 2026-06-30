<?php

namespace App\Services\AI;

use App\Models\LeadActivity;
use Illuminate\Support\Facades\Log;

/**
 * Call Intelligence Service (Phase 13 - Epic 3)
 *
 * Handles proper orchestration of Audio Transcription (Whisper)
 * and Text Analysis (LLM).
 */
class CallIntelligenceService
{
    /**
     * Process a voice call recording.
     *
     * @param int $activityId LeadActivity ID
     * @param string $audioPath Path to audio file
     * @return array Analysis result
     */
    /**
     * Analyze a call recording.
     *
     * @param int $activityId LeadActivity ID
     * @param string|null $audioPath Path to audio file (optional)
     * @param string|null $transcript Existing transcript (optional)
     * @return array Analysis result structure
     */
    public function analyzeCall(int $activityId, ?string $audioPath = null, ?string $transcript = null): array
    {
        Log::info("CallIntelligence: Starting analysis for Activity #{$activityId}");

        // 1. Transcribe if needed
        if ($audioPath && !$transcript) {
            $transcript = $this->transcribeAudio($audioPath);
        }

        if (!$transcript) {
            Log::warning("CallIntelligence: No transcript available for Activity #{$activityId}");
            return ['success' => false, 'message' => 'No transcript available'];
        }

        // 2. Perform NLP Analysis
        $analysis = $this->generateAnalysis($transcript);

        // 3. Persist Logic (to be called by Controller/Job)
        // For now, return the data structure
        return [
            'activity_id' => $activityId,
            'transcript' => $transcript,
            'summary_short' => $analysis['summary_short'] ?? '',
            'summary_long' => $analysis['summary_long'] ?? '',
            'sentiment_score' => $analysis['sentiment_score'] ?? 5,
            'keywords' => $analysis['keywords'] ?? [],
            'missing_info' => $analysis['missing_info'] ?? [],
            'cost_usd' => $analysis['cost_usd'] ?? 0,
        ];
    }

    /**
     * Transcribe audio using OpenAI Whisper.
     */
    protected function transcribeAudio(string $audioPath): string
    {
        // Mockable extraction point
        // @see OpenAI Whisper — Phase 6 entegrasyonu
        return "Bu bir örnek çağrı metnidir. Müşteri daireyi beğendi ama fiyatı yüksek buldu.";
    }

    /**
     * Generate analysis using GPT-4o.
     */
    protected function generateAnalysis(string $transcript): array
    {
        $prompt = <<<EOT
Analiz et:
"{$transcript}"

Çıktı JSON formatında olsun:
{
    "summary_short": "3-4 maddelik bullet point",
    "summary_long": "Tek paragraf detaylı özet",
    "sentiment_score": 1-10 (1=Çok Negatif, 10=Çok Pozitif),
    "keywords": ["anahtar", "kelimeler"],
    "missing_info": ["fiyat", "lokasyon", "zaman"] (Eksik olanları listele)
}
EOT;

        // Mockable completion
        // @see OpenAI GPT-4o — Phase 6 entegrasyonu
        return [
            'summary_short' => "- Müşteri daireyi beğendi\n- Fiyat pazarlığı istiyor",
            'summary_long' => "Müşteri genel olarak daireyi beğendi ancak fiyatın piyasa ortalamasının üzerinde olduğunu düşünüyor. Tekrar arayacağını belirtti.",
            'sentiment_score' => 6,
            'keywords' => ['fiyat', 'pazarlık', 'daire'],
            'missing_info' => ['bütçe_net_değil'],
            'cost_usd' => 0.01 // Estimated
        ];
    }
}
