<?php

namespace App\Services\Wizard;

use App\Domain\Ilan\Services\WizardSchemaResolver;
use App\Domain\Ilan\Services\WizardSessionManager;
use App\Domain\Ilan\Services\WizardStepExecutor;
use App\Services\AI\SmartFieldGenerationService;
use App\Services\AI\Monitoring\AiTelemetryService; // SSOT: Monitoring namespace
use App\Services\AI\AiLearningSignalService;
use App\Services\AI\AiExperimentService;
use App\Services\Ups\FeatureTemplateResolver;
use App\Services\Template\TemplateService;
use App\Services\AI\VisionAnalysisService;
use App\Services\Wizard\ListingQualityService;

/**
 * Orchestrator Service for Wizard endpoints
 *
 * SAB v4.1 Rule #11 Enforcer: Reduces constructor dependencies in Controller.
 * Groups specialized services into a single unified facade for the Wizard controllers.
 */
class WizardOrchestrator
{
    public function __construct(
        public FeatureTemplateResolver $resolver,
        public SmartFieldGenerationService $aiService,
        public TemplateService $templateService,
        public AiTelemetryService $telemetryService,
        public VisionAnalysisService $visionService,
        public AiLearningSignalService $learningService,
        public AiExperimentService $experimentService,
        public ListingQualityService $qualityService,
        public ?WizardSessionManager $sessionManager = null,
        public ?WizardSchemaResolver $schemaResolver = null,
        public ?WizardStepExecutor $stepExecutor = null
    ) {
        $this->sessionManager = $sessionManager ?? app(WizardSessionManager::class);
        $this->schemaResolver = $schemaResolver ?? app(WizardSchemaResolver::class);
        $this->stepExecutor = $stepExecutor ?? app(WizardStepExecutor::class);
    }
}
