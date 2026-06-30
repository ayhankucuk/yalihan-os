<?php

namespace App\Services\AI;

use App\Models\Ilan;
use Illuminate\Support\Collection;

/**
 * 🏢 SAB SEALED
 * AI Advisor Command Center Service
 *
 * Central orchestration layer combining multiple AI services into a unified dashboard.
 * Does NOT contain core business logic; delegates to existing services and standardizes output.
 */
class AdvisorCommandCenterService
{
    public function __construct(
        private DealRadarService $dealRadarService,
        private OpportunityEngineService $opportunityService,
        private PortfolioDoctorService $portfolioDoctorService,
        private BuyerMatchQueueService $buyerMatchService
    ) {}

    /**
     * Get the unified command center payload.
     */
    public function getCommandCenterData(array $filters = []): array
    {
        $hotDeals = $this->dealRadarService->getRadarListings($filters);

        $opportunities = $this->opportunityService->getOpportunities($filters)->toArray();

        $portfolioHealth = $this->portfolioDoctorService->analyzePortfolio($filters);

        // Buyer matches requires a listing context. We will grab recent active listings to generate a global snapshot.
        $buyerMatches = $this->buildBuyerMatchPanel();

        // Pass all module raw data to build unified KPIs and Actions
        $modules = [
            'deal_radar' => $hotDeals,
            'opportunity_engine' => $opportunities,
            'portfolio_doctor' => $portfolioHealth,
            'buyer_match' => $buyerMatches
        ];

        return [
            'kpis' => $this->buildKpiSummary($modules),
            'hot_deals' => $hotDeals,
            'opportunities' => $opportunities,
            'portfolio_health' => $portfolioHealth,
            'buyer_matches' => $buyerMatches,
            'priority_actions' => $this->buildPriorityActions($modules, $filters),
        ];
    }

    /**
     * Build the KPI strip based on cross-module data.
     */
    private function buildKpiSummary(array $modules): array
    {
        $criticalPortfolioIssues = collect($modules['portfolio_doctor'])
            ->whereIn('primary_problem', ['OVERPRICED', 'NO_BUYER_MATCH', 'HIGH_DEMAND_LOW_CONVERSION'])
            ->count();

        $highIntentBuyers = collect($modules['buyer_match'])
            ->whereIn('urgency_signal', ['HIGH_INTENT', 'AT_RISK'])
            ->count();

        $allActions = $this->buildPriorityActions($modules);
        $todayPriorityActions = collect($allActions)
            ->whereIn('execution_priority', ['CRITICAL', 'HIGH'])
            ->count();

        return [
            'total_hot_deals' => collect($modules['deal_radar'])->whereIn('deal_tier', ['HOT_DEAL', 'FAST_MOVING'])->count(),
            'total_opportunities' => count($modules['opportunity_engine']),
            'critical_portfolio_issues' => $criticalPortfolioIssues,
            'high_intent_buyers' => $highIntentBuyers,
            'today_priority_actions' => $todayPriorityActions,
        ];
    }

    /**
     * Gather buyer matches globally by scanning active portfolio listings.
     */
    private function buildBuyerMatchPanel(): array
    {
        // SoftDeletes trait on Ilan automatically filters deleted records
        $listings = Ilan::where('yayin_durumu', 'yayinda')->take(10)->get();

        $globalMatches = [];

        foreach ($listings as $listing) {
            $queue = $this->buyerMatchService->getMatchesForQueue($listing, 5); // top 5 per listing

            if (isset($queue['matches']) && !empty($queue['matches'])) {
                foreach ($queue['matches'] as $match) {
                    $globalMatches[] = array_merge($match, [
                        // Injecting listing context directly into the match array for the global view
                        'listing_id' => $queue['listing']['id'],
                        'listing_title' => $queue['listing']['title'],
                    ]);
                }
            }
        }

        // Sort globally by score descending
        usort($globalMatches, fn($a, $b) => $b['match_score'] <=> $a['match_score']);

        return $globalMatches;
    }

