<?php

namespace App\Application\AI\Actions;

use App\Application\AI\DTOs\CortexResponseData;
use App\Models\FeatureAssignment;
use App\Models\YayinTipiSablonu;
use App\Services\AI\YalihanCortex;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

final class AnalyzePropertyGapsAction
{
    public function __construct(
        private readonly YalihanCortex $cortex
    ) {}

    /**
     * Handle the gap analysis request.
     */
    public function handle(array $input, ?Authenticatable $user = null): CortexResponseData
    {
        $traceId = $input['trace_id'] ?? (string) Str::uuid();

        try {
            $yayinTipiId = $input['yayin_tipi_id'];
            $categoryName = $input['category_name'];

            // Fetch current assignments for the template
            $currentAssignments = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
                ->where('assignable_id', $yayinTipiId)
                ->with('feature')
                ->get();

            $currentFeatures = $currentAssignments->map(fn($a) => $a->feature->name)->toArray();

            // Execute via Cortex Authority
            $result = $this->cortex->analyzePropertyGaps($categoryName, $currentFeatures);

            return new CortexResponseData(
                success: true,
                output: $result,
                provider: 'cortex',
                traceId: $traceId
            );
        } catch (\Throwable $e) {
            /** @sab-ignore-catch intentional: exception bilgisi CortexResponseData.errorMessage'a aktarılıyor, yutulmuyor */
            return new CortexResponseData(
                success: false,
                traceId: $traceId,
                errorCode: 'GAP_ANALYSIS_FAILED',
                errorMessage: $e->getMessage()
            );
        }
    }
}
