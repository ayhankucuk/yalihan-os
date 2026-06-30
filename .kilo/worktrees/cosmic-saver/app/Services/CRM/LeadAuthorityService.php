<?php

namespace App\Services\CRM;

use App\Models\Lead;
use App\Models\AILeadScore;
use App\Services\AI\LeadScoreCalculator;
use App\Services\AI\NextActionRecommender;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LeadAuthorityService
 *
 * Central authority for Lead lifecycle and intelligence coordination.
 * Enforces SAB v24.0 standards by consolidating fragmented Lead logic.
 */
class LeadAuthorityService
{
    use GuardsAgentWrites;

    public function __construct(
        private readonly LeadScoringService $scoringService,
        private readonly LeadScoreCalculator $aiCalculator,
        private readonly NextActionRecommender $recommender
    ) {}

    /**
     * @deprecated Use LeadRepository->getLeads() instead. Left for backward compatibility.
     * Get a paginated list of leads with optional filtering.
     */
    public function getLeads(array $filters = [], int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        /** @var \App\Repositories\LeadRepository $repo */
        $repo = app(\App\Repositories\LeadRepository::class);
        return $repo->getLeads($filters, $perPage);
    }

    /**
     * Get an enriched Lead with guaranteed up-to-date intelligence.
     * Replaces shadow logic in controllers and services.
     */
    public function getEnrichedLead(Lead $lead): array
    {
        // 1. Get current AI score or trigger refresh if missing
        $score = $this->ensureScoreExists($lead);

        // 2. Get recommendations based on current intelligence
        $recommendation = $this->recommender->recommend($lead, $score);

        return [
            'lead' => $lead->load(['activities', 'messages']),
            'score' => $score,
            'recommendation' => $recommendation
        ];
    }

    /**
     * Refresh intelligence (scoring) for a lead.
     */
    public function refreshIntelligence(Lead $lead): AILeadScore
    {
        $this->blockAgentWrite(__FUNCTION__);

        return DB::transaction(function () use ($lead) {
            // Calculate and save via the AI calculator (which handles the DB model)
            $score = $this->aiCalculator->calculateAndSave($lead);

            // Also update the internal Lead scoring (legacy/consolidated fields if any)
            $this->scoringService->calculateAndSaveBasicScore($lead);

            Log::info("Lead intelligence refreshed via Authority", ['lead_id' => $lead->id]);

            return $score;
        });
    }

    /**
     * Authoritative status transition for Leads with telemetry.
     */
    public function updateStatus(Lead $lead, int|string $newStatus, string $trigger = 'manual'): void
    {
        $this->blockAgentWrite(__FUNCTION__);

        $oldStatus = $lead->crm_durumu;

        // P0-A FIX: Setter on model or manual handling to ensure int conversion
        $lead->crm_durumu = $newStatus;
        $lead->save();

        // Status change triggers an automatic intelligence refresh
        $this->refreshIntelligence($lead);

        Log::info("Lead status updated via Authority", [
            'lead_id' => $lead->id,
            'action' => 'status_transition',
            'trigger' => $trigger,
            'from_status' => $oldStatus,
            'to_status' => $lead->crm_durumu, // Log the int value after setter conversion
        ]);
    }

    /**
     * Register or update a lead from external sources (Webhooks).
     * Consolidates logic from LeadService.
     */
    public function registerLeadFromExternalSource(
        string $platform,
        string $platformUserId,
        string $messageText,
        array $nlpResult,
        array $meta = []
    ): Lead {
        $this->blockAgentWrite(__FUNCTION__);

        return DB::transaction(function () use ($platform, $platformUserId, $messageText, $nlpResult, $meta) {
            // 1. Find or create lead
            $lead = Lead::firstOrCreate( // governance-bypass: LeadAuthorityService IS the single write authority
                [
                    'platform' => $platform,
                    'platform_user_id' => $platformUserId,
                ],
                [
                    'name' => $meta['name'] ?? ($nlpResult['entities']['person_name'] ?? null),
                    'crm_durumu' => Lead::CRM_NEW, // governance-bypass: model constant reference
                    'aktif' => true,
                    'first_message' => $messageText,
                    'platform_phone' => $meta['phone'] ?? null,
                    'platform_username' => $meta['username'] ?? null,
                ]
            );

            $isNew = $lead->wasRecentlyCreated;
            $oldConfidence = $lead->confidence;

            // 2. Authoritative Mutation
            $lead->update([
                'intent' => $nlpResult['intent'] ?? $lead->intent,
                'confidence' => $nlpResult['confidence'] ?? $lead->confidence,
                'interested_property_type' => $nlpResult['entities']['property_type'] ?? $lead->interested_property_type,
                'interested_location_id' => $nlpResult['entities']['location_id'] ?? $lead->interested_location_id,
                'budget_min' => $nlpResult['entities']['price_min'] ?? $lead->budget_min,
                'budget_max' => $nlpResult['entities']['price_max'] ?? $lead->budget_max,
                'entities' => json_encode($nlpResult['entities'] ?? []),
            ]);

            // 3. Record the interaction
            $this->recordInteraction($lead, 'message_received', "Message from {$platform}: " . mb_substr($messageText, 0, 100));

            // 4. Intelligence Refresh (Authoritative side effect)
            $this->refreshIntelligence($lead);

            Log::info($isNew ? "Lead registered via Authority" : "Lead updated via Authority", [
                'lead_id' => $lead->id,
                'action' => $isNew ? 'register' : 'sync',
                'source' => $platform,
                'confidence_before' => $oldConfidence,
                'confidence_after' => $lead->confidence,
                'intent' => $nlpResult['intent'] ?? $lead->intent,
            ]);

            return $lead;
        });
    }

