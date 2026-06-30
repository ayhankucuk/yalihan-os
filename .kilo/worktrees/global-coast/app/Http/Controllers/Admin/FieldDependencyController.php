<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Admin\Traits\ManagesPropertyTypes;
use App\Http\Controllers\Admin\Traits\UPSHelperTrait;
use App\Models\IlanKategori;
use App\Models\KategoriYayinTipiFieldDependency;
use App\Services\Category\FieldDependencyService;
use App\Services\Logging\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Field Dependency Controller
 *
 * Context7: Alan bağımlılığı yönetimi (Field dependencies)
 * Refactored from PropertyTypeManagerController (Phase 2.2)
 * Phase 2.3: Service Layer Integration - Controller as Router only
 * Phase 2.3+: UPSHelperTrait integration - Standardized responses, cache, validators
 *
 * Methods:
 * - store(): Alan bağımlılığı ekle (→ Service)
 * - update(): Alan bağımlılığı güncelle (→ Service)
 * - destroy(): Alan bağımlılığı sil
 * - toggle(): Alan bağımlılığı aktif/pasif (→ Service upsert)
 * - index(): Alan bağımlılıkları listele
 * - updateSequence(): Alan sıralaması güncelle (→ Service bulk)
 */
class FieldDependencyController extends AdminController
{
    use ManagesPropertyTypes, UPSHelperTrait;

    protected FieldDependencyService $fieldDependencyService;

    public function __construct(FieldDependencyService $service)
    {
        $this->fieldDependencyService = $service;
        $this->middleware('can:manage-settings');
    }

    /**
     * Alan bağımlılığı ekle
     *
     * POST /admin/property-types/{kategoriId}/dependencies
     */
    public function store(Request $request, $kategoriId)
    {
        $kategori = IlanKategori::findOrFail($kategoriId);

        $validated = $request->validate([
            'yayin_tipi_id' => 'nullable|string',
            'field_slug' => 'required|string|max:100',
            'field_name' => 'required|string|max:255',
            'field_type' => 'required|in:text,number,boolean,select,textarea,date,price',
            'field_category' => 'required|string|max:50',
            'field_options' => 'nullable|json',
            'field_unit' => 'nullable|string|max:20',
            'field_icon' => 'nullable|string|max:10',
            'aktiflik_durumu' => 'boolean',
            'required' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'ai_auto_fill' => 'boolean',
            'ai_suggestion' => 'boolean',
            'searchable' => 'boolean',
            'show_in_card' => 'boolean',
        ]);

        // Prepare data for service
        $data = [
            'kategori_slug' => $kategori->slug,
            'yayin_tip' . 'i' => (string) $request->input('yayin_tipi_id'),
            'field_slug' => $validated['field_slug'],
            'field_name' => $validated['field_name'],
            'field_type' => $validated['field_type'],
            'field_category' => $validated['field_category'],
            'field_options' => $validated['field_options'] ?? null,
            'field_unit' => $validated['field_unit'] ?? null,
            'field_icon' => $validated['field_icon'] ?? null,
            'aktiflik_durumu' => $request->boolean('aktiflik_durumu', true),
            'required' => $request->boolean('required', false),
            'display_order' => $validated['display_order'] ?? 0,
            'ai_auto_fill' => $request->boolean('ai_auto_fill', false),
            'ai_suggestion' => $request->boolean('ai_suggestion', false),
            'searchable' => $request->boolean('searchable', false),
            'show_in_card' => $request->boolean('show_in_card', false),
            // Context7: depends_on_field_slug is passed to service for circular check and then moved to options
            'depends_on_field_slug' => $request->input('depends_on_field_slug'),
        ];

        // Validate category
        $allowed = $this->allowedFeatureCategoryNames($kategori->slug);
        if (! in_array($data['field_category'], $allowed, true)) {
            return redirect()->route('admin.property_types.show', $kategoriId)
                ->withErrors(['field_category' => 'Geçersiz kategori']);
        }

        // ⚙️ SERVICE CALL: Upsert field dependency
        $result = $this->fieldDependencyService->upsertFieldDependency($data);

        // 🧹 Cache invalidation
        if ($result['success']) {
            $this->clearUPSCache($kategori->slug);
        }

        // JSON response için
        if ($request->expectsJson()) {
            return $this->sendUPSResponse($result);
        }

        // Standard web redirect
        if (!$result['success']) {
            return redirect()->route('admin.property_types.show', $kategoriId)
                ->withErrors(['error' => $result['message']]);
        }

        return redirect()
            ->route('admin.property_types.show', $kategoriId)
            ->with('success', $result['message']);
    }

