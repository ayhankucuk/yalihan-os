<?php

namespace App\Application\AI\Actions;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\AI\DTOs\CortexResponseData;
use App\Domain\AI\Contracts\CortexServiceInterface;
use App\Domain\AI\Enums\AITaskType;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

final class GeneratePropertyTemplateAction
{
    public function __construct(
        private readonly CortexServiceInterface $cortex
    ) {}

    public function handle(array $input, ?Authenticatable $user = null): CortexResponseData
    {
        // Guard: Check if the pivot exists and is active
        $pivotExists = \App\Models\AltKategoriYayinTipi::where('alt_kategori_id', $input['alt_kategori_id'] ?? 0)
            ->where('yayin_tipi_id', $input['yayin_tipi_id'] ?? 0)
            ->where('aktiflik_durumu', true)
            ->exists();

        if (!$pivotExists) {
            return new CortexResponseData(
                success: false,
                errorCode: 'PIVOT_NOT_FOUND',
                errorMessage: 'Seçili kategori ve yayın tipi arasında aktif bir ilişki bulunamadı.'
            );
        }

        $request = new CortexRequestData(
            taskType: AITaskType::GENERATE_PROPERTY_TEMPLATE,
            input: $input,
            userId: $user?->id ?? null,
            traceId: (string) Str::uuid()
        );

        $response = $this->cortex->execute($request);

        // If the adapter says it's a legacy task, we route to the legacy guard.
        if (!$response->success && $response->errorCode === 'LEGACY_TASK') {
            try {
                $output = app(\App\Services\PropertyType\LegacyGeneratorGuard::class)->generate(
                    $input['kategori'] ?? '',
                    $input['yayin_tipi'] ?? '',
                    $input['alt_tur'] ?? '',
                    ['trace_id' => $request->traceId]
                );

                if (empty($output)) {
                    return new CortexResponseData(
                        success: false,
                        traceId: $request->traceId,
                        errorCode: 'PIVOT_NOT_FOUND',
                        errorMessage: 'Seçili kategori ve yayın tipi için uygun bir şablon bulunamadı (Legacy).'
                    );
                }

                return new CortexResponseData(
                    success: true,
                    output: $output,
                    provider: 'legacy_json',
                    traceId: $request->traceId
                );
            } catch (\Throwable $e) {
                return new CortexResponseData(
                    success: false,
                    traceId: $request->traceId,
                    errorCode: 'LEGACY_GENERATOR_ERROR',
                    errorMessage: $e->getMessage()
                );
            }
        }

        return $response;
    }
}
