<?php

namespace App\Http\Controllers\Admin\AI;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\LocalVisionService;
use App\Services\Response\ResponseService;
use App\Services\Logging\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Local Vision Controller
 * 
 * Gemma 3 tabanlı yerel görsel analiz endpoint'i.
 * Context7 Standardı: C7-LOCAL-VISION-CONTROLLER-2026-01-12
 */
class LocalVisionController extends Controller
{
    public function __construct(
        private LocalVisionService $visionService
    ) {}

    /**
     * Fotoğraf analizi yapar
     * 
     * POST /admin/ai/analyze-image
     */
    public function analyze(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|string', // Base64 image
                'context' => 'nullable|array'
            ]);

            $base64Image = $request->input('image');
            $context = $request->input('context', []);

            LogService::info('Local Vision analysis started', [
                'context' => $context
            ]);

            $result = $this->visionService->analizEt($base64Image, $context);

            return ResponseService::success(
                data: $result,
                message: 'Görsel analizi tamamlandı'
            );

        } catch (\Exception $e) {
            LogService::error('Local Vision analysis failed', [
                'error' => $e->getMessage()
            ], $e);

            return ResponseService::serverError(
                message: 'Görsel analizi şu an yapılamadı.',
                exception: $e
            );
        }
    }
}
