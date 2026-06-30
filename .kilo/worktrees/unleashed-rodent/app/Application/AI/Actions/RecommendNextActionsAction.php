<?php

namespace App\Application\AI\Actions;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\AI\DTOs\CortexResponseData;
use App\Domain\AI\Contracts\CortexServiceInterface;
use App\Domain\AI\Enums\AITaskType;
use Illuminate\Support\Str;

/**
 * 🛡️ RecommendNextActionsAction
 * Aggregates listing data and requests strategic advice from Cortex.
 */
final class RecommendNextActionsAction
{
    public function __construct(
        private readonly CortexServiceInterface $cortex
    ) {}

    public function execute(array $listingData, array $context = []): CortexResponseData
    {
        $request = new CortexRequestData(
            taskType: AITaskType::RECOMMEND_NEXT_ACTIONS,
            input: $listingData,
            context: $context,
            traceId: (string) Str::uuid()
        );

        return $this->cortex->execute($request);
    }
}
