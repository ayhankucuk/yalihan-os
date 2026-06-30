<?php

namespace App\Services\CRM;

use App\Models\Lead;
use App\Repositories\LeadRepository;
use Illuminate\Support\Facades\Log;
use App\Traits\GuardsAgentWrites;

/**
 * LeadScoringService
 *
 * Calculates lead quality score (0-100) based on multiple factors
 * Hot (80-100), Warm (50-79), Cold (0-49)
 *
 * @governance PHASE4B_SERVICE_GOVERNANCE
 * @refactored 2026-05-12
 * @reason Migrated from direct model access to Repository Kernel pattern
 */
class LeadScoringService
{
    use GuardsAgentWrites;
    const HOT_THRESHOLD = 80;
    const WARM_THRESHOLD = 50;
    const COLD_THRESHOLD = 0;

    public function __construct(
        protected LeadRepository $leadRepository
    ) {}

    /**
     * Calculate overall lead score
     */
    public function calculateScore(Lead $lead): int
    {
        $score = 0;

        // 1. NLP Confidence Score (0-30 points)
        $score += $this->scoreConfidence($lead);

        // 2. Engagement Level (0-20 points)
        $score += $this->scoreEngagement($lead);

        // 3. Budget Qualification (0-15 points)
        $score += $this->scoreBudgetQualification($lead);

        // 4. Response Pattern (0-20 points)
        $score += $this->scoreResponsePattern($lead);

        // 5. Intent Strength (0-15 points)
        $score += $this->scoreIntent($lead);

        // Cap at 100
        $score = min($score, 100);

        return max($score, 0);
    }

    /**
     * Calculate lead temperature (hot/warm/cold)
     */
    public function getTemperature(int $score): string
    {
        if ($score >= self::HOT_THRESHOLD) {
            return 'hot';
        } elseif ($score >= self::WARM_THRESHOLD) {
            return 'warm';
        }
        return 'cold';
    }

    /**
     * Score confidence level (NLP accuracy)
     * 0-30 points
     */
    private function scoreConfidence(Lead $lead): int
    {
        $confidence = $lead->confidence ?? 0;
        return intval($confidence * 30);
    }

    /**
     * Score engagement level (messages, interactions)
     * 0-20 points
     */
    private function scoreEngagement(Lead $lead): int
    {
        $messageCount = $lead->messages()->count();
        $activityCount = $lead->activities()->count();

        // Scale: 0 msgs = 0, 1-3 msgs = 5, 4-6 msgs = 10, 7+ msgs = 20
        $messageScore = match (true) {
            $messageCount === 0 => 0,
            $messageCount <= 3 => 5,
            $messageCount <= 6 => 10,
            default => 20,
        };

        // Activity bonus: +2 per activity (up to 10)
        $activityBonus = min($activityCount * 2, 10);

        return min($messageScore + $activityBonus, 20);
    }

    /**
     * Score budget qualification
     * 0-15 points
     */
    private function scoreBudgetQualification(Lead $lead): int
    {
        $score = 0;

        // Budget range defined: +10 points
        if ($lead->budget_min && $lead->budget_max) {
            $score += 10;
        } elseif ($lead->budget_min || $lead->budget_max) {
            $score += 5;
        }

        // Area/room preferences: +5 points
        if ($lead->area_min || $lead->area_max || $lead->rooms) {
            $score += 5;
        }

        return min($score, 15);
    }

    /**
     * Score response pattern (how quickly they respond)
     * 0-20 points
     */
    private function scoreResponsePattern(Lead $lead): int
    {
        $lastContact = $lead->last_contacted_at;
        if (!$lastContact) {
            return 0;
        }

        $hoursSinceContact = now()->diffInHours($lastContact);

        // Fast responders (within 24 hours): 20 points
        if ($hoursSinceContact <= 24) {
            return 20;
        }
        // Medium responders (within 48 hours): 15 points
        elseif ($hoursSinceContact <= 48) {
            return 15;
        }
        // Slow responders (within 72 hours): 10 points
        elseif ($hoursSinceContact <= 72) {
            return 10;
        }
        // Very slow: 5 points
        else {
            return 5;
        }
    }

    /**
     * Score intent strength
     * 0-15 points
     */
    private function scoreIntent(Lead $lead): int
    {
        $intent = $lead->intent ?? null;

        $intentScores = [
            'buy' => 15,           // 가장 강한 의도
            'rent' => 12,          // 강한 의도
            'info_request' => 8,   // 중간 의도
            'price_check' => 5,    // 약한 의도
            'inquiry' => 5,        // 약한 의도
            'appointment' => 14,   // 매우 강한 의도
            'feedback' => 3,       // 매우 약함
        ];

        return $intentScores[$intent] ?? 0;
    }

    /**
     * Authoritative calculation and save trigger for basic scores.
     * Used by LeadAuthorityService to synchronize legacy/basic fields.
     */
    public function calculateAndSaveBasicScore(Lead $lead): void
    {
        $score = $this->calculateScore($lead);
        $temperature = $this->getTemperature($score);

        // We use quiet update here to avoid triggering duplicate events if managed by Authority
        $lead->update([
            'quality_score' => $score,
            'temperature' => $temperature,
        ]);

        Log::info('Lead basic score synchronized', [
            'lead_id' => $lead->id,
            'score' => $score
        ]);
    }

    /**
     * Get hot leads for proactive outreach
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses Repository Kernel instead of direct model access
     */
    public function getHotLeads(int $limit = 10)
    {
        return $this->leadRepository->getHotLeads(
            self::HOT_THRESHOLD,
            $limit,
            auth()->user()
        );
    }

    /**
     * Get warm leads for follow-up
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses Repository Kernel instead of direct model access
     */
    public function getWarmLeads(int $limit = 20)
    {
        return $this->leadRepository->getWarmLeads(
            self::WARM_THRESHOLD,
            self::HOT_THRESHOLD,
            $limit,
            auth()->user()
        );
    }
}
