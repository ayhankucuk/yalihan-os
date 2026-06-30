<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Engine\Adapters;

use App\Domain\PropertyHub\Resolution\Contracts\TemplateResolutionEngineInterface;
use App\Domain\PropertyHub\Resolution\DTOs\ResolutionContext;
use App\Domain\PropertyHub\Resolution\DTOs\ResolutionResult;
use App\Services\Template\TemplateService;

/**
 * V2 Adapter for Shadow Mode
 *
 * Wraps existing TemplateService to match V3 interface.
 */
class V2TemplateResolutionEngineAdapter implements TemplateResolutionEngineInterface
{
    public function __construct(
        private readonly TemplateService $templateService
    ) {}

    public function resolve(ResolutionContext $context): ResolutionResult
    {
        // Convert V3 context back to V2 params
        $v2Result = $this->templateService->autoSelectTemplate(
            kategoriId: $context->categoryId,
            yayinTipiId: $context->publicationTypeId,
            context: $context->toArray()
        );

        $templateId = $v2Result['template_id'] ?? 0;
        $features = $v2Result['features'] ?? [];

        // Compute a stable signature for V2 result to compare with V3
        $signature = $this->computeV2Signature($templateId, $features);

        return new ResolutionResult(
            templateId: (int) $templateId,
            features: $features,
            fieldDependencies: $v2Result['validation']['rules'] ?? [],
            signature: $signature,
            trace: [
                'engine' => 'V2',
                'adapter' => 'V2TemplateResolutionEngineAdapter',
                'v2_template_id' => $templateId,
            ]
        );
    }

    /**
     * Compute stable signature for V2 results.
     */
    private function computeV2Signature(int $templateId, array $features): string
    {
        $data = [
            'id' => $templateId,
            'feat' => $features,
        ];

        // Ensure deterministic signature
        ksort($data['feat']);

        return hash('sha256', json_encode($data));
    }
}
