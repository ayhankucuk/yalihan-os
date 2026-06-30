<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\Konut\StructuredDataMapper;
use App\Services\Konut\StructuredDataValidator;
use App\Services\AI\DataDrivenAIContentService;
use App\Actions\Admin\Ilan\StoreStructuredDataAction;
use App\Actions\Admin\Ilan\ApproveStructuredDataAction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class KonutStructuredDataController extends Controller
{
    public function __construct(
        protected StructuredDataMapper $mapper,
        protected StructuredDataValidator $validator,
        protected DataDrivenAIContentService $aiService,
        protected \App\Services\Konut\KonutStructuredDataShadowWriter $shadowWriter
    ) {}

    public function validateStructuredData(Request $request, int $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);

        if ($ilan->structured_data_scope !== 'konut_satilik') {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilan konut satılık template\'i kullanmıyor',
            ], 422);
        }

        $structuredData = $request->input('structured_data', []);

        if (empty($structuredData)) {
            $structuredData = $ilan->structured_data ?? [];
        }

        $result = $this->validator->validate($structuredData);

        return response()->json([
            'success' => $result['valid'],
            'errors' => $result['errors'],
        ], $result['valid'] ? 200 : 422);
    }

    public function store(Request $request, int $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);

        $payload = $request->input('structured_data', []);
        $structuredData = $this->mapper->mapFromWizardInput($payload);

        $validationResult = $this->validator->validate($structuredData);

        if (!$validationResult['valid']) {
            return response()->json([
                'success' => false,
                'errors' => $validationResult['errors'],
            ], 422);
        }

        app(StoreStructuredDataAction::class)->handle($ilan, $structuredData, 'konut_satilik');

        // ✅ Hybrid SSOT: Shadow Write to Feature Assignments
        // This ensures compatibility with the main Feature/EAV system
        $this->shadowWriter->shadowWrite($ilan, $structuredData);

        return response()->json([
            'success' => true,
            'message' => 'Structured data kaydedildi',
        ]);
    }

    public function approve(Request $request, int $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);

        if ($ilan->structured_data_scope !== 'konut_satilik') {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilan konut satılık template\'i kullanmıyor',
            ], 422);
        }

        $structuredData = $ilan->structured_data ?? [];

        if (empty($structuredData)) {
            return response()->json([
                'success' => false,
                'message' => 'Structured data bulunamadı',
            ], 422);
        }

        $validationResult = $this->validator->validate($structuredData);

        if (!$validationResult['valid']) {
            return response()->json([
                'success' => false,
                'message' => 'Validation başarısız',
                'errors' => $validationResult['errors'],
            ], 422);
        }

        app(ApproveStructuredDataAction::class)->handle($ilan, Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'İlan onaylandı (mühürlendi)',
        ]);
    }

    public function generateTitle(Request $request, int $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);

        if ($ilan->structured_data_scope !== 'konut_satilik') {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilan konut satılık template\'i kullanmıyor',
            ], 403);
        }

        if (!$ilan->approved_at) {
            return response()->json([
                'success' => false,
                'message' => 'İlan onaylanmamış (mühürlenmemiş)',
            ], 403);
        }

        $structuredData = $ilan->structured_data ?? [];

        if (empty($structuredData)) {
            return response()->json([
                'success' => false,
                'message' => 'Structured data bulunamadı',
            ], 422);
        }

        $result = $this->aiService->generateTitle($structuredData);

        if (!$result['success']) {
            $errorMessage = $result['error'] ?? 'Başlık üretilemedi';
            $cevap_kodu = 500;

            if (str_contains(strtolower($errorMessage), 'bağlanılamadı') ||
                str_contains(strtolower($errorMessage), 'timeout')) {
                $cevap_kodu = 503;
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'metadata' => $result['metadata'] ?? [],
            ], $cevap_kodu);
        }

        return response()->json($result, 200);
    }

    public function generateSummary(Request $request, int $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);

        if ($ilan->structured_data_scope !== 'konut_satilik') {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilan konut satılık template\'i kullanmıyor',
            ], 403);
        }

        if (!$ilan->approved_at) {
            return response()->json([
                'success' => false,
                'message' => 'İlan onaylanmamış (mühürlenmemiş)',
            ], 403);
        }

        $structuredData = $ilan->structured_data ?? [];

        if (empty($structuredData)) {
            return response()->json([
                'success' => false,
                'message' => 'Structured data bulunamadı',
            ], 422);
        }

        $result = $this->aiService->generateSummary($structuredData);

        if (!$result['success']) {
            $errorMessage = $result['error'] ?? 'Özet üretilemedi';
            $cevap_kodu = 500;

            if (str_contains(strtolower($errorMessage), 'bağlanılamadı') ||
                str_contains(strtolower($errorMessage), 'timeout')) {
                $cevap_kodu = 503;
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'metadata' => $result['metadata'] ?? [],
            ], $cevap_kodu);
        }

        return response()->json($result, 200);
    }

    public function generateDescription(Request $request, int $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);

        if ($ilan->structured_data_scope !== 'konut_satilik') {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilan konut satılık template\'i kullanmıyor',
            ], 403);
        }

        if (!$ilan->approved_at) {
            return response()->json([
                'success' => false,
                'message' => 'İlan onaylanmamış (mühürlenmemiş)',
            ], 403);
        }

        $structuredData = $ilan->structured_data ?? [];

        if (empty($structuredData)) {
            return response()->json([
                'success' => false,
                'message' => 'Structured data bulunamadı',
            ], 422);
        }

        $result = $this->aiService->generateDescription($structuredData);

        if (!$result['success']) {
            $errorMessage = $result['error'] ?? 'Açıklama üretilemedi';
            $cevap_kodu = 500;

            if (str_contains(strtolower($errorMessage), 'bağlanılamadı') ||
                str_contains(strtolower($errorMessage), 'timeout')) {
                $cevap_kodu = 503;
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'metadata' => $result['metadata'] ?? [],
            ], $cevap_kodu);
        }

        return response()->json($result, 200);
    }

    public function generateSeoMeta(Request $request, int $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);

        if ($ilan->structured_data_scope !== 'konut_satilik') {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilan konut satılık template\'i kullanmıyor',
            ], 403);
        }

        if (!$ilan->approved_at) {
            return response()->json([
                'success' => false,
                'message' => 'İlan onaylanmamış (mühürlenmemiş)',
            ], 403);
        }

        $structuredData = $ilan->structured_data ?? [];

        if (empty($structuredData)) {
            return response()->json([
                'success' => false,
                'message' => 'Structured data bulunamadı',
            ], 422);
        }

        $result = $this->aiService->generateSeoMeta($structuredData);

        if (!$result['success']) {
            $errorMessage = $result['error'] ?? 'SEO meta üretilemedi';
            $cevap_kodu = 500;

            if (str_contains(strtolower($errorMessage), 'bağlanılamadı') ||
                str_contains(strtolower($errorMessage), 'timeout')) {
                $cevap_kodu = 503;
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'metadata' => $result['metadata'] ?? [],
            ], $cevap_kodu);
        }

        return response()->json($result, 200);
    }
}
