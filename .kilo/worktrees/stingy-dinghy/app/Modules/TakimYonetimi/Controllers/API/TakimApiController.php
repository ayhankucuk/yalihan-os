<?php

namespace App\Modules\TakimYonetimi\Controllers\API;

use App\Http\Controllers\Controller;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Models\Proje;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TakimApiController extends Controller
{
    /**
     * Takım listesi
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::whereHas('roles', function ($q) {
            $q->where('name', 'danisman');
        })->with(['roles']);

        // Filtreleme
        if ($request->has('active') && $request->active) {
            $query->where('yayin_durumu', true);
        }

        if ($request->has('arama')) {
            $arama = $request->arama;
            $query->where(function ($q) use ($arama) {
                $q->where('name', 'like', "%{$arama}%")
                    ->orWhere('email', 'like', "%{$arama}%");
            });
        }

        // Sıralama
        $sortBy = $request->get('sort_by', 'name');
        $siralamaYonu = $request->get('siralama_yonu', 'asc');
        $query->orderBy($sortBy, $siralamaYonu);

        $takimlar = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $takimlar,
            'message' => 'Takım listesi getirildi',
        ]);
    }

    /**
     * Takım üyesi detayı
     */
    public function show(User $takim): JsonResponse
    {
        $takim->load(['roles']);

        // Takım üyesi istatistikleri
        $istatistikler = [
            'toplam_gorev' => Gorev::where('user_id', $takim->id)->count(),
            'tamamlanan_gorev' => Gorev::where('user_id', $takim->id)
                ->where('yayin_durumu', 'tamamlandi')->count(),
            'devam_eden_gorev' => Gorev::where('user_id', $takim->id)
                ->where('yayin_durumu', 'devam_ediyor')->count(),
            'gecikmis_gorev' => Gorev::where('user_id', $takim->id)
                ->gecikmis()->count(),
            'aktif_projeler' => Proje::where('user_id', $takim->id)
                ->where('yayin_durumu', true)->count(),
        ];

        $takim->istatistikler = $istatistikler;

        return response()->json([
            'success' => true,
            'data' => $takim,
            'message' => 'Takım üyesi detayı getirildi',
        ]);
    }

    /**
     * Yeni takım üyesi oluştur
     */
    public function store(Request $request, \App\Modules\TakimYonetimi\Services\TakimService $takimService): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone_number' => 'nullable|string|max:20',
            'title' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'expertise_summary' => 'nullable|string',
            'aktiflik_durumu' => 'boolean',
        ]);

        $user = $takimService->store($validated);
        $user->load(['roles']);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Takım üyesi başarıyla oluşturuldu',
        ], 201);
    }

    /**
     * Takım üyesi güncelle
     */
    public function update(Request $request, User $takim): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,'.$takim->id,
            'phone_number' => 'nullable|string|max:20',
            'title' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'expertise_summary' => 'nullable|string',
            'yayin_durumu' => 'boolean',
        ]);

        $takim->update($validated);
        $takim->load(['roles']);

        return response()->json([
            'success' => true,
            'data' => $takim,
            'message' => 'Takım üyesi başarıyla güncellendi',
        ]);
    }

    /**
     * Takım üyesi sil
     */
    public function destroy(User $takim): JsonResponse
    {
        // Aktif görevleri kontrol et
        $aktifGorevler = Gorev::where('user_id', $takim->id)
            ->whereIn('yayin_durumu', ['beklemede', 'devam_ediyor'])
            ->count();

        if ($aktifGorevler > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Bu kullanıcının aktif görevleri var. Önce görevleri tamamlayın veya başka kullanıcıya atayın.',
            ], 400);
        }

        $takim->delete();

        return response()->json([
            'success' => true,
            'message' => 'Takım üyesi başarıyla silindi',
        ]);
    }

    /**
     * Takım üyesi ekle
     */
    public function addMember(Request $request, User $takim): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'rol' => 'nullable|string|in:danisman,asistan,admin',
        ]);

        $user = User::find($validated['user_id']);

        if ($user->hasRole('danisman')) {
            return response()->json([
                'success' => false,
                'message' => 'Bu kullanıcı zaten takım üyesi',
            ], 400);
        }

        $user->assignRole($validated['rol'] ?? 'danisman');

        return response()->json([
            'success' => true,
            'data' => $user->load(['roles']),
            'message' => 'Kullanıcı takıma başarıyla eklendi',
        ]);
    }

    /**
     * Takım üyesi çıkar
     */
    public function removeMember(User $takim, $uyeId): JsonResponse
    {
        $user = User::find($uyeId);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı bulunamadı',
            ], 404);
        }

        // Aktif görevleri kontrol et
        $aktifGorevler = Gorev::where('user_id', $user->id)
            ->whereIn('yayin_durumu', ['beklemede', 'devam_ediyor'])
            ->count();

        if ($aktifGorevler > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Bu kullanıcının aktif görevleri var. Önce görevleri tamamlayın veya başka kullanıcıya atayın.',
            ], 400);
        }

        $user->removeRole('danisman');

        return response()->json([
            'success' => true,
            'message' => 'Kullanıcı takımdan başarıyla çıkarıldı',
        ]);
    }

    /**
     * Takım performansı
     */
    public function performans(User $takim): JsonResponse
    {
        $baslangic = request()->get('baslangic', now()->startOfMonth());
        $bitis = request()->get('bitis', now()->endOfMonth());

        $performans = [
            'kullanici' => $takim->load(['roles']),
            'donem' => [
                'baslangic' => $baslangic,
                'bitis' => $bitis,
            ],
            'gorev_istatistikleri' => [
                'toplam_gorev' => Gorev::where('user_id', $takim->id)
                    ->whereBetween('created_at', [$baslangic, $bitis])
                    ->count(),
                'tamamlanan_gorev' => Gorev::where('user_id', $takim->id)
                    ->where('yayin_durumu', 'tamamlandi')
                    ->whereBetween('created_at', [$baslangic, $bitis])
                    ->count(),
                'devam_eden_gorev' => Gorev::where('user_id', $takim->id)
                    ->where('yayin_durumu', 'devam_ediyor')
                    ->whereBetween('created_at', [$baslangic, $bitis])
                    ->count(),
                'gecikmis_gorev' => Gorev::where('user_id', $takim->id)
                    ->gecikmis()
                    ->whereBetween('created_at', [$baslangic, $bitis])
                    ->count(),
            ],
            'zaman_analizi' => [
                'ortalama_tamamlanma_suresi' => Gorev::where('user_id', $takim->id)
                    ->where('yayin_durumu', 'tamamlandi')
                    ->whereNotNull('gerceklesen_sure')
                    ->whereBetween('created_at', [$baslangic, $bitis])
                    ->avg('gerceklesen_sure'),
                'toplam_calisma_suresi' => Gorev::where('user_id', $takim->id)
                    ->where('yayin_durumu', 'tamamlandi')
                    ->whereNotNull('gerceklesen_sure')
                    ->whereBetween('created_at', [$baslangic, $bitis])
                    ->sum('gerceklesen_sure'),
            ],
            'proje_istatistikleri' => [
                'aktif_projeler' => Proje::where('user_id', $takim->id)
                    ->where('yayin_durumu', true)
                    ->count(),
                'tamamlanan_projeler' => Proje::where('user_id', $takim->id)
                    ->where('yayin_durumu', 'tamamlandi')
                    ->whereBetween('created_at', [$baslangic, $bitis])
                    ->count(),
            ],
        ];

        // Performans skoru hesapla
        $performans['performans_skoru'] = $this->calculatePerformansSkoru($performans);

        return response()->json([
            'success' => true,
            'data' => $performans,
            'message' => 'Takım performansı getirildi',
        ]);
    }

    /**
     * Takım istatistikleri
     */
    public function istatistikler(User $takim): JsonResponse
    {
        $istatistikler = [
            'genel' => [
                'toplam_gorev' => Gorev::where('user_id', $takim->id)->count(),
                'tamamlanan_gorev' => Gorev::where('user_id', $takim->id)
                    ->where('yayin_durumu', 'tamamlandi')->count(),
                'aktif_gorev' => Gorev::where('user_id', $takim->id)
                    ->whereIn('yayin_durumu', ['beklemede', 'devam_ediyor'])->count(),
                'gecikmis_gorev' => Gorev::where('user_id', $takim->id)
                    ->gecikmis()->count(),
            ],
            'zaman_dagilimi' => [
                'bu_hafta' => Gorev::where('user_id', $takim->id)
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
                'bu_ay' => Gorev::where('user_id', $takim->id)
                    ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count(),
                'bu_yil' => Gorev::where('user_id', $takim->id)
                    ->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()])
                    ->count(),
            ],
            'oncelik_dagilimi' => Gorev::where('user_id', $takim->id)
                ->selectRaw('oncelik, COUNT(*) as sayi')
                ->groupBy('oncelik')
                ->get(),
            'durum_dagilimi' => Gorev::where('user_id', $takim->id)
                ->selectRaw('yayin_durumu, COUNT(*) as sayi')
                ->groupBy('yayin_durumu')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $istatistikler,
            'message' => 'Takım istatistikleri getirildi',
        ]);
    }

    /**
     * Performans skoru hesapla
     */
    private function calculatePerformansSkoru(array $performans): int
    {
        $gorevler = $performans['gorev_istatistikleri'];
        $toplamGorev = $gorevler['toplam_gorev'];

        if ($toplamGorev == 0) {
            return 0;
        }

        $tamamlanmaOrani = ($gorevler['tamamlanan_gorev'] / $toplamGorev) * 100;
        $gecikmeOrani = ($gorevler['gecikmis_gorev'] / $toplamGorev) * 100;

        // Temel skor: tamamlanma oranı
        $skor = $tamamlanmaOrani;

        // Gecikme cezası
        $skor -= $gecikmeOrani * 0.5;

        // Minimum 0, maksimum 100
        return max(0, min(100, round($skor)));
    }
}
