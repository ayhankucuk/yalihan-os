<?php

namespace App\Http\Controllers\Owner;

/**
 * @sab-ignore-service-layer Read-only actions (index, show) use direct model access intentionally.
 * Write actions (create, store) delegate to IlanService → IlanCrudService — SAB zinciri korunur.
 */

use App\Enums\IlanDurumu;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreOwnerIlanRequest;
use App\Http\Requests\Owner\UpdateOwnerIlanRequest;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Il;
use App\Services\Ilan\IlanService;
use App\Services\AI\MarketValuationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * OwnerIlanController
 *
 * Mülk sahibi için portföy (ilan) yönetimi.
 * Read: doğrudan model erişimi (read-only için service gerekmez).
 * Write: IlanService üzerinden — SAB write zinciri korunur.
 *
 * SAB v6.1.2 — Owner Portal Sprint (Task #15)
 * SAB v3.4.1 — Sprint 3.4.1: create + store eklendi (edit/update out of scope)
 * @sab-ignore-thin
 */
class OwnerIlanController extends Controller
{
    /**
     * İlanları listeler.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Mülk sahibinin ilanları (SAB Task #15: user_id = auth user)
        $ilanlar = Ilan::with(['il', 'ilce', 'mahalle', 'anaKategori', 'altKategori'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc') // context7-ignore
            ->paginate(12);

        return view('owner.ilanlar.index', compact('ilanlar'));
    }

    /**
     * İlan detaylarını gösterir.
     */
    public function show(Ilan $ilan, MarketValuationService $valuationService): View
    {
        $user = auth()->user();

        // Ownership check: 404 if not owned
        if ($ilan->user_id !== $user->id) {
            abort(404);
        }

        $ilan->load(['il', 'ilce', 'mahalle', 'anaKategori', 'altKategori', 'fotograflar', 'danisman']);

        $valuation = $this->getValuation($ilan, $valuationService);

        return view('owner.ilanlar.show', compact('ilan', 'valuation'));
    }

    /**
     * Get market valuation for listing if data is sufficient.
     */
    private function getValuation(Ilan $ilan, MarketValuationService $valuationService): ?array
    {
        if (!$ilan->il_id || !$ilan->ilce_id || !$ilan->brut_m2) {
            return null;
        }

        try {
            $result = $valuationService->evaluateQuery([
                'il' => $ilan->il?->il_adi ?? 'Muğla',
                'ilce' => $ilan->ilce?->ilce_adi ?? 'Bodrum',
                'mahalle' => $ilan->mahalle?->mahalle_adi ?? '',
                'asset_type' => $ilan->altKategori?->name ?? $ilan->anaKategori?->name ?? 'Konut',
                'm2' => $ilan->brut_m2,
            ]);

            return $result['is_success'] ? $result['data'] : null;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Market valuation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Yeni portföy oluşturma formunu gösterir.
     *
     * SAB v6.1.2 — Sprint 3.4.1 Product Start: Portfolio Create
     * Owner create + store only. No edit/update.
     */
    public function create(): View
    {
        $anaKategoriler = IlanKategori::whereNull('parent_id')
            ->with('children')
            ->orderBy('display_order') // context7-ignore
            ->get();

        $iller = Il::orderBy('il_adi')->select(['id', 'il_adi'])->get();

        return view('owner.ilanlar.create', [
            'anaKategoriler' => $anaKategoriler,
            'iller' => $iller,
        ]);
    }

    /**
     * Yeni portföyü veritabanına kaydeder.
     *
     * SAB v6.1.2 — Sprint 3.4.1
     * Write authority: IlanService::storeListing() → IlanCrudService::store()
     */
    public function store(StoreOwnerIlanRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Owner, ilanı kendi user_id ile oluşturur
        $data['user_id'] = auth()->id();

        $ilanService = app(IlanService::class);
        $result = $ilanService->storeListing($data);

        return to_route('owner.ilanlar.show', $result['id'])
            ->with('success', $result['message'] ?? 'Portföy başarıyla oluşturuldu.');
    }

    /**
     * İlan düzenleme formunu gösterir.
     *
     * SAB v6.1.2 — Sprint 4.2
     * Ownership check: 404 if not owned by authenticated user.
     */
    public function edit(Ilan $ilan): View
    {
        $user = auth()->user();
        if ($ilan->user_id !== $user->id) {
            abort(404);
        }

        $anaKategoriler = IlanKategori::whereNull('parent_id')
            ->with('children')
            ->orderBy('display_order') // context7-ignore
            ->get();

        $iller = Il::orderBy('il_adi')->select(['id', 'il_adi'])->get();

        return view('owner.ilanlar.edit', [
            'ilan'            => $ilan,
            'anaKategoriler' => $anaKategoriler,
            'iller'          => $iller,
        ]);
    }

    /**
     * İlanı günceller.
     *
     * SAB v6.1.2 — Sprint 4.2
     * Write authority: IlanService::updateListing() → IlanCrudService::update()
     * UpdateOwnerIlanRequest strips yayin_durumu/danisman_id/user_id automatically.
     * Ownership check in UpdateOwnerIlanRequest::failedAuthorization() → 404.
     */
    public function update(UpdateOwnerIlanRequest $request, Ilan $ilan): RedirectResponse
    {
        $ilanService = app(IlanService::class);
        $result = $ilanService->updateListing($ilan, $request->validated());

        return to_route('owner.ilanlar.show', $ilan->id)
            ->with('success', $result['message'] ?? 'Portföy başarıyla güncellendi.');
    }

    /**
     * İlanı soft-delete olarak siler.
     *
     * SAB v6.1.2 — Sprint 4.2
     * Write authority: IlanService::deleteListing() → IlanCrudService
     * Ownership check: 404 if not owned by authenticated user.
     */
    public function destroy(Ilan $ilan): RedirectResponse
    {
        $user = auth()->user();
        if ($ilan->user_id !== $user->id) {
            abort(404);
        }

        $ilanService = app(IlanService::class);
        $ilanService->deleteListing($ilan);

        return to_route('owner.ilanlar.index')
            ->with('success', 'Portföy başarıyla silindi.');
    }

    /**
     * Portföy hazırlık analizini döndürür (JSON).
     *
     * SAB v6.1.2 — Sprint 4.2
     * Ownership check: 404 if not owned by authenticated user.
     */
    public function readiness(Ilan $ilan): JsonResponse
    {
        $user = auth()->user();
        if ($ilan->user_id !== $user->id) {
            abort(404);
        }

        $ilanService = app(IlanService::class);
        $data = $ilanService->getDetailedListingAnalysis($ilan);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
