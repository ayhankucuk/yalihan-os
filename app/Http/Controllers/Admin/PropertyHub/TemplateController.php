<?php

namespace App\Http\Controllers\Admin\PropertyHub;

use App\Actions\PropertyHub\SyncPivotAssignmentsAction;
use App\Http\Controllers\Api\Concerns\ApiResponds;
use App\Http\Controllers\Controller;
use App\Services\AI\YalihanCortex;
use App\Services\PropertyHub\PropertyHubOrchestrator;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * PropertyHub Template Controller
 *
 * Handles template operations, AI suggestions, and import/export.
 * Part of PropertyHub modular refactoring (Sprint 2).
 */
class TemplateController extends Controller
{
    use ApiResponds;

    public function __construct(
        private PropertyHubOrchestrator $hub,
        private YalihanCortex $cortex
    ) {}

    /**
     * Store AI Generated Template Structure (UPS Standard V1)
     *
     * [SAB ENFORCEMENT]: Domain Consolidation
     * Aggregate Root uzerinden sealTemplate() cagirilir.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'junction_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'ups_json' => 'required|array',
            'should_seal' => 'nullable|boolean'
        ]);

        try {
            $result = $this->hub->aggregateRoot->sealTemplate(
                junctionId: (int) $validated['junction_id'],
                upsJson: $validated['ups_json'],
                shouldSeal: $validated['should_seal'] ?? true,
                userId: auth()->id()
            );

            if ($result['is_duplicate']) {
                return ResponseService::success(
                    $result['template'],
                    'Bu sablon zaten guncel surum olarak kayitli.'
                );
            }

            return ResponseService::success(
                $result['template'],
                "AI Sablonu basariyla muhurlendi (v{$result['template']->template_version}) 🛡️"
            );

        } catch (\Exception $e) {
            return ResponseService::error('Sablon muhurleme hatasi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get features assigned to a specific category-template pivot
     */
    public function getPivotAssignments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'yayin_tipi_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'alt_kategori_id' => 'required|exists:ilan_kategorileri,id',
        ]);

        $data = $this->hub->getPivotAssignments(
            (int) $validated['yayin_tipi_id'],
            (int) $validated['alt_kategori_id']
        );

        return response()->json($data);
    }

    /**
     * Save feature assignments for a specific category-template pivot
     */
    public function savePivotAssignments(Request $request, SyncPivotAssignmentsAction $action): JsonResponse
    {
        $validated = $request->validate([
            'yayin_tipi_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'alt_kategori_id' => 'required|exists:ilan_kategorileri,id',
            'feature_ids' => 'present|array',
            'feature_ids.*' => 'exists:features,id',
        ]);

        try {
            $this->hub->syncPivotAssignments(
                (int) $validated['yayin_tipi_id'],
                (int) $validated['alt_kategori_id'],
                $validated['feature_ids'],
                auth()->id()
            );

            return ResponseService::success(null, 'Kategoriye özel özellikler güncellendi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Kayıt başarısız: ' . $e->getMessage(), $e);
        }
    }

    /**
     * AI: Suggest template structure from category + optional description.
     */
    public function aiSuggestTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $startedAt = microtime(true);
        $traceId = (string) Str::uuid();

        try {
            $aiData = $this->cortex->generateTemplateSuggestions(
                $validated['category_name'],
                (string) ($validated['description'] ?? '')
            );

            $httpCode = 200;
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            return response()->json([
                'success' => true,
                'data' => $aiData,
                'mapped_features' => $this->mapSuggestedFeatures($aiData),
                'trace_id' => $traceId,
                'telemetry' => [
                    'basarili' => true,
                    'http_durum_kodu' => $httpCode,
                    'duration_ms' => $durationMs,
                    'istek_url' => $request->fullUrl(),
                    'trace_id' => $traceId,
                ],
            ], $httpCode);
        } catch (\Throwable $e) {
            $httpCode = 500;
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            return response()->json([
                'success' => false,
                'message' => 'AI şablon önerisi başarısız: ' . $e->getMessage(),
                'trace_id' => $traceId,
                'telemetry' => [
                    'basarili' => false,
                    'http_durum_kodu' => $httpCode,
                    'duration_ms' => $durationMs,
                    'istek_url' => $request->fullUrl(),
                    'trace_id' => $traceId,
                ],
            ], $httpCode);
        }
    }

    /**
     * Apply a master template (blueprint) to a specific yayin tipi şablonu
     */
    public function applyMasterTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'master_template_id' => 'required|exists:ups_master_templates,id',
            'yayin_tipi_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'mode' => 'nullable|in:merge,replace',
        ]);

        try {
            $result = $this->hub->applyMasterTemplate(
                (int) $request->master_template_id,
                (int) $request->yayin_tipi_id,
                ['mode' => $request->mode ?? 'merge']
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Export templates
     */
    public function export(Request $request)
    {
        try {
            $export = $this->hub->exportFullConfiguration();
            $filename = 'ups-templates-' . now()->format('Y-m-d-His') . '.json';

            return response()->json($export)
                ->header('Content-Disposition', "attachment; filename={$filename}")
                ->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Import templates
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:json',
        ]);

        try {
            $result = $this->hub->importConfiguration($request->file('file'));

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import başarısız: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Map suggested features from AI response
     */
    private function mapSuggestedFeatures(array $aiData): array
    {
        return collect($aiData['groups'] ?? [])
            ->flatMap(fn (array $group) => collect($group['features'] ?? [])
                ->map(fn (array|string $feature) => is_array($feature)
                    ? ($feature['slug'] ?? $feature['name'] ?? null)
                    : $feature))
            ->filter(fn ($slug) => is_string($slug) && $slug !== '')
            ->values()
            ->all();
    }
}
