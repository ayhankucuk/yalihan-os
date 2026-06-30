<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Services\AddressSyncService;
use App\Actions\Location\UpdateMahalleCoordinatesAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddressManagementController extends Controller
{
    protected AddressSyncService $syncService;

    public function __construct(AddressSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Adres Yönetim Paneli (Context7 Compliant)
     */
    public function index()
    {
        $stats = [
            'iller_count' => Il::count(),
            'ilceler_count' => Ilce::count(),
            'mahalleler_count' => Mahalle::count(),
            'mahalleler_with_coords' => Mahalle::whereNotNull('lat')->whereNotNull('lng')->count(),
        ];

        return view('admin.address-management.index', compact('stats'));
    }

    /**
     * İller listesi
     */
    public function getIller()
    {
        try {
            $iller = Il::orderBy('il_adi')->get();

            return response()->json([
                'success' => true,
                'data' => $iller->map(fn($il) => [
                    'id' => $il->id,
                    'name' => $il->il_adi,
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('İller yüklenirken hata: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'İller yüklenirken hata oluştu.'
            ], 500);
        }
    }

    public function getBolgeler()
    {
        return $this->getIller();
    }

    /**
     * İlçeler listesi (On-Demand Sync)
     */
    public function getIlceler(Request $request)
    {
        $request->validate(['il_id' => 'required|exists:iller,id']);

        try {
            $il = Il::find($request->il_id);
            $ilceler = Ilce::where('il_id', $request->il_id)->orderBy('ilce_adi')->get(); // context7-ignore

            // Eğer ilçe yoksa ve ilin api_id'si varsa senkronize et
            if ($ilceler->isEmpty() && $il->api_id) {
                $this->syncService->syncDistricts($il->api_id);
                // Tekrar çek
                $ilceler = Ilce::where('il_id', $request->il_id)->orderBy('ilce_adi')->get(); // context7-ignore
            }

            return response()->json([
                'success' => true,
                'data' => $ilceler->map(fn($ilce) => [
                    'id' => $ilce->id,
                    'name' => $ilce->ilce_adi,
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('İlçeler yüklenirken hata: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'İlçeler yüklenirken hata oluştu.'
            ], 500);
        }
    }

    /**
     * Mahalleler listesi (On-Demand Sync)
     */
    public function getMahalleler(Request $request)
    {
        $request->validate(['ilce_id' => 'required|exists:ilceler,id']);

        try {
            $ilce = Ilce::find($request->ilce_id);
            $mahalleler = Mahalle::where('ilce_id', $request->ilce_id)->orderBy('mahalle_adi')->get(); // context7-ignore

            // Eğer mahalle yoksa ve ilçenin api_id'si varsa senkronize et
            if ($mahalleler->isEmpty() && $ilce->api_id) {
                $this->syncService->syncNeighborhoods($ilce->api_id);
                // Tekrar çek
                $mahalleler = Mahalle::where('ilce_id', $request->ilce_id)->orderBy('mahalle_adi')->get(); // context7-ignore
            }

            return response()->json([
                'success' => true,
                'data' => $mahalleler->map(fn($mahalle) => [
                    'id' => $mahalle->id,
                    'name' => $mahalle->mahalle_adi,
                    'has_coords' => !is_null($mahalle->lat) && !is_null($mahalle->lng),
                    'lat' => $mahalle->lat,
                    'lng' => $mahalle->lng,
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('Mahalleler yüklenirken hata: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Mahalleler yüklenirken hata oluştu.'
            ], 500);
        }
    }



    /**
     * Koordinat güncelleme (Geocoding hazır yapı)
     */
    public function updateCoordinates(Request $request)
    {
        $request->validate([
            'mahalle_id' => 'required|exists:mahalleler,id',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        try {
            $mahalle = Mahalle::findOrFail($request->mahalle_id);
            app(UpdateMahalleCoordinatesAction::class)->handle($mahalle, $request->lat, $request->lng);

            return response()->json([
                'success' => true,
                'message' => 'Koordinatlar başarıyla güncellendi.'
            ]);
        } catch (\Exception $e) {
            Log::error('Koordinat güncelleme hatası: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Koordinatlar güncellenirken hata oluştu.'
            ], 500);
        }
    }

    /**
     * Toplu senkronizasyon (Türkiye API üzerinden illeri senkronize eder)
     */
    public function bulkSync()
    {
        try {
            // ✅ SAB: İlk aşama olarak illeri senkronize et
            $results = $this->syncService->syncProvinces();

            return response()->json([
                'success' => true,
                'message' => "{$results['created']} yeni il eklendi, {$results['updated']} il güncellendi.",
                'data' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk sync hatası: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Senkronizasyon sırasında bir hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }
}
