<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Models\Deprecated\ConfigOption;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Helpers\ConfigOptionHelper;
use App\Actions\Admin\Config\UpdateConfigOptionAction;
use App\Actions\Admin\Config\DuplicateConfigOptionAction;
use App\Rules\ConfigOptionValue;
use App\Services\Ups\PropertyPublicationPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Config Option Controller
 *
 * Kategori ve Yayın Tipi bazlı config seçenekleri yönetimi
 * Context7: C7-CONFIG-OPTIONS-CONTROLLER-2025-12-15
 */
class ConfigOptionController extends AdminController
{
    /**
     * Config seçeneklerini listele
     */
    public function index(Request $request)
    {
        $query = ConfigOption::with(['kategori', 'yayinTipi']);

        $ayarDurumu = $request->input('ayar_durumu', $request->input('aktiflik_durumu'));

        // Filtreler
        if ($request->filled('kategori_id')) {
            $query->forKategori($request->kategori_id);
        }

        if ($request->filled('yayin_tipi_id')) {
            $query->forYayinTipi($request->yayin_tipi_id);
        }

        if ($request->filled('option_key')) {
            $query->forOptionKey($request->option_key);
        }

        if ($ayarDurumu) {
            if ($ayarDurumu === 'active') { // context7-ignore
                $query->active(); // context7-ignore
            } elseif ($ayarDurumu === 'passive') {
                $query->where('aktiflik_durumu', false); // ✅ SAB uyumlu
            }
        } else {
            $query->active(); // context7-ignore
        }

        $configOptions = $query->ordered()->paginate(20); // context7-ignore

        // Filtreleme için veriler
        $kategoriler = IlanKategori::where('aktiflik_durumu', true) // ✅ SAB uyumlu
            ->whereNull('parent_id')
            ->orderBy('name') // context7-ignore
            ->get();

        // UPS policy ile yayın tipi listesini daralt
        $yayinTipleri = $this->getFilteredYayinTipleri($request->input('kategori_id'));

        // Tüm option key'leri (unique)
        $optionKeys = ConfigOption::select('option_key')
            ->distinct()
            ->orderBy('option_key') // context7-ignore
            ->pluck('option_key');

        return view('admin.config-options.index', compact(
            'configOptions',
            'kategoriler',
            'yayinTipleri',
            'optionKeys'
        ));
    }

    /**
     * Yeni config seçeneği formu
     */
    public function create()
    {
        $kategoriler = IlanKategori::where('aktiflik_durumu', true) // ✅ SAB uyumlu
            ->whereNull('parent_id')
            ->orderBy('name') // context7-ignore
            ->get();

        $yayinTipleri = YayinTipiSablonu::where('aktiflik_durumu', true) // ✅ SAB uyumlu
            ->with('kategori')
            ->orderBy('kategori_id') // context7-ignore
            ->orderBy('yayin_tipi') // context7-ignore
            ->get();

        return view('admin.config-options.create', compact('kategoriler', 'yayinTipleri'));
    }

