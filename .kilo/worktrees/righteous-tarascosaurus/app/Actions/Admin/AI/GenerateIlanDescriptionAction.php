<?php

namespace App\Actions\Admin\AI;

use App\Services\AI\DataDrivenAIContentService;
use App\Services\AI\AiCostGuardService;
use App\Services\AI\AiFeatureFlags;

/**
 * 🛰️ GenerateIlanDescriptionAction
 * 
 * Part of P2 Remediation. Handles data-driven content generation 
 * (description, summary, seo-meta) with budget guarding.
 */
class GenerateIlanDescriptionAction
{
    protected DataDrivenAIContentService $dataDrivenAI;
    protected AiCostGuardService $costGuard;

    public function __construct(
        DataDrivenAIContentService $dataDrivenAI,
        AiCostGuardService $costGuard
    ) {
        $this->dataDrivenAI = $dataDrivenAI;
        $this->costGuard = $costGuard;
    }

    /**
     * Execute content generation logic.
     */
    public function handle(array $structuredData, array $options = [], string $type = 'description'): array
    {
        // 1. Feature flag guard
        if (!AiFeatureFlags::isEnabled('assist')) {
            return ['success' => false, 'error' => 'AI özelliği devre dışı', 'code' => 403];
        }

        // 2. Budget Guard
        $budget = $this->costGuard->checkBudget();
        if (!$budget['allowed']) {
            return ['success' => false, 'error' => 'AI bütçe sınırı aşıldı: ' . $budget['reason'], 'code' => 402];
        }

        // 3. Delegate based on type
        switch ($type) {
            case 'description':
                $result = $this->dataDrivenAI->generateDescription($structuredData, $options);
                break;
            case 'title':
                $result = $this->dataDrivenAI->generateTitle($structuredData, $options);
                break;
            case 'summary':
                $result = $this->dataDrivenAI->generateSummary($structuredData, $options);
                break;
            case 'seo_meta':
                $result = $this->dataDrivenAI->generateSeoMeta($structuredData, $options);
                break;
            default:
                return ['success' => false, 'error' => 'Geçersiz üretim tipi', 'code' => 400];
        }

        if (!$result['success']) {
            $statusCode = isset($result['metadata']['data_validation']) && $result['metadata']['data_validation'] === 'failed' ? 422 : 500;
            return ['success' => false, 'error' => $result['error'] ?? 'Üretim başarısız', 'code' => $statusCode];
        }

        return [
            'success' => true,
            'data' => $result['data'],
            'provider' => $result['provider'],
            'metadata' => $result['metadata'],
        ];
    }
}
