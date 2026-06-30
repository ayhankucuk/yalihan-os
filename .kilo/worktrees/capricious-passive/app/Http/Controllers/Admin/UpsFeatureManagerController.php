<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\FeatureCategory;
use App\Services\Logging\LogService;
use App\Services\Response\ResponseService;
use App\Actions\Ilan\Feature\StoreFeatureAction;
use App\Actions\Ilan\Feature\UpdateFeatureAction;
use App\Actions\PropertyHub\ToggleFeatureAction;
use Illuminate\Http\Request;

/**
 * UPS Feature Manager Controller
 *
 * @deprecated 2026-01-25 Routes consolidated into PropertyHubController.
 * Use admin.property-hub.features.* routes instead.
 * This controller is kept for backwards compatibility only.
 * ⚠️ QUARANTINE(DS-04): Do not add new methods. Legacy shell only.
 *
 * Context7 Compliance: Feature CRUD admin
 * - Thin controller, delegates to Feature model
 * - NO content_type in logs
 */
class UpsFeatureManagerController extends Controller
{
    public function index(Request $request)
    {
        $features = Feature::query()
            ->with('category:id,name')
            ->when($request->ozellik_durumu, fn($q, $ozellikDurumu) => $q->where('aktiflik_durumu', (bool)$ozellikDurumu))
            ->when($request->lifecycle, fn($q, $lifecycle) => $q->where('lifecycle', $lifecycle))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($request->category, fn($q, $categoryId) => $q->where('feature_category_id', $categoryId))
            ->when($request->type, fn($q, $type) => $q->where('type', $type)) // context7-ignore
            ->orderBy('slug') // context7-ignore
            ->paginate(50);

        $categories = FeatureCategory::where('aktiflik_durumu', true)
            ->orderBy('display_order') // context7-ignore
            ->orderBy('name') // context7-ignore
            ->get(['id', 'name', 'slug']);

        return view('admin.ups.features.index', [
            'features' => $features,
            'categories' => $categories,
            'filters' => $request->only(['aktiflik_durumu', 'lifecycle', 'search', 'category', 'type']), // context7-ignore
        ]);
    }

    public function create()
    {
        $categories = FeatureCategory::where('aktiflik_durumu', true)
            ->orderBy('display_order') // context7-ignore
            ->orderBy('name') // context7-ignore
            ->get(['id', 'name', 'slug']);

        return view('admin.ups.features.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100|unique:features,slug',
            'type' => 'required|in:text,number,boolean,date,select,multiselect', // context7-ignore
            'description' => 'nullable|string',
            'unit' => 'nullable|string|max:50',
            'options' => 'nullable|array',
            'feature_category_id' => 'nullable|exists:feature_categories,id',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'is_searchable' => 'boolean',
            'aktiflik_durumu' => 'boolean',
        ]);

        $feature = app(StoreFeatureAction::class)->handle($validated);

        LogService::info('UPS Feature created via admin', [
            'feature_id' => $feature->id,
            'feature_slug' => $feature->slug,
            'user_id' => auth()->id(),
        ]);

        return ResponseService::redirectSuccess(
            'admin.ups.features.index',
            "Feature '{$feature->slug}' created successfully"
        );
    }

    public function edit(Feature $feature)
    {
        return response()->json($feature);
    }

    public function update(Request $request, Feature $feature)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,number,boolean,date,select,multiselect', // context7-ignore
            'description' => 'nullable|string',
            'unit' => 'nullable|string|max:50',
            'options' => 'nullable|array',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'is_searchable' => 'boolean',
        ]);

        $changes = array_keys(array_diff_assoc($validated, $feature->only(array_keys($validated))));

        app(UpdateFeatureAction::class)->handle($feature, $validated);

        LogService::info('UPS Feature updated via admin', [
            'feature_id' => $feature->id,
            'feature_slug' => $feature->slug,
            'changed_fields' => $changes,
            'user_id' => auth()->id(),
        ]);

        return ResponseService::redirectSuccess(
            'admin.ups.features.index',
            "Feature '{$feature->slug}' updated successfully"
        );
    }

    public function toggleDurum(Feature $feature)
    {
        app(ToggleFeatureAction::class)->handle($feature, auth()->id());

        LogService::info('UPS Feature aktiflik_durumu toggled', [
            'feature_id' => $feature->id,
            'feature_slug' => $feature->slug,
            'new_durum' => $feature->aktiflik_durumu,
            'user_id' => auth()->id(),
        ]);

        return ResponseService::success([
            'aktiflik_durumu' => $feature->aktiflik_durumu,
        ], "Durum updated to " . ($feature->aktiflik_durumu ? 'active' : 'inactive')); // context7-ignore
    }
    public function dependencies()
    {
        // Placeholder for Feature Dependencies UI
        return view('admin.ups.features.dependencies');
    }
}
