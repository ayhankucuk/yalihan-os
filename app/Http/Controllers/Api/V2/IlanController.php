<?php

namespace App\Http\Controllers\Api\V2;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Actions\Api\V2\Ilan\DestroyIlanAction;
use App\Actions\Api\V2\Ilan\PublishIlanAction;
use App\Actions\Api\V2\Ilan\StoreIlanAction;
use App\Actions\Api\V2\Ilan\UnpublishIlanAction;
use App\Actions\Api\V2\Ilan\UpdateIlanAction;
use App\Http\Controllers\Controller;
use App\Models\V2\Ilan;
use App\Http\Resources\Mobile\IlanDetailResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * V2 Ilanlar (Listings) API Controller
 *
 * Context7: 100% Compliant
 * - Field names: baslik, aciklama, alan_m2, birim_fiyat, il, ilce, mahalle, lat, lng
 * - Publication field: yayin_durumu (approved canonical name)
 * - No forbidden field patterns
 * - RESTful endpoints with proper validation
 */
class IlanController extends Controller
{
    /**
     * Display a listing of listings
     * GET /api/v1/ilanlar
     */
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $ilanlar = Ilan::query()
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->with(['il', 'ilce', 'mahalle', 'anaKategori', 'fotograflar']) // Fix N+1
            ->latest('created_at')
            ->paginate(20);

        return \App\Http\Resources\Mobile\IlanListResource::collection($ilanlar);
    }

    /**
     * Store a newly created listing
     * POST /api/v1/ilanlar
     */
    public function store(Request $request, StoreIlanAction $action): JsonResponse
    {
        $validated = $request->validate([
            'baslik' => 'required|string|max:255',
            'aciklama' => 'required|string|min:20',
            'alan_m2' => 'required|numeric|min:1',
            'birim_fiyat' => 'required|numeric|min:0',
            'il' => 'required|string|max:50',
            'ilce' => 'required|string|max:50',
            'mahalle' => 'required|string|max:50',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'one_cikan' => 'sometimes|boolean',
        ]);

        $ilan = $action->handle($validated);

        return response()->json([
            'success' => true,
            'message' => 'İlan başarıyla oluşturuldu',
            'data' => $ilan,
        ], 201);
    }

    /**
     * Display the specified listing
     * GET /api/v1/ilanlar/{id}
     */
    public function show($id): IlanDetailResource|JsonResponse
    {
        $ilan = Ilan::with(['il', 'ilce', 'mahalle', 'fotograflar', 'danisman', 'anaKategori'])
            ->find($id);

        if (!$ilan) {
            return response()->json(['message' => 'İlan bulunamadı'], 404);
        }

        // SAB Kural #1 — Tenant Isolation IDOR protection
        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            if ($user->tenant_id !== $ilan->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu ilana erişim izniniz yok',
                ], 403);
            }
        }

        return new IlanDetailResource($ilan);
    }

    /**
     * Update the specified listing
     * PUT /api/v1/ilanlar/{id}
     */
    public function update(Request $request, Ilan $ilan, UpdateIlanAction $action): JsonResponse
    {
        // Check authorization
        if ($ilan->danisman_id !== auth('sanctum')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilana erişim izniniz yok',
            ], 403);
        }

        $validated = $request->validate([
            'baslik' => 'sometimes|string|max:255',
            'aciklama' => 'sometimes|string|min:20',
            'alan_m2' => 'sometimes|numeric|min:1',
            'birim_fiyat' => 'sometimes|numeric|min:0',
            'il' => 'sometimes|string|max:50',
            'ilce' => 'sometimes|string|max:50',
            'mahalle' => 'sometimes|string|max:50',
            'lat' => 'sometimes|numeric|between:-90,90',
            'lng' => 'sometimes|numeric|between:-180,180',
            'one_cikan' => 'sometimes|boolean',
        ]);

        $action->handle($ilan, $validated);

        return response()->json([
            'success' => true,
            'message' => 'İlan başarıyla güncellendi',
            'data' => $ilan,
        ]);
    }

    /**
     * Delete the specified listing
     * DELETE /api/v1/ilanlar/{id}
     */
    public function destroy(Ilan $ilan, DestroyIlanAction $action): JsonResponse
    {
        if ($ilan->danisman_id !== auth('sanctum')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilana erişim izniniz yok',
            ], 403);
        }

        $action->handle($ilan);

        return response()->json([
            'success' => true,
            'message' => 'İlan başarıyla silindi',
        ]);
    }

    /**
     * Publish listing
     * PATCH /api/v1/ilanlar/{id}/publish
     */
    public function publish(Ilan $ilan, PublishIlanAction $action): JsonResponse
    {
        if ($ilan->danisman_id !== auth('sanctum')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilana erişim izniniz yok',
            ], 403);
        }

        $action->handle($ilan);

        return response()->json([
            'success' => true,
            'message' => 'İlan yayınlandı',
            'data' => $ilan,
        ]);
    }

    /**
     * Unpublish listing
     * PATCH /api/v1/ilanlar/{id}/unpublish
     */
    public function unpublish(Ilan $ilan, UnpublishIlanAction $action): JsonResponse
    {
        if ($ilan->danisman_id !== auth('sanctum')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilana erişim izniniz yok',
            ], 403);
        }

        $action->handle($ilan);

        return response()->json([
            'success' => true,
            'message' => 'İlan pasif duruma alındı',
            'data' => $ilan,
        ]);
    }
}
