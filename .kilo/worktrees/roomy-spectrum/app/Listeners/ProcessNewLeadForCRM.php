<?php

namespace App\Listeners;

use App\Events\LeadOlusturuldu;
use App\Services\CRM\AgentAssignmentService;
use App\Services\CRM\FollowUpAutomationService;
use Illuminate\Support\Facades\Log;

/**
 * ProcessNewLeadForCRM
 *
 * Automatically processes newly created leads:
 * 1. Calculate quality score
 * 2. Assign to agent
 * 3. Schedule follow-up tasks
 */
class ProcessNewLeadForCRM
{
    protected AgentAssignmentService $assignmentService;
    protected FollowUpAutomationService $followUpService;

    public function __construct(
        AgentAssignmentService $assignmentService,
        FollowUpAutomationService $followUpService
    ) {
        $this->assignmentService = $assignmentService;
        $this->followUpService = $followUpService;
    }

    /**
     * Handle the event.
     */
    public function handle(LeadOlusturuldu $event): void
    {
        $lead = $event->lead;

        try {
            // Step 1: Calculate quality score (Removed)
            Log::info('Step 1: Omitted lead quality score calculation', [
                'lead_id' => $lead->id,
            ]);

            // Step 2: Assign to agent
            Log::info('Step 2: Assigning lead to agent', [
                'lead_id' => $lead->id,
            ]);
            $agent = $this->assignmentService->assignLead($lead);

            if ($agent) {
                Log::info('Lead assigned', [
                    'lead_id' => $lead->id,
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                ]);
            } else {
                Log::warning('No agent available for assignment', [
                    'lead_id' => $lead->id,
                ]);
            }

            // Step 3: Schedule follow-up tasks
            Log::info('Step 3: Scheduling follow-up tasks', [
                'lead_id' => $lead->id,
            ]);
            $this->followUpService->scheduleFollowUp($lead);

            Log::info('New lead processing completed', [
                'lead_id' => $lead->id,
                'temperature' => $lead->temperature,
                'score' => $lead->quality_score,
                'assigned_agent' => $agent?->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process new lead for CRM', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
