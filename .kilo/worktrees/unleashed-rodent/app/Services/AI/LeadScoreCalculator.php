<?php

namespace App\Services\AI;

use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use App\Traits\GuardsAgentWrites;

/**
 * Lead Score Calculator (Phase 13 - Epic 3)
 *
 * Calculates lead temperature (hot/warm/cold) based on
 * interactions, profile data, and AI analysis.
 */
class LeadScoreCalculator
{
    use GuardsAgentWrites;
    // protected WinProbabilityService $winProbService;

    public function __construct(
        // WinProbabilityService $winProbService
    ) {
        // $this->winProbService = $winProbService;
    }

    /**
     * Calculate score for a lead.
     *
     * @param Lead $lead
     * @return array [score, label, reasoning]
     */
    public function calculate(Lead $lead): array
    {
        Log::info("LeadScore: Calculating for Lead #{$lead->id}");

        $score = 50; // Base score
        $reasons = [];

        // 1. Profile Completeness (Rule Based)
        if ($lead->phone) {
            $score += 10;
            $reasons[] = "+10 Telefon var";
        }
        if ($lead->email) {
            $score += 5;
            $reasons[] = "+5 Email var";
        }
        if ($lead->budget_max > 0) {
            $score += 15;
            $reasons[] = "+15 Bütçe belli";
        }
        if ($lead->interested_location_id) {
            $score += 10;
            $reasons[] = "+10 Lokasyon belli";
        }

        // 2. Activity / AI Score (Mocked for now, implies fetching recent call analysis)
        // In real impl, we would query LeadActivity -> CallAnalysis
        // $avgSentiment = $lead->activities()->avg('sentiment_score');
        $avgSentiment = 7; // Mock

        if ($avgSentiment >= 8) {
            $score += 20;
            $reasons[] = "+20 Yüksek AI Duygu Skoru";
        } elseif ($avgSentiment <= 3) {
            $score -= 20;
            $reasons[] = "-20 Düşük AI Duygu Skoru";
        }

        // Clamp 0-100
        $score = max(0, min(100, $score));

        // Label
        $label = 'Soğuk';
        if ($score >= 80) $label = 'Sıcak';
        elseif ($score >= 50) $label = 'Ilık';

        return [
            'score' => $score,
            'label' => $label,
            'reasoning' => implode(', ', $reasons),
            'avg_sentiment' => $avgSentiment, // Return sentiment for WinProb use
        ];
    }

    /**
     * Update lead tags based on score and insights.
     */
    public function updateTags(Lead $lead, array $insights = []): void
    {
        $this->blockAgentWrite(__FUNCTION__);

        $tags = $lead->tags ?? []; // Assuming cast to array in model or manual decode

        // 1. Tag based on Budget
        if ($lead->budget_max >= 10000000) {
            if (!in_array('Yüksek Bütçe', $tags)) $tags[] = 'Yüksek Bütçe';
        }

        // 2. Tag based on Intent
        if ($lead->intent === 'invest') {
            if (!in_array('Yatırımcı', $tags)) $tags[] = 'Yatırımcı';
        }

        // 3. Tag based on Urgent insight
        if (in_array('Acil', $insights)) {
            if (!in_array('Acil', $tags)) $tags[] = 'Acil';
        }

        $lead->tags = $tags; // Will be cast to JSON if model supports it
        $lead->save();
    }

    /**
     * Calculate and save score to database.
     */
    public function calculateAndSave(Lead $lead): \App\Models\AILeadScore
    {
        $result = $this->calculate($lead);
        $this->updateTags($lead);

        // Win Probability
        $winProb = 50; // $this->winProbService->calculate($lead, $result['avg_sentiment']);

        return \App\Models\AILeadScore::updateOrCreate(
            ['lead_id' => $lead->id],
            [
                'skor_degeri' => $result['score'],
                'skor_etiketi' => $result['label'],
                'skor_nedeni' => $result['reasoning'],
                'win_probability' => $winProb,
                'hesaplama_tarihi' => now(),
            ]
        );
    }
}
