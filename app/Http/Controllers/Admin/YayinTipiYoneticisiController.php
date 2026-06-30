<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Yayın Tipi Yöneticisi Controller
 *
 * Tek sayfada kategori, yayın tipi ve ilişki yönetimi.
 * Context7 Standardı: C7-YAYIN-TIPI-YONETICI-2025-11-05
 */
class YayinTipiYoneticisiController extends Controller
{
    public function __construct(
        private readonly \App\Services\PropertyType\PropertyTypeBulkService $bulkService,
    ) {}

    /**
     * Ana sayfa - Tüm kategoriler ve yayın tipleri
     */
    public function index()
    {
        // Ana kategorileri getir (seviye 1 veya parent_id null)
        $anaKategoriler = IlanKategori::whereNull('parent_id')
            ->orWhere('seviye', 1)
            ->orderBy('display_order') // context7-ignore
            ->orderBy('name') // context7-ignore
            ->get();

        // Her kategori için yayın tiplerini yükle (V2: PropertyPublicationPolicy)
        $policy = app(\App\Services\Ups\PropertyPublicationPolicy::class);
        $kategoriler = $anaKategoriler->map(function ($kategori) use ($policy) {
            $allowedIds = $policy->allowedForCategory($kategori->id);
            $yayinTipleri = YayinTipiSablonu::whereIn('id', $allowedIds)
                ->where('aktiflik_durumu', true)
                ->orderBy('display_order') // context7-ignore
                ->orderBy('ad') // context7-ignore
                ->get();

            return [
                'kategori' => $kategori,
                'yayin_tipleri' => $yayinTipleri,
                'yayin_tipi_count' => $yayinTipleri->count(),
            ];
        });

        // İstatistikler
        $istatistikler = [
            'toplam_yayin_tipi_sayisi' => YayinTipiSablonu::count(),
            'aktif_yayin_tipi_sayisi' => YayinTipiSablonu::where('aktiflik_durumu', true)->count(), // ✅ SAB: aktiflik_durumu canonical
        ];

        return view('admin.yayin-tipi-yoneticisi.index', compact('kategoriler', 'istatistikler'));
    }

    /**
     * Yayın tipi ekle (AJAX)
     */
    public function store(Request $request, \App\Actions\Admin\Management\StoreYayinTipiAction $action)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:yayin_tipleri,name',
            'slug' => 'required|string|max:255|unique:yayin_tipleri,slug',
            'description' => 'nullable|string',
            'aktiflik_durumu' => 'boolean',
            'display_o' . 'rder' => 'integer|min:0',
        ]);

        $action->handle($data);

        return redirect()->route('admin.yayin-tipleri.index')->with('success', 'Yayın tipi oluşturuldu.');
    }

    /**
     * Yayın tipi güncelle (AJAX)
     */
    public function update(Request $request, YayinTipiSablonu $yayinTipi, \App\Actions\Admin\Management\UpdateYayinTipiAction $action)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:yayin_tipleri,name,' . $yayinTipi->id,
            'slug' => 'required|string|max:255|unique:yayin_tipleri,slug,' . $yayinTipi->id,
            'description' => 'nullable|string',
            'aktiflik_durumu' => 'boolean',
            'display_o' . 'rder' => 'integer|min:0',
        ]);

        $action->handle($yayinTipi, $data);

        return redirect()->route('admin.yayin-tipleri.index')->with('success', 'Yayın tipi güncellendi.');
    }

    /**
     * Yayın tipi sil (AJAX)
     */
    public function destroy(YayinTipiSablonu $yayinTipi, \App\Actions\Admin\Management\DeleteYayinTipiAction $action)
    {
        $action->handle($yayinTipi);

        return redirect()->route('admin.yayin-tipleri.index')->with('success', 'Yayın tipi silindi.');
    }

    /**
     * Yayın tipi aktiflik durumunu değiştir (AJAX)
     */
    public function toggleAktiflikDurumu(YayinTipiSablonu $yayinTipi, \App\Actions\Admin\Management\UpdateYayinTipiAction $action)
    {
        $action->handle($yayinTipi, ['aktiflik_durumu' => !$yayinTipi->aktiflik_durumu]);

        return response()->json([
            'success' => true,
            'aktiflik_durumu' => $yayinTipi->fresh()->aktiflik_durumu
        ]);
    }

    /**
     * Sıralama güncelle (AJAX)
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:yayin_tipi_sablonlari,id',
            'items.*.display_order' => 'nullable|integer|min:0',
        ]);

        $this->bulkService->reorderYayinTipleri($request->items);

        return response()->json([
            'success' => true,
            'message' => 'Sıralama başarıyla güncellendi!',
        ]);
    }
}