    /**
     * Assign lead to an agent.
     */
    public function assignLeadToAgent(Lead $lead, int $agentId, string $trigger = 'manual'): void
    {
        $this->blockAgentWrite(__FUNCTION__);

        DB::transaction(function () use ($lead, $agentId, $trigger) {
            $oldAgentId = $lead->assigned_agent_id;
            $lead->update(['assigned_agent_id' => $agentId]);

            $this->recordInteraction($lead, 'lead_assigned', "Lead assigned to agent ID: {$agentId}");

            // Post-assignment intelligence trigger
            $this->refreshIntelligence($lead);

            Log::info("Lead assigned via Authority", [
                'lead_id' => $lead->id,
                'action' => 'assignment',
                'trigger' => $trigger,
                'from_agent' => $oldAgentId,
                'to_agent' => $agentId,
            ]);
        });
    }

    /**
     * Record an activity / interaction for a lead.
     */
    public function recordInteraction(Lead $lead, string $type, string $description): void
    {
        $this->blockAgentWrite(__FUNCTION__);

        $lead->activities()->create([
            'activity_type' => $type,
            'description' => $description,
            'activity_date' => now(),
        ]);
    }

    /**
     * Evaluate a lead against qualification policies.
     * Centralizes the "what makes a lead qualified" decision.
     */
    public function evaluateAndQualify(Lead $lead): bool
    {
        $oldStatus = $lead->crm_durumu;

        // POLICY: Intent must exist + Confidence threshold (0.65)
        if (!$lead->intent || $lead->confidence < 0.65) {
            return false;
        }

        // POLICY: Must have either location or property type
        if (!$lead->interested_location_id && !$lead->interested_property_type) {
            return false;
        }

        // If already qualified or won, we don't need to re-qualify
        if ($lead->crm_durumu === Lead::CRM_QUALIFIED || $lead->crm_durumu === Lead::CRM_WON) {
            return true;
        }

        // Side Effect: Transition to Qualified
        DB::transaction(function () use ($lead, $oldStatus) {
            $this->updateStatus($lead, Lead::CRM_QUALIFIED, 'policy_evaluation');

            $this->recordInteraction(
                $lead,
                'auto_qualified',
                'System: Lead auto-qualified based on high intent and confidence.'
            );

            // Update notes without direct Lead::update bypass
            $lead->notes = ($lead->notes ? $lead->notes . "\n\n" : "") . "Auto-qualified via Authority logic.";
            $lead->save();

            Log::info("Lead qualified via Authority Policy", [
                'lead_id' => $lead->id,
                'action' => 'qualification',
                'from_status' => $oldStatus,
                'to_status' => Lead::CRM_QUALIFIED,
                'confidence' => $lead->confidence,
            ]);
        });

        return true;
    }

    /**
     * Synchronize lead intelligence from new NLP results (e.g. from a new message).
     * Enforces the "Confidence Policy" (only update if higher).
     */
    public function syncIntelligenceFromNlp(Lead $lead, array $nlpResult): void
    {
        DB::transaction(function () use ($lead, $nlpResult) {
            $oldConfidence = $lead->confidence;
            $updates = [];

            // POLICY: Only update confidence/intent if the new result is stronger
            if (($nlpResult['confidence'] ?? 0) > $lead->confidence) {
                $updates['confidence'] = $nlpResult['confidence'];
                $updates['intent'] = $nlpResult['intent'] ?? $lead->intent;
            }

            // Entity Sync Policy: Update specific fields if they are missing or new ones found
            if (!empty($nlpResult['entities'])) {
                $updates['interested_property_type'] = $nlpResult['entities']['property_type'] ?? $lead->interested_property_type;
                $updates['interested_location_id'] = $nlpResult['entities']['location_id'] ?? $lead->interested_location_id;
                $updates['budget_min'] = $nlpResult['entities']['price_min'] ?? $lead->budget_min;
                $updates['budget_max'] = $nlpResult['entities']['price_max'] ?? $lead->budget_max;
            }

            if (!empty($updates)) {
                $lead->update($updates);
                $this->refreshIntelligence($lead);

                Log::info("Lead intelligence synced via Authority", [
                    'lead_id' => $lead->id,
                    'action' => 'intelligence_sync',
                    'confidence_before' => $oldConfidence,
                    'confidence_after' => $lead->confidence,
                    'updated_fields' => array_keys($updates),
                ]);
            }
        });
    }

    /**
     * Internal helper to ensure score consistency.
     */
    private function ensureScoreExists(Lead $lead): AILeadScore
    {
        $score = AILeadScore::where('lead_id', $lead->id)->first();

        if (!$score) {
            return $this->refreshIntelligence($lead);
        }

        return $score;
    }
}
