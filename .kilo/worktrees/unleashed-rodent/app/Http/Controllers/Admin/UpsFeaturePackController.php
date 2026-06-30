<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\FeaturePack;
use App\Models\IlanKategori;
use App\Services\Response\ResponseService;
use App\Services\Ups\UpsFeaturePackService;
use App\Services\Ups\UpsPreviewService;
use Illuminate\Http\Request;

/**
 * UPS Feature Pack Controller
 *
 * Context7 Compliance: Pack CRUD + apply operations
 */
class UpsFeaturePackController extends Controller
{
    public function __construct(
        private UpsFeaturePackService $packService,
        private UpsPreviewService $previewService
    ) {}

    public function index()
    {
        $packs = FeaturePack::with('features:id,slug,name')
            ->withCount('features')
            ->ordered() // context7-ignore
            ->get();

        $kategoriler = IlanKategori::where('parent_id', null)->get(['id', 'name']);

        return view('admin.ups.feature-packs.index', compact('packs', 'kategoriler'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'aktiflik_durumu' => 'boolean',
        ]);

        $pack = $this->packService->createPack($validated);

        return ResponseService::redirectSuccess(
            route('admin.ups.feature-packs.index'),
            "Pack '{$pack->name}' created"
        );
    }

    public function update(Request $request, FeaturePack $pack)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $this->packService->updatePack($pack, $validated);

        return ResponseService::redirectSuccess(
            route('admin.ups.feature-packs.index'),
            "Pack updated"
        );
    }

    public function toggleAktiflikDurumu(FeaturePack $pack)
    {
        $this->packService->toggleAktiflikDurumu($pack);

        return ResponseService::success([
            'aktiflik_durumu' => $pack->fresh()->aktiflik_durumu,
        ]);
    }

    public function toggleDurum(FeaturePack $pack)
    {
        return $this->toggleAktiflikDurumu($pack);
    }

    public function addFeature(Request $request, FeaturePack $pack)
    {
        $validated = $request->validate([
            'feature_id' => 'required|exists:features,id',
            'display_order' => 'integer|min:0',
        ]);

        $feature = Feature::findOrFail($validated['feature_id']);
        $added = $this->packService->addFeatureToPack(
            $pack,
            $feature,
            $validated['display_order'] ?? 0
        );

        return ResponseService::success([
            'added' => $added,
        ], $added ? 'Feature added to pack' : 'Feature already in pack');
    }

    public function removeFeature(FeaturePack $pack, Feature $feature)
    {
        $removed = $this->packService->removeFeatureFromPack($pack, $feature);

        return ResponseService::success([
            'removed' => $removed,
        ], $removed ? 'Feature removed from pack' : 'Feature not found in pack');
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'pack_id' => 'required|exists:ups_feature_packs,id',
            'kategori_id' => 'required|integer',
            'yayin_tipi_ids' => 'required|array',
            'yayin_tipi_ids.*' => 'integer',
            'mode' => 'required|in:merge,replace',
        ]);

        $preview = $this->previewService->previewPackApply(
            $validated['pack_id'],
            $validated['kategori_id'],
            $validated['yayin_tipi_ids'],
            $validated['mode']
        );

        return ResponseService::success($preview, 'Preview generated');
    }

    public function apply(Request $request)
    {
        $validated = $request->validate([
            'pack_id' => 'required|exists:ups_feature_packs,id',
            'kategori_id' => 'required|integer',
            'yayin_tipi_ids' => 'required|array',
            'yayin_tipi_ids.*' => 'integer',
            'mode' => 'required|in:merge,replace',
        ]);

        $pack = FeaturePack::findOrFail($validated['pack_id']);

        $report = $this->packService->applyPack(
            $pack,
            $validated['kategori_id'],
            $validated['yayin_tipi_ids'],
            $validated['mode']
        );

        return ResponseService::success($report, 'Pack applied successfully');
    }
}
