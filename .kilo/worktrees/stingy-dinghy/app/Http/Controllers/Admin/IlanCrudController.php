<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Ilan\StoreIlanRequest;
use App\Http\Requests\Admin\Ilan\UpdateIlanRequest;
use App\Models\Ilan;
use App\Repositories\IlanRepository;
use App\Services\Ilan\IlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ilan CRUD Controller
 * Standard: ZERO CHAIN (SAB GUARD V3)
 *
 * Authorization Topology:
 *   Layer 1 (Capability): $this->authorize() — policy-based capability check
 *   Layer 2 (Ownership):  IlanRepository::findOrFail() — scoped query, 404 concealment
 *
 * Pattern: findOrFail() BEFORE authorize() to prevent existence leakage.
 */
class IlanCrudController extends AdminController
{
    public function __construct(
        private IlanService $ilanService,
        private IlanRepository $ilanRepository
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Ilan::class);

        $params  = $request->all();
        $ilanlar = $this->ilanRepository->getAdminListings($params);

        // Delegate stats/form data to service (non-scoped aggregations for admin, scoped via repo)
        $data = $this->ilanService->getAdminListingsWithStats($params);
        $data['ilanlar'] = $ilanlar;

        return view('admin.ilanlar.index', $data);
    }

    public function create(Request $request): View|JsonResponse
    {
        $this->authorize('create', Ilan::class);

        $params = $request->all();
        $data   = $this->ilanService->getWizardFormData($params);
        return view('admin.ilanlar.create-wizard', $data);
    }

    public function store(StoreIlanRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Ilan::class);

        $input = $request->validated();
        $res   = $this->ilanService->storeListing($input);

        $isJson = $request->expectsJson();
        if ($isJson) {
            return response()->json($res);
        }

        return to_route('admin.ilanlar.show', $res['id'])->with($res);
    }

    public function show(int $id): View
    {
        $ilan = $this->ilanRepository->findOrFail($id);  // Layer 2: 404 concealment
        $this->authorize('view', $ilan);                  // Layer 1: Capability check

        $data = $this->ilanService->getDetailedListingAnalysis($ilan);
        return view('admin.ilanlar.show', $data);
    }

    public function edit(int $id): View
    {
        $ilan = $this->ilanRepository->findOrFail($id);  // Layer 2: 404 concealment
        $this->authorize('update', $ilan);                // Layer 1: Capability check

        $data = $this->ilanService->getEditFormData($ilan);
        return view('admin.ilanlar.edit', $data);
    }

    public function update(UpdateIlanRequest $request, int $id): RedirectResponse|JsonResponse
    {
        $ilan = $this->ilanRepository->findOrFail($id);  // Layer 2: 404 concealment
        $this->authorize('update', $ilan);                // Layer 1: Capability check

        $input = $request->validated();
        $res   = $this->ilanService->updateListing($ilan, $input);

        $isJson = $request->expectsJson();
        if ($isJson) {
            return response()->json($res);
        }

        return to_route('admin.ilanlar.show', $ilan->id)->with($res);
    }

    public function destroy(int $id): RedirectResponse
    {
        $ilan = $this->ilanRepository->findOrFail($id);  // Layer 2: 404 concealment
        $this->authorize('delete', $ilan);                // Layer 1: Capability check

        $this->ilanService->deleteListing($ilan);
        return to_route('admin.ilanlar.index')->with('success', 'İlan silindi.');
    }

    public function getAiPriceRecommendation(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Ilan::class);

        $params = $request->all();
        $res    = $this->ilanService->getAiPriceAnalysis($params);
        return response()->json($res);
    }

    public function getTypeConfig(Request $request, int $yayinTipiId): JsonResponse
    {
        $this->authorize('viewAny', Ilan::class);

        $res = $this->ilanService->getTypeConfiguration($yayinTipiId);
        return response()->json($res);
    }

    public function ownerPrivate(int $id): JsonResponse
    {
        $ilan = $this->ilanRepository->findOrFail($id);           // Layer 2: 404 concealment
        $this->authorize('viewPrivateListingData', $ilan);         // Layer 1: Capability check

        $res = $this->ilanService->getOwnerPrivateDetails($ilan);
        return response()->json($res);
    }

    public function updatePortalIds(Request $request, int $id): JsonResponse
    {
        $ilan = $this->ilanRepository->findOrFail($id);  // Layer 2: 404 concealment
        $this->authorize('update', $ilan);                // Layer 1: Capability check

        $ids = $request->input('portal_ids', []);
        $res = $this->ilanService->updateListingPortalIds($ilan, $ids);
        return response()->json($res);
    }
}
