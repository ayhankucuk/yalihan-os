<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Services\Location\AdresCacheService;
use App\Services\Location\AdresBulkService;
use App\Services\Location\AdresLocationService;
use App\Services\Location\TurkiyeAPILocationSyncService;
use App\Services\TurkiyeAPIService;
use Illuminate\Http\Request;

class AdresYonetimiController extends AdminController
{
    public function __construct(
        private readonly TurkiyeAPIService $turkiyeAPI,
        private readonly TurkiyeAPILocationSyncService $syncService,
        private readonly AdresCacheService $adresCache,
        private readonly AdresBulkService $bulkService,
        private readonly AdresLocationService $adresLocationService
    ) {
    }

    public function index()
    {
        $ulkeler = $this->adresCache->ulkeler();
        $iller = $this->adresCache->iller();

        return view('admin.adres-yonetimi.index', compact('ulkeler', 'iller'));
    }

    public function provinces()
    {
        $provinces = $this->adresLocationService->getProvincesWithDistrictCounts();

        return response()->json($provinces);
    }

    public function districts($provinceApiId)
    {
        $districts = $this->adresLocationService->getDistrictsByProvinceApiId($provinceApiId);

        return response()->json($districts);
    }

    public function neighborhoods($districtApiId)
    {
        $neighborhoods = $this->adresLocationService->getNeighborhoodsByDistrictApiId($districtApiId);

        return response()->json($neighborhoods);
    }

