<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\Yazlik\StructuredDataMapper;
use App\Services\Yazlik\StructuredDataValidator;
use App\Services\AI\DataDrivenAIContentService;
use App\Actions\Admin\Ilan\StoreStructuredDataAction;
use App\Actions\Admin\Ilan\ApproveStructuredDataAction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class YazlikStructuredDataController extends Controller
{
    public function __construct(
        protected StructuredDataMapper $mapper,
        protected StructuredDataValidator $validator,
        protected DataDrivenAIContentService $aiService
    ) {}

    public function validateStructuredData(Request $request, int $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);

        if ($ilan->structured_data_scope !== 'yazlik_kiralama') {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilan yazlık kiralama template\'i kullanmıyor',
            ], 422);
        }

        $structuredData = $request->input('structured_data', []);

        if (empty($structuredData)) {
            $structuredData = $ilan->structured_data ?? [];
        }

        $yayinTipi = $ilan->yayinTipi;
        $yayinTipiSlug = $yayinTipi ? strtolower($yayinTipi->yayin_tipi) : 'gunluk';

        $result = $this->validator->validate($structuredData, $yayinTipiSlug);

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

        $yayinTipi = $ilan->yayinTipi;
        $yayinTipiSlug = $yayinTipi ? strtolower($yayinTipi->yayin_tipi) : 'gunluk';

        $validationResult = $this->validator->validate($structuredData, $yayinTipiSlug);

        if (!$validationResult['valid']) {
            return response()->json([
                'success' => false,
                'errors' => $validationResult['errors'],
            ], 422);
        }

        app(StoreStructuredDataAction::class)->handle($ilan, $structuredData, 'yazlik_kiralama');

        return response()->json([
            'success' => true,
            'message' => 'Structured data kaydedildi',
        ]);
    }

    public function approve(Request $request, int $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);

        if ($ilan->structured_data_scope !== 'yazlik_kiralama') {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilan yazlık kiralama template\'i kullanmıyor',
            ], 422);
        }

        $structuredData = $ilan->structured_data ?? [];

        if (empty($structuredData)) {
            return response()->json([
                'success' => false,
                'message' => 'Structured data bulunamadı',
            ], 422);
        }

        $yayinTipi = $ilan->yayinTipi;
        $yayinTipiSlug = $yayinTipi ? strtolower($yayinTipi->yayin_tipi) : 'gunluk';

        $validationResult = $this->validator->validate($structuredData, $yayinTipiSlug);

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

        if ($ilan->structured_data_scope !== 'yazlik_kiralama') {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilan yazlık kiralama template\'i kullanmıyor',
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

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function generateSummary(Request $request, int $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);

        if ($ilan->structured_data_scope !== 'yazlik_kiralama') {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilan yazlık kiralama template\'i kullanmıyor',
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

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function generateDescription(Request $request, int $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);

        if ($ilan->structured_data_scope !== 'yazlik_kiralama') {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilan yazlık kiralama template\'i kullanmıyor',
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

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function generateSeoMeta(Request $request, int $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);

        if ($ilan->structured_data_scope !== 'yazlik_kiralama') {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilan yazlık kiralama template\'i kullanmıyor',
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

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