    /**
     * Aggregate and normalize suggested actions from all modules into a single prioritized list.
     */
    private function buildPriorityActions(array $modules, array $filters = []): array
    {
        $actions = [];

        // 1. Deal Radar Actions
        foreach ($modules['deal_radar'] as $deal) {
            $actions[] = [
                'action_source' => 'deal_radar',
                'listing_id' => $deal['listing_id'],
                'title' => $deal['listing_title'],
                'action_label' => $deal['suggested_action'],
                'reason' => $deal['primary_signal'] . ' (' . $deal['deal_tier'] . ')',
                'raw_tier' => $deal['deal_tier']
            ];
        }

        // 2. Opportunity Engine Actions
        foreach ($modules['opportunity_engine'] as $opp) {
            $actions[] = [
                'action_source' => 'opportunity_engine',
                'listing_id' => $opp['listing_id'],
                'title' => $opp['title'],
                'action_label' => $opp['suggested_action'],
                'reason' => $opp['opportunity_type'] . ' - ' . substr($opp['reason'], 0, 50),
                'raw_tier' => $opp['opportunity_type']
            ];
        }

        // 3. Portfolio Doctor Actions
        foreach ($modules['portfolio_doctor'] as $doc) {
            $actions[] = [
                'action_source' => 'portfolio_doctor',
                'listing_id' => $doc['listing_id'],
                'title' => $doc['listing_title'],
                'action_label' => $doc['suggested_actions']['action_type'] ?? 'REVIEW',
                'reason' => $doc['primary_problem'] . ' - ' . ($doc['suggested_actions']['description'] ?? ''),
                'raw_tier' => $doc['primary_problem']
            ];
        }

        // 4. Buyer Match Actions
        foreach ($modules['buyer_match'] as $match) {
            $actions[] = [
                'action_source' => 'buyer_match',
                'listing_id' => $match['listing_id'],
                'title' => $match['listing_title'] . ' -> ' . $match['buyer_name'],
                'action_label' => $match['suggested_action'],
                'reason' => 'Buyer Intent: ' . $match['urgency_signal'] . ' for Match Tier ' . $match['match_tier'],
                'raw_tier' => $match['urgency_signal']
            ];
        }

        $normalizedActions = $this->normalizeActionPriority($actions);

        // If specific priority level is filtered (e.g., 'Today Priority only' maps to CRITICAL/HIGH)
        if (isset($filters['priority_filter']) && $filters['priority_filter'] === 'today') {
            $normalizedActions = array_filter($normalizedActions, function($action) {
                return in_array($action['execution_priority'], ['CRITICAL', 'HIGH']);
            });
        }

        return array_values($normalizedActions);
    }

    /**
     * Map varying signals to a standardized execution_priority field (CRITICAL, HIGH, MEDIUM, LOW)
     */
    private function normalizeActionPriority(array $actions): array
    {
        $normalized = array_map(function ($action) {
            $tier = $action['raw_tier'];
            $priority = 'LOW';
            $urgencyLevel = 1;

            if (in_array($tier, ['HOT_DEAL', 'HIGH_INTENT', 'AT_RISK', 'HIGH_DEMAND_LOW_CONVERSION'])) {
                $priority = 'CRITICAL';
                $urgencyLevel = 4;
            } elseif (in_array($tier, ['FAST_MOVING', 'OVERPRICED', 'UNDERPRICED_LISTING', 'HIGH_BUYER_MATCH'])) {
                $priority = 'HIGH';
                $urgencyLevel = 3;
            } elseif (in_array($tier, ['LOW_VISIBILITY', 'LOW_DEMAND_AREA', 'LOW_QUALITY_HIGH_POTENTIAL'])) {
                $priority = 'MEDIUM';
                $urgencyLevel = 2;
            }

            // Standardize action format
            return [
                'action_source' => $action['action_source'],
                'listing_id' => $action['listing_id'],
                'title' => $action['title'],
                'action_label' => $action['action_label'],
                'urgency_level' => $urgencyLevel,
                'execution_priority' => $priority,
                'reason' => $action['reason']
            ];
        }, $actions);

        // Sort globally by urgency Level descending
        usort($normalized, fn($a, $b) => $b['urgency_level'] <=> $a['urgency_level']);

        return $normalized;
    }
}