    public function updateNeighborhood(Request $request, $id)
    {
        $request->validate([
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        // ✅ SAB: Mutation delegated to AdresLocationService
        $neighborhood = $this->adresLocationService->updateMahalleCoordinates(
            (int) $id,
            $request->lat,
            $request->lng,
        );

        return response()->json($neighborhood);
    }

    public function syncAll()
    {
        $syncService = new \App\Services\AddressSyncService();

        // Sync all provinces
        $provinceResult = $syncService->syncProvinces();

        // For each province, sync districts
        $districtResults = [];
        $provinces = $this->adresLocationService->getIllerWithApiIds();
        foreach ($provinces as $province) {
            $districtResults[] = $syncService->syncDistricts($province->api_id);

            // For each district, sync neighborhoods
            $districts = $this->adresLocationService->getIlcelerWithApiIdsByIlId($province->id);
            foreach ($districts as $district) {
                $syncService->syncNeighborhoods($district->api_id);
            }
        }

        $totalCreated = $provinceResult['created'] + array_sum(array_column($districtResults, 'created'));
        $totalUpdated = $provinceResult['updated'] + array_sum(array_column($districtResults, 'updated'));

        return response()->json([
            'created' => $totalCreated,
            'updated' => $totalUpdated,
        ]);
    }

    public function show($type, $id)
    {
        try {
            switch ($type) {
                case 'ulke':
                    $item = Ulke::findOrFail($id);
                    $relatedData = [
                        'iller_count' => Il::count(), // Context7: ulke_id kolonu olmadığı için tüm illeri say
                        'type' => 'Ülke', // context7-ignore
                        'name' => $item->ulke_adi,
                    ];
                    break;

                case 'il':
                    // ✅ N+1 FIX: Select optimization
                    $item = $this->adresLocationService->getIl($id);
                    $relatedData = [
                        'ilceler_count' => $this->adresLocationService->getIlceCountByIlId($id),
                        'type' => 'İl', // context7-ignore
                        'name' => $item->il_adi,
                    ];
                    break;

                case 'ilce':
                    // ✅ N+1 FIX: Eager loading + Select optimization
                    $item = $this->adresLocationService->getIlceWithIl($id);
                    $relatedData = [
                        'mahalleler_count' => $this->adresLocationService->getMahalleCountByIlceId($id),
                        'parent_name' => $item->il->il_adi ?? 'Bilinmiyor',
                        'type' => 'İlçe', // context7-ignore
                        'name' => $item->ilce_adi,
                    ];
                    break;

                case 'mahalle':
                    // ✅ N+1 FIX: Eager loading + Select optimization
                    $item = $this->adresLocationService->getMahalleWithParents($id);
                    $relatedData = [
                        'parent_name' => $item->ilce->ilce_adi ?? 'Bilinmiyor',
                        'grandparent_name' => $item->ilce->il->il_adi ?? 'Bilinmiyor',
                        'type' => 'Mahalle', // context7-ignore
                        'name' => $item->mahalle_adi,
                    ];
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Geçersiz tür',
                    ], 422);
            }

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'item' => $item,
                    'related_data' => $relatedData,
                ]);
            }

            return view('admin.adres-yonetimi.show', compact('item', 'relatedData', 'type')); // context7-ignore
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Öğe bulunamadı: ' . $e->getMessage(),
                ], 404);
            }

            return redirect()->route('admin.adres-yonetimi.index')
                ->with('error', 'Öğe bulunamadı: ' . $e->getMessage());
        }
    }

    /**
     * Yeni adres öğesi oluşturma formu
     * Context7: Lokasyon sistemi yeni öğe ekleme
     */
    public function create($type)
    {
        try {
            $parentOptions = [];

            switch ($type) {
                case 'ulke':
                    // Ülke için parent yok
                    break;

                case 'il':
                    // ✅ CACHE: Ülkeler için cache ekle
                    $parentOptions = $this->adresCache->ulkeler();
                    break;

                case 'ilce':
                    // ✅ CACHE: İller için cache ekle
                    $parentOptions = $this->adresCache->iller();
                    break;

                case 'mahalle':
                    // ✅ CACHE: İlçeler için cache ekle (tüm ilçeler)
                    $parentOptions = $this->adresCache->tumIlceler();
                    break;

                default:
                    return redirect()->route('admin.adres-yonetimi.index')
                        ->with('error', 'Geçersiz tür');
            }

            return view('admin.adres-yonetimi.create', compact('type', 'parentOptions')); // context7-ignore
        } catch (\Exception $e) {
            return redirect()->route('admin.adres-yonetimi.index')
                ->with('error', 'Form yüklenirken hata: ' . $e->getMessage());
        }
    }

    /**
     * Adres öğesi düzenleme formu
     * Context7: Lokasyon sistemi öğe düzenleme
     */
    public function edit($type, $id)
    {
        try {
            $parentOptions = [];

            switch ($type) {
                case 'ulke':
                    // ✅ N+1 FIX: Select optimization
                    $item = Ulke::select(['id', 'ulke_adi'])->findOrFail($id);
                    break;

                case 'il':
                    // ✅ N+1 FIX: Select optimization
                    $item = Il::select(['id', 'il_adi'])->findOrFail($id);
                    // ✅ CACHE: Ülkeler için cache ekle
                    $parentOptions = $this->adresCache->ulkeler();
                    break;

                case 'ilce':
                    // ✅ N+1 FIX: Select optimization
                    $item = Ilce::select(['id', 'il_id', 'ilce_adi'])->findOrFail($id);
                    // ✅ CACHE: İller için cache ekle
                    $parentOptions = $this->adresCache->iller();
                    break;

                case 'mahalle':
                    // ✅ N+1 FIX: Select optimization
                    $item = Mahalle::select(['id', 'ilce_id', 'mahalle_adi'])->findOrFail($id);
                    // ✅ CACHE: İlçeler için cache ekle
                    $parentOptions = $this->adresCache->tumIlceler();
                    break;

                default:
                    return redirect()->route('admin.adres-yonetimi.index')
                        ->with('error', 'Geçersiz tür');
            }

            return view('admin.adres-yonetimi.edit', compact('item', 'type', 'parentOptions')); // context7-ignore
        } catch (\Exception $e) {
            return redirect()->route('admin.adres-yonetimi.index')
                ->with('error', 'Öğe bulunamadı: ' . $e->getMessage());
        }
    }

    public function getUlkeler()
    {
        $ulkeler = $this->adresCache->ulkeler();

        return response()->json(['success' => true, 'ulkeler' => $ulkeler]);
    }

    public function getBolgeler()
    {
        return response()->json(['success' => true, 'bolgeler' => []]);
    }

    public function getIller()
    {
        // ✅ CACHE: İller için cache ekle (7200s = 2 saat)
        $iller = $this->adresCache->iller();

        // Context7: Eğer veritabanında il yoksa, TurkiyeAPI'den otomatik çek
        if ($iller->isEmpty()) {
            try {
                Log::info('TurkiyeAPI: Veritabanında il yok, otomatik sync başlatılıyor...');

                // Delegation
                $count = $this->syncService->sync('provinces')['provinces'] ?? 0;

                if ($count > 0) {
                    $this->adresCache->invalidateIller();
                    $iller = $this->adresCache->iller();
                }

            } catch (\Exception $e) {
                Log::error('TurkiyeAPI: Otomatik sync hatası', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Hata durumunda boş array döndür (kullanıcı manuel sync yapabilir)
            }
        }

        return response()->json(['success' => true, 'iller' => $iller]);
    }

    public function getIllerByUlke($ulkeId)
    {
        // Context7: iller tablosunda ulke_id kolonu yok - tüm illeri döndür
        // NOT: Eğer ulke filtrelemesi gerekiyorsa, migration ile ulke_id kolonu eklenmeli
        $iller = Il::orderBy('il_adi')->get(['id', 'il_adi']);

        return response()->json(['success' => true, 'iller' => $iller]);
    }

    public function getIlceler()
    {
        $ilceler = Ilce::orderBy('ilce_adi')->get(['id', 'il_id', 'ilce_adi']);

        return response()->json(['success' => true, 'ilceler' => $ilceler]);
    }

    public function getIlcelerByIl($ilId)
    {
        $ilceler = $this->adresCache->ilcelerByIl((int) $ilId);

        return response()->json(['success' => true, 'ilceler' => $ilceler]);
    }

    public function getMahalleler()
    {
        $mahalleler = Mahalle::orderBy('mahalle_adi')->get(['id', 'ilce_id', 'mahalle_adi']);

        return response()->json(['success' => true, 'mahalleler' => $mahalleler]);
    }

    public function getMahallelerByIlce($ilceId)
    {
        $mahalleler = $this->adresCache->mahallelerByIlce((int) $ilceId);

        return response()->json(['success' => true, 'mahalleler' => $mahalleler]);
    }

    public function store(Request $request, $type)
    {
        $name = $request->input('name');
        $parentId = $request->input('parent_id');

        // ✅ SAB: All mutations delegated to AdresLocationService
        if ($type === 'ulke') {
            $item = $this->adresLocationService->createUlke($name);
            $this->adresCache->invalidateUlkeler();

            return response()->json(['success' => true, 'item' => $item]);
        }
        if ($type === 'il') {
            $item = $this->adresLocationService->createIl($name);
            $this->adresCache->invalidateIller();

            return response()->json(['success' => true, 'item' => $item]);
        }
        if ($type === 'ilce') {
            $item = $this->adresLocationService->createIlce((int) $parentId, $name);
            $this->adresCache->invalidateIlceGroup([(int) $parentId]);

            return response()->json(['success' => true, 'item' => $item]);
        }
        if ($type === 'mahalle') {
            $item = $this->adresLocationService->createMahalle((int) $parentId, $name);
            $this->adresCache->invalidateMahallelerByIlce((int) $parentId);

            return response()->json(['success' => true, 'item' => $item]);
        }

        return response()->json(['success' => false, 'message' => 'Geçersiz tür'], 422);
    }

    public function update(Request $request, $type, $id)
    {
        $name = $request->input('name');

        // ✅ SAB: All mutations delegated to AdresLocationService
        if ($type === 'ulke') {
            $this->adresLocationService->updateUlke((int) $id, $name);
            $this->adresCache->invalidateUlkeler();

            return response()->json(['success' => true]);
        }
        if ($type === 'il') {
            $this->adresLocationService->updateIl((int) $id, $name);
            $this->adresCache->invalidateIller();

            return response()->json(['success' => true]);
        }
        if ($type === 'ilce') {
            $result = $this->adresLocationService->updateIlce((int) $id, $name);
            $this->adresCache->invalidateIlceGroup(
                array_unique([(int) $result['old_il_id'], (int) $result['ilce']->il_id])
            );

            return response()->json(['success' => true]);
        }
        if ($type === 'mahalle') {
            $result = $this->adresLocationService->updateMahalle((int) $id, $name);
            $this->adresCache->invalidateMahalleGroup(
                array_unique([(int) $result['old_ilce_id'], (int) $result['mahalle']->ilce_id])
            );

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Geçersiz tür'], 422);
    }

    public function destroy($type, $id)
    {
        if ($type === 'ulke') {
            $this->adresLocationService->deleteUlke($id);
            $this->adresCache->invalidateUlkeler();

            return response()->json(['success' => true]);
        }
        if ($type === 'il') {
            $this->adresLocationService->deleteIl($id);
            $this->adresCache->invalidateIller();

            return response()->json(['success' => true]);
        }
        if ($type === 'ilce') {
            $ilce = Ilce::find($id);
            $ilId = $ilce?->il_id;
            $this->adresLocationService->deleteIlce($id);
            $this->adresCache->invalidateTumIlceler();
            if ($ilId) {
                $this->adresCache->invalidateIlcelerByIl((int) $ilId);
            }

            return response()->json(['success' => true]);
        }
        if ($type === 'mahalle') {
            $mahalle = Mahalle::find($id);
            $ilceId = $mahalle?->ilce_id;
            $this->adresLocationService->deleteMahalle($id);
            if ($ilceId) {
                $this->adresCache->invalidateMahallelerByIlce((int) $ilceId);
            }

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Geçersiz tür'], 422);
    }

    /**
     * Bulk delete address items
     * Context7: Toplu silme işlemi
     */
    public function bulkDelete(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:ulke,il,ilce,mahalle', // context7-ignore
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|integer',
            ], [
                'type.required' => 'Tip belirtilmelidir', // context7-ignore
                'type.in' => 'Geçersiz tip. İzin verilen tipler: ulke, il, ilce, mahalle', // context7-ignore
                'ids.required' => 'Silinecek öğe ID\'leri belirtilmelidir',
                'ids.array' => 'ID\'ler bir dizi olmalıdır',
                'ids.min' => 'En az bir öğe seçilmelidir',
                'ids.*.required' => 'Her ID değeri gereklidir',
                'ids.*.integer' => 'Her ID bir tam sayı olmalıdır',
            ]);

            $type = $validated['type']; // context7-ignore
            $ids = $validated['ids'];
            $errors = [];

            $deletedCount = $this->bulkService->bulkDelete($type, $ids);

            if ($deletedCount > 0) {
                $msg = "{$deletedCount} öğe başarıyla silindi";
                $msg .= (count($errors) > 0 ? '. Bazı öğeler silinemedi.' : '');

                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'deleted_count' => $deletedCount,
                    'errors' => $errors,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Hiçbir öğe silinemedi. Seçilen ID\'ler veritabanında bulunamadı.',
                    'errors' => $errors,
                ], 422);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation hatası',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Bulk delete hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Toplu silme işlemi sırasında hata: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * TurkiyeAPI'den tüm lokasyon verilerini sync et
     * Context7: Hybrid Approach - TurkiyeAPI sync + Local DB CRUD
     */
    public function syncFromTurkiyeAPI(Request $request)
    {
        try {
            $type = $request->input('type', 'all'); // all, provinces, districts, neighborhoods // context7-ignore
            $provinceId = $request->input('province_id');
            $districtId = $request->input('district_id');

            // 🔧 REFACTORED: 440-line method extracted to TurkiyeAPILocationSyncService
            $syncResults = $this->syncService->sync($type, $provinceId, $districtId);

            return response()->json([
                'success' => true,
                'message' => 'TurkiyeAPI\'den veri sync edildi',
                'results' => $syncResults,
                'source' => 'turkiyeapi',
            ]);
        } catch (\Exception $e) {
            Log::error('TurkiyeAPI sync hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * TurkiyeAPI'den belirli bir ilin ilçelerini getir
     * Context7: Harita sistemi için lokasyon verileri
     */
    public function getIlcelerByIlFromTurkiyeAPI($ilId)
    {
        try {
            $ilceler = $this->turkiyeAPI->getDistricts($ilId);

            return response()->json([
                'success' => true,
                'ilceler' => $ilceler,
                'source' => 'turkiyeapi',
                'count' => count($ilceler),
            ]);
        } catch (\Exception $e) {
            Log::error('TurkiyeAPI ilçe getirme hatası', [
                'il_id' => $ilId,
                'error' => $e->getMessage(),
            ]);

            // Fallback: Local DB'den çek
            $ilceler = $this->adresLocationService->getIlcelerByIlId($ilId);

            return response()->json([
                'success' => true,
                'ilceler' => $ilceler,
                'source' => 'local_db',
                'count' => count($ilceler),
                'warning' => 'TurkiyeAPI kullanılamadı, local DB kullanıldı',
            ]);
        }
    }

    /**
     * TurkiyeAPI'den belirli bir ilçenin tüm lokasyon tiplerini getir
     * Context7: Mahalle + Belde + Köy birlikte
     */
    public function getAllLocationTypesFromTurkiyeAPI($ilceId)
    {
        try {
            $allLocations = $this->turkiyeAPI->getAllLocations($ilceId);

            return response()->json([
                'success' => true,
                'data' => $allLocations,
                'source' => 'turkiyeapi',
                'counts' => [
                    'neighborhoods' => count($allLocations['neighborhoods'] ?? []),
                    'towns' => count($allLocations['towns'] ?? []),
                    'villages' => count($allLocations['villages'] ?? []),
                    'total' => count($allLocations['neighborhoods'] ?? []) +
                        count($allLocations['towns'] ?? []) +
                        count($allLocations['villages'] ?? []),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('TurkiyeAPI lokasyon tipleri getirme hatası', [
                'ilce_id' => $ilceId,
                'error' => $e->getMessage(),
            ]);

            // Fallback: Local DB'den sadece mahalleleri çek
            $mahalleler = $this->adresLocationService->getMahallelerByIlceId($ilceId);

            return response()->json([
                'success' => true,
                'data' => [
                    'neighborhoods' => $mahalleler->map(function ($m) {
                        return [
                            'id' => $m->id,
                            'name' => $m->mahalle_adi,
                            'type' => 'mahalle', // context7-ignore
                            'type_label' => 'Mahalle', // context7-ignore
                            'icon' => '📍',
                        ];
                    })->toArray(),
                    'towns' => [],
                    'villages' => [],
                ],
                'source' => 'local_db',
                'warning' => 'TurkiyeAPI kullanılamadı, sadece mahalleler gösteriliyor',
            ]);
        }
    }

    /**
     * TurkiyeAPI'den seçili il/ilçe/mahalleleri çek (sync etmeden sadece göster)
     * Context7: Seçimli veri çekme - Kullanıcı istediği lokasyonları seçerek çekebilir
     */
    public function fetchFromTurkiyeAPI(Request $request)
    {
        try {
            $provinceId = $request->input('province_id');
            $districtId = $request->input('district_id');
            $fetchType = $request->input('type', 'auto'); // auto, districts, neighborhoods // context7-ignore

            $results = [
                'provinces' => [],
                'districts' => [],
                'neighborhoods' => [],
                'towns' => [],
                'villages' => [],
            ];

            // 1. İl seçildiyse ilçeleri çek
            if ($provinceId && ($fetchType === 'auto' || $fetchType === 'districts')) {
                $ilceler = $this->turkiyeAPI->getDistricts($provinceId);
                $results['districts'] = $ilceler;
                Log::info("TurkiyeAPI: İl ID {$provinceId} için " . count($ilceler) . ' ilçe çekildi');

                // Context7: İlçeler içinde mahalleler varsa onları da çıkar
                // TurkiyeAPI bazen ilçeleri mahalleleriyle birlikte döndürüyor
                foreach ($ilceler as $ilce) {
                    if (isset($ilce['neighborhoods']) && is_array($ilce['neighborhoods'])) {
                        foreach ($ilce['neighborhoods'] as $mahalle) {
                            $results['neighborhoods'][] = [
                                'id' => $mahalle['id'] ?? null,
                                'name' => $mahalle['name'] ?? '',
                                'districtId' => $ilce['id'] ?? null,
                                'population' => $mahalle['population'] ?? null,
                            ];
                        }
                    }
                }

                // Context7: İl seçildiyse ve ilçe seçilmemişse, tüm ilçelerin mahallelerini de çek (opsiyonel)
                // Bu çok fazla veri olabilir, bu yüzden sadece ilk 5 ilçe için yapıyoruz
                if (! $districtId && $fetchType === 'auto' && empty($results['neighborhoods'])) {
                    $firstDistricts = array_slice($ilceler, 0, 5); // İlk 5 ilçe
                    foreach ($firstDistricts as $ilce) {
                        try {
                            $allLocations = $this->turkiyeAPI->getAllLocations($ilce['id']);
                            $results['neighborhoods'] = array_merge(
                                $results['neighborhoods'] ?? [],
                                $allLocations['neighborhoods'] ?? []
                            );
                            $results['towns'] = array_merge(
                                $results['towns'] ?? [],
                                $allLocations['towns'] ?? []
                            );
                            $results['villages'] = array_merge(
                                $results['villages'] ?? [],
                                $allLocations['villages'] ?? []
                            );
                        } catch (\Exception $e) {
                            Log::warning(
                                "TurkiyeAPI: İlçe ID {$ilce['id']} için mahalle çekilemedi",
                                ['error' => $e->getMessage()]
                            );
                        }
                    }
                    Log::info("TurkiyeAPI: İl ID {$provinceId} için ilk 5 ilçenin mahalleleri çekildi");
                }
            }

            // 2. İlçe seçildiyse mahalleleri çek
            if ($districtId && ($fetchType === 'auto' || $fetchType === 'neighborhoods')) {
                $allLocations = $this->turkiyeAPI->getAllLocations($districtId);
                $results['neighborhoods'] = array_merge(
                    $results['neighborhoods'] ?? [],
                    $allLocations['neighborhoods'] ?? []
                );
                $results['towns'] = array_merge(
                    $results['towns'] ?? [],
                    $allLocations['towns'] ?? []
                );
                $results['villages'] = array_merge(
                    $results['villages'] ?? [],
                    $allLocations['villages'] ?? []
                );
                Log::info("TurkiyeAPI: İlçe ID {$districtId} için " .
                    (count($allLocations['neighborhoods'] ?? []) +
                    count($allLocations['towns'] ?? []) +
                    count($allLocations['villages'] ?? [])) .
                    ' lokasyon çekildi');
            }

            // Debug: Log results
            Log::info('TurkiyeAPI fetch results', [
                'province_id' => $provinceId,
                'district_id' => $districtId,
                'fetch_type' => $fetchType,
                'districts_count' => count($results['districts']),
                'neighborhoods_count' => count($results['neighborhoods']),
                'towns_count' => count($results['towns']),
                'villages_count' => count($results['villages']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'TurkiyeAPI\'den veriler başarıyla çekildi',
                'data' => $results,
                'counts' => [
                    'districts' => count($results['districts']),
                    'neighborhoods' => count($results['neighborhoods']),
                    'towns' => count($results['towns']),
                    'villages' => count($results['villages']),
                    'total' => count($results['districts']) +
                        count($results['neighborhoods']) +
                        count($results['towns']) +
                        count($results['villages']),
                ],
                'source' => 'turkiyeapi',
                'debug' => [
                    'province_id' => $provinceId,
                    'district_id' => $districtId,
                    'fetch_type' => $fetchType,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('TurkiyeAPI fetch hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Veri çekme hatası: ' . $e->getMessage(),
            ], 500);
        }
    }
}
