<?php

namespace App\Services\AI;

use App\Application\AI\Actions\AnalyzePropertyAction;
use App\Application\AI\Actions\AnalyzePropertyGapsAction;
use App\Application\AI\Actions\ExtractPropertyFeaturesAction;
use App\Application\AI\Actions\GeneratePropertyTemplateAction;
use App\Application\AI\Actions\SuggestPropertyTemplateAction;
use App\Models\User;

/**
 * Property AI Service (Facade/Orchestrator)
 * 
 * Centralizes AI actions to ensure "Clean by Design" controller proxies.
 */
class PropertyAIService
{
    public function __construct(
        private AnalyzePropertyAction $analyzeAction,
        private AnalyzePropertyGapsAction $analyzeGapsAction,
        private ExtractPropertyFeaturesAction $extractFeaturesAction,
        private SuggestPropertyTemplateAction $suggestTemplateAction,
        private GeneratePropertyTemplateAction $generateTemplateAction
    ) {}

    public function analyze(array $data, User $user)
    {
        return $this->analyzeAction->handle($data, $user);
    }

    public function analyzeGaps(array $data, User $user)
    {
        return $this->analyzeGapsAction->handle($data, $user);
    }

    public function extractFeatures(array $data, User $user)
    {
        return $this->extractFeaturesAction->handle($data, $user);
    }

    public function suggestTemplate(array $data, User $user)
    {
        return $this->suggestTemplateAction->handle($data, $user);
    }

    public function generateTemplate(int $templateId, array $data, User $user)
    {
        $payload = array_merge($data, ['yayin_tipi_id' => $templateId]);
        return $this->generateTemplateAction->handle($payload, $user);
    }
}
