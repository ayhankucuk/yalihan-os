<?php

namespace App\Http\Controllers\Admin\AI;

use App\Http\Controllers\Controller;
use App\Http\Requests\AI\AnalyzePropertyRequest;
use App\Http\Requests\AI\ExtractPropertyFeaturesRequest;
use App\Http\Requests\AI\GeneratePropertyTemplateRequest;
use App\Http\Requests\AI\SuggestPropertyTemplateRequest;
use App\Services\AI\PropertyAIService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;

/**
 * @sab-ignore-thin
 */
final class PropertyAIController extends Controller
{
    public function __construct(
        private PropertyAIService $aiService
    ) {}

    public function analyze(
        AnalyzePropertyRequest $request
    ): JsonResponse {
        $result = $this->aiService->analyze($request->validated(), $request->user());

        if (!$result->success) {
            return ResponseService::error($result->errorMessage ?? 'Analiz başarısız', 422, [], $result->errorCode);
        }

        return ResponseService::success($result->output, $result->errorMessage ?? 'Analiz başarılı', 200, ['trace_id' => $result->traceId]);
    }

    public function generateTemplate(
        GeneratePropertyTemplateRequest $request,
        int $templateId
    ): JsonResponse {
        try {
            $result = $this->aiService->generateTemplate($templateId, $request->validated(), $request->user());

            if (!$result->success) {
                return ResponseService::error(
                    $result->errorMessage ?? 'Şablon üretimi başarısız', 
                    422, 
                    [], 
                    $result->errorCode ?? 'AI_PROCESS_FAILED'
                );
            }

            return ResponseService::success(
                $result->output, 
                $result->errorMessage ?? 'Şablon başarıyla üretildi', 
                200, 
                ['trace_id' => $result->traceId]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AI Provider Error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseService::error(
                $e->getMessage(), 
                502, 
                ['exception' => get_class($e)], 
                'AI_PROVIDER_FAILED'
            );
        }
    }

    public function suggestTemplate(
        SuggestPropertyTemplateRequest $request
    ): JsonResponse {
        $result = $this->aiService->suggestTemplate($request->validated(), $request->user());

        if (!$result->success) {
            return ResponseService::error($result->errorMessage ?? 'Öneri başarısız', 422, [], $result->errorCode);
        }

        return ResponseService::success($result->output, $result->errorMessage ?? 'Öneri başarılı', 200, ['trace_id' => $result->traceId]);
    }

    public function analyzeGaps(
        AnalyzePropertyRequest $request
    ): JsonResponse {
        $result = $this->aiService->analyzeGaps($request->validated(), $request->user());

        if (!$result->success) {
            return ResponseService::error($result->errorMessage ?? 'Eksik analizi başarısız', 422, [], $result->errorCode);
        }

        return ResponseService::success($result->output, $result->errorMessage ?? 'Eksik analizi başarılı', 200, ['trace_id' => $result->traceId]);
    }

    public function extractFeatures(
        ExtractPropertyFeaturesRequest $request
    ): JsonResponse {
        $result = $this->aiService->extractFeatures($request->validated(), $request->user());

        if (!$result->success) {
            return ResponseService::error($result->errorMessage ?? 'Özellik çıkarımı başarısız', 422, [], $result->errorCode);
        }

        return ResponseService::success($result->output, $result->errorMessage ?? 'Özellik çıkarımı başarılı', 200, ['trace_id' => $result->traceId]);
    }
}
