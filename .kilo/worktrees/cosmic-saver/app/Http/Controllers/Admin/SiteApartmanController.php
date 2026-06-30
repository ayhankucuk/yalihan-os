<?php

namespace App\Http\Controllers\Admin;
use App\Actions\Admin\SiteApartman\DeleteSiteApartmanAction;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Models\SiteApartman;
use App\Services\SiteApartmanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SiteApartmanController extends AdminController
{
    public function __construct(
        private readonly SiteApartmanService $siteService,
        private readonly DeleteSiteApartmanAction $deleteSiteApartmanAction,
    ) {}
    /**
     * Site/Apartman listesi
     */
    public function index()
    {
        $sites = $this->siteService->getPaginatedSites();

        return view('admin.site-apartman.index', compact('sites'));
    }

    /**
     * Yeni site/apartman oluşturma formu
     */
    public function create()
    {
        $iller = Il::orderBy('il_adi')->get();
        $ilceler = collect();
        $mahalleler = collect();

        return view('admin.site-apartman.create', compact('iller', 'ilceler', 'mahalleler'));
    }

    /**
     * Site/Apartman kaydetme
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'toplam_daire_sayisi' => 'nullable|integer|min:1',
            'adres' => 'nullable|string|max:500',
            'il_id' => 'nullable|exists:iller,id',
            'ilce_id' => 'nullable|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'site_ozellikleri' => 'nullable|array',
            'site_durumu' => 'required|in:active,inactive,pending',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $site = $this->siteService->create($request->all(), auth()->id());

        return redirect()
            ->route('admin.site-apartman.index')
            ->with('success', 'Site/Apartman başarıyla oluşturuldu.');
    }

    /**
     * Site/Apartman düzenleme formu
     */
    public function edit(SiteApartman $siteApartman)
    {
        $iller = Il::orderBy('il_adi')->get();

        $ilceler = $siteApartman->il_id
            ? Ilce::where('il_id', $siteApartman->il_id)->orderBy('ilce_adi')->get() // context7-ignore
            : collect();

        $mahalleler = $siteApartman->ilce_id
            ? Mahalle::where('ilce_id', $siteApartman->ilce_id)->orderBy('mahalle_adi')->get() // context7-ignore
            : collect();

        return view('admin.site-apartman.edit', compact('siteApartman', 'iller', 'ilceler', 'mahalleler'));
    }

    public function show(SiteApartman $siteApartman)
    {
        return view('admin.site-apartman.show', compact('siteApartman'));
    }

    /**
     * Site/Apartman güncelleme
     */
    public function update(Request $request, SiteApartman $siteApartman)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'toplam_daire_sayisi' => 'nullable|integer|min:1',
            'adres' => 'nullable|string|max:500',
            'il_id' => 'nullable|exists:iller,id',
            'ilce_id' => 'nullable|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'lat' => 'nullable|numeric|between:-90,90', // Context7 SEALED
            'lng' => 'nullable|numeric|between:-180,180', // Context7 SEALED
            'site_ozellikleri' => 'nullable|array',
            'site_durumu' => 'required|in:active,inactive,pending', // Context7: field naming restricted
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->siteService->update($siteApartman, $request->all(), auth()->id());

        return redirect()
            ->route('admin.site-apartman.index')
            ->with('success', 'Site/Apartman başarıyla güncellendi.');
    }

    /**
     * Site/Apartman silme
     */
    public function destroy(SiteApartman $siteApartman)
    {
        // İlan sayısını kontrol et
        if ($siteApartman->ilanlar()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Bu site/apartmana ait ilanlar bulunduğu için silinemez.');
        }

        $this->deleteSiteApartmanAction->handle($siteApartman);

        return redirect()
            ->route('admin.site-apartman.index')
            ->with('success', 'Site/Apartman başarıyla silindi.');
    }

    /**
     * AJAX: İlçeleri getir
     */
    public function getIlceler(Request $request)
    {
        $ilId = $request->input('il_id');

        if (! $ilId) {
            return response()->json(['success' => false, 'message' => 'İl ID gerekli']);
        }

        $ilceler = Ilce::where('il_id', $ilId)
            ->orderBy('ilce_adi') // context7-ignore
            ->select(['id', 'ilce_adi as name'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $ilceler,
        ]);
    }

    /**
     * AJAX: Mahalleleri getir
     */
    public function getMahalleler(Request $request)
    {
        $ilceId = $request->input('ilce_id');

        if (! $ilceId) {
            return response()->json(['success' => false, 'message' => 'İlçe ID gerekli']);
        }

        $mahalleler = Mahalle::where('ilce_id', $ilceId)
            ->orderBy('mahalle_adi') // context7-ignore
            ->select(['id', 'mahalle_adi as name'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $mahalleler,
        ]);
    }

    /**
     * AJAX: Site/Apartman arama
     */
    public function search(Request $request)
    {
        $query = $request->input('q');
        $limit = $request->input('limit', 20);

        if (! $query) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $sites = $this->siteService->search($query, $limit);

        return response()->json([
            'success' => true,
            'data' => $sites,
        ]);
    }
}
