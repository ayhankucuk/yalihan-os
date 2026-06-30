<?php

namespace App\Http\Controllers\Admin\PropertyHub;

use App\Actions\PropertyHub\ApplyPackAction;
use App\Http\Controllers\Api\Concerns\ApiResponds;
use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\FeaturePack;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\PropertyHub\PropertyHubOrchestrator;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PropertyHub Pack Controller
 *
 * Handles feature pack operations and pack application to templates.
 * Part of PropertyHub modular refactoring (Sprint 2).
 */
class PackController extends Controller
{
    use ApiResponds;

    public function __construct(
        private PropertyHubOrchestrator $hub
    ) {}

    /**
     * Feature packs list
     */
    public function index()
    {
        $packs = FeaturePack::with(['features'])
            ->withCount('features')
            ->ordered() // context7-ignore
            ->paginate(20);

        // Features for pack creation modal
        $features = Feature::where('aktiflik_durumu', true)
            ->with('category')
            ->ordered() // context7-ignore
            ->get();

        // Templates for apply modal
        $templates = YayinTipiSablonu::all();

        $kategoriler = IlanKategori::where('aktiflik_durumu', true)
            ->ordered() // context7-ignore
            ->get();

        return view('admin.property-hub.packs.index', compact('packs', 'features', 'templates', 'kategoriler'));
    }

    /**
     * Store a new feature pack
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $pack = $this->hub->createPack($request->all());
            return ResponseService::success($pack, 'Feature Pack başarıyla oluşturuldu');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseService::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseService::serverError('İşlem başarısız: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Update feature pack
     */
    public function update(Request $request, FeaturePack $pack): JsonResponse
    {
        try {
            $updated = $this->hub->updatePack($pack, $request->all());
            return ResponseService::success($updated, 'Feature Pack başarıyla güncellendi');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseService::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseService::serverError('İşlem başarısız: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Delete feature pack
     */
    public function destroy(FeaturePack $pack): JsonResponse
    {
        try {
            $this->hub->deletePack($pack);
            return ResponseService::success(null, 'Feature Pack başarıyla silindi');
        } catch (\Exception $e) {
            return ResponseService::serverError('İşlem başarısız: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Apply pack to templates
     */
    public function apply(Request $request, FeaturePack $pack, ApplyPackAction $action): JsonResponse
    {
        $validated = $request->validate([
            'yayin_tipi_ids' => 'required|array|min:1',
            'yayin_tipi_ids.*' => 'exists:yayin_tipi_sablonlari,id',
            'mode' => 'nullable|in:merge,replace',
        ]);

        try {
            $result = $this->hub->applyPackToTemplates(
                $pack,
                $validated['yayin_tipi_ids'],
                auth()->id(),
                $request->mode ?? 'merge'
            );

            return ResponseService::success($result, "Paket başarıyla uygulandı");
        } catch (\Exception $e) {
            return ResponseService::serverError('İşlem başarısız: ' . $e->getMessage(), $e);
        }
    }
}