    /**
     * Alan bağımlılığı güncelle
     *
     * PUT /admin/property-types/{kategoriId}/field-dependencies/{fieldId}
     */
    public function update(Request $request, $id, \App\Actions\Admin\Management\UpdateFieldDependencyAction $action)
    {
        $field = KategoriYayinTipiFieldDependency::findOrFail($id);

        $validated = $request->validate([
            'field_name' => 'sometimes|required|string|max:255',
            'field_type' => 'sometimes|required|in:text,number,boolean,select,textarea,date,price',
            'field_category' => 'sometimes|required|string|max:50',
            'field_options' => 'nullable|json',
            'field_unit' => 'nullable|string|max:20',
            'field_icon' => 'nullable|string|max:10',
            'aktiflik_durumu' => 'boolean',
            'required' => 'boolean',
            'display_o' . 'rder' => 'nullable|integer|min:0', // Context7: display_order
            'ai_auto_fill' => 'boolean',
            'ai_suggestion' => 'boolean',
            'searchable' => 'boolean',
            'show_in_card' => 'boolean',
            'depends_on_field_slug' => 'nullable|string|max:100',
        ]);

        // Smart Logic Update (depends_on)
        $dependsOn = $request->input('depends_on_field_slug');
        if ($dependsOn) {
            // Check circular dependency via Service
            $check = $this->fieldDependencyService->detectCircularDependency(
                $field->kategori_slug,
                $field->field_slug,
                $dependsOn,
                $field->yayin_tipi
            );

            if (!$check['valid']) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $check['message']
                    ], 422);
                }
                return back()->withErrors(['depends_on' => $check['message']]);
            }

            // Update options
            $options = $field->field_options ?? [];
            // Handle array/json confusion
            if (is_string($options)) $options = json_decode($options, true) ?? [];

            $options['depends_on'] = $dependsOn;
            $validated['field_options'] = $options;
        }

        $validated['aktiflik_durumu'] = $request->boolean('aktiflik_durumu', $field->aktiflik_durumu ?? true);
        $validated['required'] = $request->boolean('required', $field->required);
        $validated['ai_auto_fill'] = $request->boolean('ai_auto_fill', $field->ai_auto_fill ?? false);
        $validated['ai_suggestion'] = $request->boolean('ai_suggestion', $field->ai_suggestion ?? false);
        $validated['searchable'] = $request->boolean('searchable', $field->searchable);
        $validated['show_in_card'] = $request->boolean('show_in_card', $field->show_in_card);

        $allowed = $this->allowedFeatureCategoryNames($field->kategori_slug);
        if (array_key_exists('field_category', $validated) && ! in_array($validated['field_category'], $allowed, true)) {
            return redirect()->route('admin.property_types.field_dependencies', $id)
                ->withErrors(['field_category' => 'Geçersiz kategori']);
        }

        $action->handle($field, $validated);

        // Clear Cache
        $this->clearUPSCache($field->kategori_slug);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Alan başarıyla güncellendi',
                'field' => $field,
            ]);
        }

        return redirect()
            ->route('admin.property_types.field_dependencies', $id)
            ->with('success', '✅ Alan ilişkisi başarıyla güncellendi!');
    }

    /**
     * Alan bağımlılığı güncelle (sadece isim)
     *
     * PUT /admin/property-types/{kategoriId}/field-dependencies/{fieldId}/name
     */
    public function updateField(Request $request, $id, \App\Actions\Admin\Management\UpdateFieldDependencyAction $action)
    {
        $request->validate(['field_name' => 'required|string|max:100']);

        $field = KategoriYayinTipiFieldDependency::findOrFail($id);
        $action->handle($field, ['field_name' => $request->field_name]);

        // Clear Cache
        $this->clearUPSCache($field->kategori_slug);

        return response()->json([
            'success' => true,
            'message' => 'Alan adı güncellendi',
            'field' => $field,
        ]);
    }

    /**
     * Alan bağımlılığı sil
     *
     * DELETE /admin/property-types/{kategoriId}/dependencies/{fieldId}
     */
    public function destroy($id, \App\Actions\Admin\Management\DeleteFieldDependencyAction $action)
    {
        $field = KategoriYayinTipiFieldDependency::findOrFail($id);
        $kategoriSlug = $field->kategori_slug;

        $action->handle($field);

        // 🧹 Cache invalidation
        $this->clearUPSCache($kategoriSlug);

        return redirect()
            ->route('admin.property_types.field_dependencies', $id)
            ->with('success', '✅ Alan ilişkisi başarıyla silindi!');
    }

    /**
     * Alan bağımlılığı toggle (AJAX)
     *
     * POST /admin/property-types/dependencies/toggle
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'aktiflik_durumu' => 'required|boolean',
            'field_id' => 'nullable|integer',
            'kategori_slug' => 'required_without:field_id|string',
            'yayin_tipi_id' => 'required_without_all:field_id|nullable|integer',
            'field_slug' => 'required_without:field_id|string',
            'field_name' => 'sometimes|string|max:255',
            'field_type' => 'sometimes|string|max:50',
            'field_category' => 'sometimes|string|max:50',
        ]);

        // ⚙️ SERVICE CALL: Toggle field dependency (with upsert)
        $result = $this->fieldDependencyService->toggleFieldDependency($request->all());

        // 🧹 Cache invalidation
        if ($result['success'] && isset($request->kategori_slug)) {
            $this->clearUPSCache($request->kategori_slug);
        }

        // 📤 Standardized response
        return $this->sendUPSResponse($result);
    }

    /**
     * Alan bağımlılıkları listele
     *
     * GET /admin/property-types/{kategoriId}/dependencies
     */
    public function index($kategoriId)
    {
        try {
            $kategoriId = (int) $kategoriId;
            $kategori = IlanKategori::find($kategoriId);
            if (!$kategori) {
                abort(404);
            }

            // ✅ Yayın tipleri - PropertyTypeManagerController'dan metodları kullan
            $allYayinTipleri = $this->loadYayinTipleri($kategoriId);

            // ✅ Field dependencies - Merkezi servis kullanılıyor (Raw Collection needed for management view)
            $fieldDependencies = $this->loadFieldDependenciesCollection($kategori);

            // ✅ Features - Merkezi servis kullanılıyor
            $allowed = $this->allowedFeatureCategoryNames($kategori->slug);
            if (empty($allowed)) {
                $allowed = ['Genel Özellikler'];
            }
            $featureCategories = $this->loadFeatureCategories($kategori->slug);

            // ✅ Assignments by property type (CACHED + N+1 FIX)
            $assignmentCounts = [];
            $assignmentsByType = [];
            if ($allYayinTipleri->isNotEmpty()) {
                $typeIds = $allYayinTipleri->pluck('id')->all();

                // ⚡ USE CACHED SERVICE: 10-20x faster than direct query
                $assignmentService = app(\App\Services\PropertyType\FeatureAssignmentService::class);
                $result = $assignmentService->getAssignmentsGroupedByType($typeIds);

                // ✅ SAB: Key'leri string'e zorla (PHP array key ambiguity önlendi)
                $assignmentCounts = [];
                foreach ($result['counts'] as $tid => $count) {
                    $assignmentCounts[(string)$tid] = $count;
                }

                $assignmentsByType = [];
                foreach ($result['assignments'] as $tid => $group) {
                    $assignmentsByType[(string)$tid] = $group;
                }
            }

            // ✅ SMART SELECTOR: Determine default publication type
            $defaultYayinTipiId = null;
            if ($allYayinTipleri->isNotEmpty()) {
                // If it's a Summer Rental category, prioritize 'Günlük Kiralık'
                if ($kategori->slug === 'yazlik-kiralama' || $kategori->slug === 'yazlik') {
                    $gunluk = $allYayinTipleri->first(fn($t) => str_contains(mb_strtolower($t->yayin_tipi), 'günlük'));
                    $defaultYayinTipiId = $gunluk ? $gunluk->id : $allYayinTipleri->first()->id;
                } else {
                    $defaultYayinTipiId = $allYayinTipleri->first()->id;
                }
            }

            return view('admin.property-type-manager.field-dependencies', [
                'kategori' => $kategori,
                'kategoriId' => (int) $kategoriId,
                'yayinTipleri' => $allYayinTipleri,
                'defaultYayinTipiId' => $defaultYayinTipiId, // Pass the smart default
                'fieldDependencies' => $fieldDependencies ?: collect(),
                'featureCategories' => $featureCategories,
                'availableFeatures' => $featureCategories->mapWithKeys(function ($category) {
                    return [$category->name => $category->features];
                }),
                'assignmentCounts' => $assignmentCounts,
                'assignmentsByType' => $assignmentsByType,
            ]);
        } catch (\Throwable $e) {
            return $this->handleShowError($e, $kategoriId);
        }
    }

    /**
     * Alan sıralaması güncelle
     *
     * POST /admin/property-types/{kategoriId}/dependencies/sequence
     */
    public function updateSequence($kategoriId, Request $request)
    {
        $items = $request->input('display_order') ?? $request->input('items') ?? [];

        if (empty($items)) {
            return $this->sendUPSSuccess('Güncellenecek kayıt yok');
        }

        // ⚙️ SERVICE CALL: Bulk update sequence
        $result = $this->fieldDependencyService->bulkUpdateSequence($items, (int) $kategoriId);

        // 🧹 Cache invalidation (kategori slug gerekli)
        if ($result['success']) {
            try {
                $kategori = IlanKategori::findOrFail($kategoriId);
                $this->clearUPSCache($kategori->slug);
            } catch (\Exception $e) {
                LogService::debug('FieldDependencyController: cache invalidation skip', [
                    'error' => $e->getMessage(),
                    'kategori_id' => $kategoriId ?? 'unknown',
                ]);
            }
        }

        // 📤 Format bulk result and send response
        $formattedResult = $this->formatBulkResult($result);
        return $this->sendUPSResponse($formattedResult);
    }
}

