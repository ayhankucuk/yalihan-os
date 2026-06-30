<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Cortex\CortexScoringService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CortexScoreController extends Controller
{
    public function __construct(private CortexScoringService $scoringService)
    {
    }

    public function analyze(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:ilan_kategorileri,id'],
            'filled_fields' => ['nullable', 'array'],
            'photo_count' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $categoryId = (int) $validated['category_id'];
        $filledFields = $validated['filled_fields'] ?? [];
        $photoCount = (int) ($validated['photo_count'] ?? 0);
        $description = $validated['description'] ?? '';

        $result = $this->scoringService->calculateFullScore($categoryId, $filledFields, $photoCount, $description);

        return ResponseService::success($result, 'Cortex Score başarıyla hesaplandı');
    }
}
