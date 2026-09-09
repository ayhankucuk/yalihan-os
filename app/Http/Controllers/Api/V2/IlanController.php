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
use App\Http\Resources\IlanPublicDetailResource;
use App\Scopes\TenantScope;
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
     *
     * Politika (ADR-Ilan-Erisim-Politikasi):
     *   - Yayınlanmış ilan → 200 + TIKLANABİLİR alanlar
     *   - Taslak/yayınlanmamış ilan → 404 (kayıt yokmuş gibi)
     *   - Cross-tenant yayınlanmış ilan → 200 (kamu verisi)
     *   - Cross-tenant özel/yazma → 404
     *
     * Auth kullanıcı, kendi ilanının detayında tam alanları alır;
     * başka tenant'ın yayınlanmış ilanında TIKLANABİLİR alanları alır.
     */
    public function show(Request $request, $id): IlanPublicDetailResource|IlanDetailResource|JsonResponse
    {
        $ilan = Ilan::withoutGlobalScope(TenantScope::class)
            ->with(['il', 'ilce', 'mahalle', 'fotograflar', 'danisman', 'anaKategori'])
            ->find($id);

        if (!$ilan) {
            return response()->json(['message' => 'İlan bulunamadı'], 404);
        }

        // Auth kullanıcı kontrolü
        $user = auth('sanctum')->user();
        $isOwner = $user && $ilan->danisman_id === $user->id;
        $isSameTenant = $user && (int) $ilan->tenant_id === (int) $user->tenant_id;

        // Yayınlanmamış ilanlar sadece kendi tenant'ı tarafından görüntülenebilir
        if ($ilan->yayin_durumu !== IlanDurumu::YAYINDA->value && !$isSameTenant) {
            return response()->json(['message' => 'İlan bulunamadı'], 404);
        }

        // Yayınlanmış ilan — herkes 200 alır
        if ($user && ($isOwner || $isSameTenant)) {
            $request->attributes->set('ilan_detail_full', true);
            return new IlanDetailResource($ilan);
        }

        return new IlanPublicDetailResource($ilan);
    }

    /**
     * Update the specified listing
     * PUT /api/v1/ilanlar/{id}
     *
     * ⚠️ Implicit route model binding KULLANILMAZ — TenantScope fail-closed davranışı
     * nedeniyle controller method'undan ÖNCE patlar ve 404 döner.
     * Bu yüzden explicit query + withoutGlobalScope(TenantScope::class) kullanılır.
     * @see IlanController::show() — aynı pattern orada zaten mevcut
     */
    public function update(Request $request, $id, UpdateIlanAction $action): JsonResponse
    {
        $ilan = Ilan::withoutGlobalScope(TenantScope::class)->find($id);

        if (!$ilan) {
            return response()->json(['message' => 'İlan bulunamadı'], 404);
        }

        if ($authError = $this->authorizeIlanAccess($ilan)) {
            return $authError;
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
     *
     * ⚠️ Implicit route model binding KULLANILMAZ — TenantScope fail-closed.
     * @see IlanController::update() açıklama bloğu
     */
    public function destroy($id, DestroyIlanAction $action): JsonResponse
    {
        $ilan = Ilan::withoutGlobalScope(TenantScope::class)->find($id);

        if (!$ilan) {
            return response()->json(['message' => 'İlan bulunamadı'], 404);
        }

        if ($authError = $this->authorizeIlanAccess($ilan)) {
            return $authError;
        }

        $action->handle($ilan);

        return response()->json(null, 204);
    }

    /**
     * Publish listing
     * PATCH /api/v1/ilanlar/{id}/publish
     *
     * ⚠️ Implicit route model binding KULLANILMAZ — TenantScope fail-closed.
     * @see IlanController::update() açıklama bloğu
     */
    public function publish($id, PublishIlanAction $action): JsonResponse
    {
        $ilan = Ilan::withoutGlobalScope(TenantScope::class)->find($id);

        if (!$ilan) {
            return response()->json(['message' => 'İlan bulunamadı'], 404);
        }

        if ($authError = $this->authorizeIlanAccess($ilan)) {
            return $authError;
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
     *
     * ⚠️ Implicit route model binding KULLANILMAZ — TenantScope fail-closed.
     * @see IlanController::update() açıklama bloğu
     */
    public function unpublish($id, UnpublishIlanAction $action): JsonResponse
    {
        $ilan = Ilan::withoutGlobalScope(TenantScope::class)->find($id);

        if (!$ilan) {
            return response()->json(['message' => 'İlan bulunamadı'], 404);
        }

        if ($authError = $this->authorizeIlanAccess($ilan)) {
            return $authError;
        }

        $action->handle($ilan);

        return response()->json([
            'success' => true,
            'message' => 'İlan pasif duruma alındı',
            'data' => $ilan,
        ]);
    }

    /**
     * 🛡️ Tenant İzolasyonu ve Danışman Yetki Kontrolü
     *
     * 1. Cross-tenant istekler → 404 (ID Enumeration zafiyeti engellenir; kayıt yokmuş gibi davranılır)
     * 2. Aynı tenant, fakat farklı danışman → 403 (Kullanıcı kendi şirketinin ilanına yetkisizdir)
     */
    private function authorizeIlanAccess(Ilan $ilan): ?JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user || (int) $ilan->tenant_id !== (int) $user->tenant_id) {
            return response()->json([
                'message' => 'İlan bulunamadı',
            ], 404);
        }

        if ($ilan->danisman_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilana erişim izniniz yok',
            ], 403);
        }

        return null;
    }
}
