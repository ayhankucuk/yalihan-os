<?php

namespace App\Application\AI\Actions;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\AI\DTOs\CortexResponseData;
use App\Domain\AI\Contracts\CortexServiceInterface;
use App\Domain\AI\Enums\AITaskType;
use Illuminate\Support\Str;

/**
 * 🛡️ AuditPortfolioHealthAction
 * Aggregates portfolio data and requests a health audit from Cortex.
 */
final class AuditPortfolioHealthAction
{
    public function __construct(
        private readonly CortexServiceInterface $cortex
    ) {}

    public function execute(array $portfolioData, array $context = []): CortexResponseData
    {
        $request = new CortexRequestData(
            taskType: AITaskType::AUDIT_PORTFOLIO_HEALTH,
            input: $portfolioData,
            context: $context,
            traceId: (string) Str::uuid()
        );

        return $this->cortex->execute($request);
    }
}
