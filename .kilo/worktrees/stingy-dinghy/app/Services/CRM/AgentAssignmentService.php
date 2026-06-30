<?php

namespace App\Services\CRM;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * AgentAssignmentService
 *
 * Intelligently assigns leads to agents based on:
 * - Round-robin load balancing
 * - Agent availability
 * - Expertise matching (property type, location)
 * - Current workload
 */
class AgentAssignmentService
{
    const ASSIGNMENT_STRATEGY = 'round-robin'; // or 'load-based', 'expertise-based'

    public function __construct(
        protected \App\Services\CRM\LeadAuthorityService $leadAuthority
    ) {}

    /**
     * Assign lead to best available agent
     */
    public function assignLead(Lead $lead): ?User
    {
        try {
            // Get all active agents
            $agents = $this->getActiveAgents();

            if ($agents->isEmpty()) {
                Log::warning('No active agents available for assignment', [
                    'lead_id' => $lead->id,
                ]);
                return null;
            }

            // Use assignment strategy
            $agent = match (self::ASSIGNMENT_STRATEGY) {
                'load-based' => $this->assignByLowestLoad($agents, $lead),
                'expertise-based' => $this->assignByExpertise($agents, $lead),
                default => $this->assignByRoundRobin($agents),
            };

            if ($agent) {
                // DELEGATION: Route to centralized LeadAuthorityService
                $this->leadAuthority->assignLeadToAgent($lead, $agent->id);

                Log::info('Lead assigned to agent', [
                    'lead_id' => $lead->id,
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'strategy' => self::ASSIGNMENT_STRATEGY,
                ]);
            }

            return $agent;
        } catch (\Exception $e) {
            Log::error('Lead assignment failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get all active agents
     */
    private function getActiveAgents()
    {
        return User::active()
            ->where('role', 'agent')
            ->orWhere('role', 'admin')
            ->get();
    }

    /**
     * Round-robin assignment (simple load distribution)
     */
    private function assignByRoundRobin($agents): User
    {
        // Get agent with fewest assigned leads
        return $agents->sortBy(function ($agent) {
            return $agent->assignedLeads()->count();
        })->first();
    }

    /**
     * Load-based assignment (lowest current workload)
     */
    private function assignByLowestLoad($agents, Lead $lead): User
    {
        return $agents->sortBy(function ($agent) {
            $assignedCount = $agent->assignedLeads()->count();
            $hotCount = $agent->assignedLeads()
                ->where('temperature', 'hot')
                ->count();

            // Weighted score: 2x hot leads
            return $assignedCount + ($hotCount * 2);
        })->first();
    }

    /**
     * Expertise-based assignment (property type & location matching)
     */
    private function assignByExpertise($agents, Lead $lead): User
    {
        $bestAgent = null;
        $bestScore = -1;

        foreach ($agents as $agent) {
            $score = 0;

            // Check agent expertise
            $agentExpertise = $agent->agentProfile?->expertise ?? [];

            // Property type matching: +20 points
            if (in_array($lead->interested_property_type, $agentExpertise['property_types'] ?? [])) {
                $score += 20;
            }

            // Location matching: +15 points
            if (in_array($lead->interested_location_id, $agentExpertise['locations'] ?? [])) {
                $score += 15;
            }

            // Availability: +10 points
            if ($this->isAgentAvailable($agent)) {
                $score += 10;
            }

            // Workload bonus: Fewer leads = higher score (up to 10 points)
            $leadCount = $agent->assignedLeads()->count();
            $workloadScore = max(0, 10 - intval($leadCount / 5));
            $score += $workloadScore;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestAgent = $agent;
            }
        }

        return $bestAgent ?? $this->assignByRoundRobin($agents);
    }

    /**
     * Check if agent is available (not on vacation, working hours, etc)
     */
    private function isAgentAvailable(User $agent): bool
    {
        // Check if agent has vacation
        if ($agent->onVacation()) {
            return false;
        }

        // Check working hours (can be extended based on business logic)
        $hour = now()->hour;
        return $hour >= 9 && $hour < 18; // 9 AM - 6 PM
    }

    /**
     * Reassign lead to different agent
     */
    public function reassignLead(Lead $lead, User $newAgent): bool
    {
        try {
            $oldAgent = $lead->agent;

            // DELEGATION: Route to centralized LeadAuthorityService
            $this->leadAuthority->assignLeadToAgent($lead, $newAgent->id);

            Log::info('Lead reassigned', [
                'lead_id' => $lead->id,
                'old_agent_id' => $oldAgent?->id,
                'new_agent_id' => $newAgent->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Lead reassignment failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get agent workload summary
     */
    public function getAgentWorkloadSummary(User $agent): array
    {
        $totalLeads = $agent->assignedLeads()->count();
        $hotLeads = $agent->assignedLeads()->where('temperature', 'hot')->count();
        $warmLeads = $agent->assignedLeads()->where('temperature', 'warm')->count();
        $coldLeads = $totalLeads - $hotLeads - $warmLeads;

        return [
            'agent_id' => $agent->id,
            'agent_name' => $agent->name,
            'total_leads' => $totalLeads,
            'hot_leads' => $hotLeads,
            'warm_leads' => $warmLeads,
            'cold_leads' => $coldLeads,
            'workload_percentage' => intval(($totalLeads / 50) * 100), // Assume 50 leads = 100%
            'priority_leads' => $hotLeads + $warmLeads,
        ];
    }

    /**
     * Get team-wide assignment statistics
     */
    public function getTeamStatistics()
    {
        $agents = $this->getActiveAgents();

        $summary = [
            'total_agents' => $agents->count(),
            'total_assigned_leads' => 0,
            'agents' => [],
        ];

        foreach ($agents as $agent) {
            $workload = $this->getAgentWorkloadSummary($agent);
            $summary['agents'][] = $workload;
            $summary['total_assigned_leads'] += $workload['total_leads'];
        }

        return $summary;
    }
}