    /**
     * Yeni config seçeneği kaydet
     */
    public function store(Request $request)
    {
        // ✅ option_type kontrolü
        $optionType = $request->input('option_type');
        if (!$optionType) {
            // AJAX istekleri için JSON response
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'option_type alanı gereklidir',
                    'errors' => ['option_type' => ['option_type alanı gereklidir']]
                ], 422);
            }
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['option_type' => 'option_type alanı gereklidir']);
        }

        $request->merge([
            'aktiflik_durumu' => $request->boolean('aktiflik_durumu', true),
        ]);

        $validated = $request->validate([
            'option_key' => 'required|string|max:255',
            'option_type' => 'required|in:simple,associative,object_array,nested',
            'option_value' => ['required', new ConfigOptionValue($optionType)],
            'kategori_id' => 'nullable|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'nullable|exists:yayin_tipi_sablonlari,id',
            'label' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'aktiflik_durumu' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        $validated['aktiflik_durumu'] = (bool) ($validated['aktiflik_durumu'] ?? true);

        // Option value'yu JSON'a çevir
        if (is_string($validated['option_value'])) {
            $decoded = json_decode($validated['option_value'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // ✅ AJAX istekleri için JSON response
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Geçersiz JSON formatı',
                        'errors' => ['option_value' => ['Geçersiz JSON formatı']]
                    ], 422);
                }
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['option_value' => 'Geçersiz JSON formatı']);
            }
            $validated['option_value'] = $decoded;
        }

        $configOption = app(StoreConfigOptionAction::class)->handle($validated);

        // ✅ AJAX istekleri için JSON response
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Config seçeneği başarıyla oluşturuldu.',
                'data' => $configOption->load(['kategori', 'yayinTipi'])
            ], 201);
        }

        return redirect()
            ->route('admin.config-options.index')
            ->with('success', 'Config seçeneği başarıyla oluşturuldu.');
    }

    /**
     * Config seçeneği düzenleme formu
     */
    public function edit($id)
    {
        $configOption = ConfigOption::findOrFail($id);

        $kategoriler = IlanKategori::where('aktiflik_durumu', true) // ✅ SAB uyumlu
            ->whereNull('parent_id')
            ->orderBy('name') // context7-ignore
            ->get();

        $yayinTipleri = YayinTipiSablonu::where('aktiflik_durumu', true) // ✅ SAB uyumlu
            ->with('kategori')
            ->orderBy('kategori_id') // context7-ignore
            ->orderBy('yayin_tipi') // context7-ignore
            ->get();

        return view('admin.config-options.edit', compact('configOption', 'kategoriler', 'yayinTipleri'));
    }

    /**
     * Config seçeneği güncelle
     */
    public function update(Request $request, $id)
    {
        $configOption = ConfigOption::findOrFail($id);

        // ✅ option_type yoksa mevcut değeri kullan (FormData'dan gelmeyebilir)
        $optionType = $request->input('option_type');
        if (!$optionType) {
            $optionType = $configOption->option_type;
            // Request'e ekle ki validation geçsin
            $request->merge(['option_type' => $optionType]);
        }

        $request->merge([
            'aktiflik_durumu' => $request->boolean('aktiflik_durumu', $configOption->aktiflik_durumu),
        ]);

        $validated = $request->validate([
            'option_key' => 'required|string|max:255',
            'option_type' => 'required|in:simple,associative,object_array,nested',
            'option_value' => ['required', new ConfigOptionValue($optionType)],
            'kategori_id' => 'nullable|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'nullable|exists:yayin_tipi_sablonlari,id',
            'label' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'aktiflik_durumu' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        // Status is now directly mapped from aktiflik_durumu
        // Legacy support removed
        unset($validated['aktiflik_durumu']);

        // Option value'yu JSON'a çevir
        if (is_string($validated['option_value'])) {
            $decoded = json_decode($validated['option_value'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // ✅ AJAX istekleri için JSON response
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Geçersiz JSON formatı',
                        'errors' => ['option_value' => ['Geçersiz JSON formatı']]
                    ], 422);
                }
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['option_value' => 'Geçersiz JSON formatı']);
            }
            $validated['option_value'] = $decoded;
        }

        app(UpdateConfigOptionAction::class)->handle($configOption, $validated);

        // ✅ AJAX istekleri için JSON response
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Config seçeneği başarıyla güncellendi.',
                'data' => $configOption->fresh(['kategori', 'yayinTipi'])
            ]);
        }

        return redirect()
            ->route('admin.config-options.index')
            ->with('success', 'Config seçeneği başarıyla güncellendi.');
    }

    /**
     * Config seçeneği sil
     */
    public function destroy(Request $request, $id)
    {
        $configOption = ConfigOption::findOrFail($id);

        app(DeleteConfigOptionAction::class)->handle($configOption);

        // ✅ AJAX istekleri için JSON response
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Config seçeneği başarıyla silindi.'
            ]);
        }

        return redirect()
            ->route('admin.config-options.index')
            ->with('success', 'Config seçeneği başarıyla silindi.');
    }

    /**
     * Config seçeneğini kopyala
     */
    public function duplicate(Request $request, $id)
    {
        $original = ConfigOption::findOrFail($id);

        $duplicate = app(DuplicateConfigOptionAction::class)->handle($original);

        // ✅ AJAX istekleri için JSON response
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Config seçeneği kopyalandı.',
                'data' => [
                    'id' => $duplicate->id,
                    'label' => $duplicate->label
                ]
            ]);
        }

        return redirect()
            ->route('admin.config-options.edit', $duplicate->id)
            ->with('success', 'Config seçeneği kopyalandı. Lütfen kategori/yayın tipi ve değerleri düzenleyin.');
    }

    /**
     * UPS policy ile filtrelenmiş yayın tipi listesini getir
     */
    private function getFilteredYayinTipleri(?int $kategoriId)
    {
        $baseQuery = YayinTipiSablonu::where('aktiflik_durumu', true) // ✅ SAB uyumlu
            ->with('kategori')
            ->orderBy('kategori_id') // context7-ignore
            ->orderBy('yayin_tipi'); // context7-ignore

        if (!$kategoriId) {
            return $baseQuery->get();
        }

        $kategoriId = (int) $kategoriId;

        /** @var PropertyPublicationPolicy $policy */
        $policy = app(PropertyPublicationPolicy::class);

        if ($policy->hasExplicitPolicy($kategoriId)) {
            $types = $policy->getAllowedTypes($kategoriId);

            return $types->load('kategori');
        }

        // Policy yoksa mevcut davranış: tüm aktif yayın tipleri
        return $baseQuery->get();
    }

    /**
     * Config seçeneğini görüntüle
     */
    public function show($id)
    {
        $configOption = ConfigOption::with(['kategori', 'yayinTipi'])->findOrFail($id);

        return view('admin.config-options.show', compact('configOption'));
    }
}
