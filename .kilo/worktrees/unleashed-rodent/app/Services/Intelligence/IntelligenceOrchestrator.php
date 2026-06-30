<?php

namespace App\Services\Intelligence;

/**
 * Intelligence Orchestrator (Facade)
 *
 * SAB v4.1 Rule #10 Enforcer: Reduces constructor dependencies in IntelligenceDashboardController
 * by encapsulating 5 separate AI domain services into a single access point.
 */
class IntelligenceOrchestrator
{
    public function __construct(
        public ActionScoreService $actionScore,
        public BudgetCorrectionService $budgetCorrection,
        public ContractGuardService $contractGuard,
        public SentimentAnalysisService $sentimentAnalysis,
        public MultilingualService $multilingual
    ) {}
}
