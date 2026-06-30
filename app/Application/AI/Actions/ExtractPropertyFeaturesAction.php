<?php

namespace App\Application\AI\Actions;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\AI\DTOs\CortexResponseData;
use App\Domain\AI\Contracts\CortexServiceInterface;
use App\Domain\AI\Enums\AITaskType;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

final class ExtractPropertyFeaturesAction
{
    public function __construct(
        private readonly \App\Domain\AI\Contracts\CortexServiceInterface $cortex,
        private readonly \App\Application\AI\Normalizers\PropertyResponseNormalizer $normalizer
    ) {}

    public function handle(array $input, ?Authenticatable $user = null): CortexResponseData
    {
        $request = new CortexRequestData(
            taskType: AITaskType::EXTRACT_PROPERTY_FEATURES,
            input: $input,
            userId: $user?->id ?? null,
            traceId: (string) Str::uuid()
        );

        $response = $this->cortex->execute($request);

        if ($response->success && isset($response->output)) {
            return new CortexResponseData(
                success: true,
                output: [
                    'groups' => $this->normalizer->normalizeGroups($response->output),
                    'raw'    => $response->output
                ],
                provider: $response->provider,
                model: $response->model,
                traceId: $response->traceId,
                usage: $response->usage
            );
        }

        return $response;
    }
}
