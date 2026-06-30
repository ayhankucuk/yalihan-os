<?php

namespace App\Modules\TakimYonetimi\Controllers\API;

use App\Http\Controllers\Controller;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Models\User;
use App\Modules\TakimYonetimi\Services\GorevService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GorevApiController extends Controller
{
    public function __construct(
        private readonly GorevService $gorevService,
    ) {}

    /**
     * Görev listesi
     */
    public function index(Request $request): JsonResponse
    {
        $query = Gorev::with(['admin', 'danisman', 'musteri', 'proje']);

        // Filtreleme
        if ($request->has('gorev_durumu')) {
            $query->where('gorev_durumu', $request->gorev_durumu);
        }

        if ($request->has('oncelik')) {
            $query->where('oncelik', $request->oncelik);
        }

        if ($request->has('admin_id')) {
            $query->where('admin_id', $request->admin_id);
        }

        if ($request->has('proje_id')) {
            $query->where('proje_id', $request->proje_id);
        }

        if ($request->has('gecikmis') && $request->gecikmis) {
            $query->gecikmis();
        }

        if ($request->has('yaklasan') && $request->yaklasan) {
            $query->yaklasan($request->get('gun', 3));
        }

        // Sıralama
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $gorevler = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $gorevler,
            'message' => 'Görevler başarıyla getirildi',
        ]);
    }

    /**
     * Görev detayı
     */
    public function show(Gorev $gorev): JsonResponse
    {
        $gorev->load(['admin', 'danisman', 'musteri', 'proje', 'takip', 'dosyalar']);

        return response()->json([
            'success' => true,
            'data' => $gorev,
            'message' => 'Görev detayı getirildi',
        ]);
    }

    /**
     * Yeni görev oluştur
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'gorev_adi' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'baslangic_tarihi' => 'nullable|date',
            'bitis_tarihi' => 'nullable|date|after:baslangic_tarihi',
            'gorev_durumu' => 'required|in:beklemede,devam_ediyor,tamamlandi,iptal,askida',
            'oncelik' => 'required|in:dusuk,orta,yuksek,kritik',
            'user_id' => 'required|exists:users,id',
            'atayan_id' => 'required|exists:users,id',
            'proje_id' => 'nullable|exists:projeler,id',
            'tahmini_sure' => 'nullable|integer|min:0',
            'notlar' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $gorev = Gorev::create($validated);
        $gorev->load(['user', 'atayan', 'proje']);

        return response()->json([
            'success' => true,
            'data' => $gorev,
            'message' => 'Görev başarıyla oluşturuldu',
        ], 201);
    }

    /**
     * Görev güncelle
     */
    public function update(Request $request, Gorev $gorev): JsonResponse
    {
        $validated = $request->validate([
            'gorev_adi' => 'sometimes|required|string|max:255',
            'aciklama' => 'nullable|string',
            'baslangic_tarihi' => 'nullable|date',
            'bitis_tarihi' => 'nullable|date|after:baslangic_tarihi',
            'gorev_durumu' => 'sometimes|required|in:beklemede,devam_ediyor,tamamlandi,iptal,askida',
            'oncelik' => 'sometimes|required|in:dusuk,orta,yuksek,kritik',
            'user_id' => 'sometimes|required|exists:users,id',
            'atayan_id' => 'sometimes|required|exists:users,id',
            'proje_id' => 'nullable|exists:projeler,id',
            'tahmini_sure' => 'nullable|integer|min:0',
            'gerceklesen_sure' => 'nullable|integer|min:0',
            'notlar' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $this->gorevService->update($gorev, $validated);
        $gorev->load(['user', 'atayan', 'proje']);

        return response()->json([
            'success' => true,
            'data' => $gorev,
            'message' => 'Görev başarıyla güncellendi',
        ]);
    }

    /**
     * Görev sil
     */
    public function destroy(Gorev $gorev): JsonResponse
    {
        $this->gorevService->destroy($gorev);

        return response()->json([
            'success' => true,
            'message' => 'Görev başarıyla silindi',
        ]);
    }

    /**
     * Görev atama
     */
    public function atama(Request $request, Gorev $gorev): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $this->gorevService->atamaYapApi($gorev, $validated['user_id']);
        $gorev->load(['user', 'atayan']);

        // Takip kaydı ekle
        $gorev->addTakip("Görev {$gorev->danisman->name} kullanıcısına atandı");

        return response()->json([
            'success' => true,
            'data' => $gorev,
            'message' => 'Görev başarıyla atandı',
        ]);
    }

    /**
     * Görev durumu güncelle
     */
    public function updateStatus(Request $request, Gorev $gorev): JsonResponse
    {
        $validated = $request->validate([
            'gorev_durumu' => 'required|in:beklemede,devam_ediyor,tamamlandi,iptal,askida',
            'aciklama' => 'nullable|string',
        ]);

        $eskiDurum = $gorev->gorev_durumu;
        $this->gorevService->durumGuncelle($gorev, $validated['gorev_durumu']);

        // Takip kaydı ekle
        $aciklama = $validated['aciklama'] ?? "Durum '{$eskiDurum}' den '{$validated['gorev_durumu']}' e değiştirildi";
        $gorev->addTakip($aciklama);

        // Durum değişikliğine göre özel işlemler
        if ($validated['gorev_durumu'] === 'devam_ediyor' && ! $gorev->baslangic_tarihi) {
            $gorev->baslat();
        }

        if ($validated['gorev_durumu'] === 'tamamlandi') {
            $gorev->tamamla();
        }

        $gorev->load(['user', 'atayan']);

        return response()->json([
            'success' => true,
            'data' => $gorev,
            'message' => 'Görev durumu başarıyla güncellendi',
        ]);
    }

    /**
     * Görev raporu
     */
    public function rapor(Gorev $gorev): JsonResponse
    {
        $gorev->load(['user', 'atayan', 'proje', 'takip.user', 'dosyalar']);

        $rapor = [
            'gorev' => $gorev,
            'istatistikler' => [
                'toplam_takip' => $gorev->takip->count(),
                'tamamlanma_suresi' => $gorev->gerceklesen_sure,
                'tahmini_sure' => $gorev->tahmini_sure,
                'verimlilik' => $gorev->tahmini_sure > 0 ?
                    round(($gorev->gerceklesen_sure / $gorev->tahmini_sure) * 100, 2) : 0,
                'gecikme_durumu' => $gorev->isGecikmis(),
                'kalan_gun' => $gorev->kalan_gun,
            ],
            'son_aktiviteler' => $gorev->takip()->latest()->limit(10)->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $rapor,
            'message' => 'Görev raporu oluşturuldu',
        ]);
    }

    /**
     * Dosya ekle
     */
    public function addFile(Request $request, Gorev $gorev): JsonResponse
    {
        $validated = $request->validate([
            'dosya' => 'required|file|max:10240', // 10MB max
            'aciklama' => 'nullable|string|max:255',
        ]);

        // Dosya yükleme işlemi burada yapılacak
        // Şimdilik placeholder
        $dosya = [
            'id' => rand(1000, 9999),
            'dosya_adi' => $validated['dosya']->getClientOriginalName(),
            'boyut' => $validated['dosya']->getSize(),
            'aciklama' => $validated['aciklama'],
            'yuklenme_tarihi' => now(),
        ];

        return response()->json([
            'success' => true,
            'data' => $dosya,
            'message' => 'Dosya başarıyla eklendi',
        ]);
    }

    /**
     * Dosya sil
     */
    public function deleteFile(Gorev $gorev, $dosyaId): JsonResponse
    {
        // Dosya silme işlemi burada yapılacak
        // Şimdilik placeholder

        return response()->json([
            'success' => true,
            'message' => 'Dosya başarıyla silindi',
        ]);
    }

    /**
     * Görev geçmişi
     */
    public function gecmis(Gorev $gorev): JsonResponse
    {
        $gecmis = $gorev->takip()
            ->with('user')
            ->orderBy('tarih', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $gecmis,
            'message' => 'Görev geçmişi getirildi',
        ]);
    }

    /**
     * Dashboard verileri
     */
    public function dashboard(): JsonResponse
    {
        $istatistikler = [
            'toplam_gorev' => Gorev::count(),
            'tamamlanan_gorev' => Gorev::where('gorev_durumu', 'tamamlandi')->count(),
            'devam_eden_gorev' => Gorev::where('gorev_durumu', 'devam_ediyor')->count(),
            'gecikmis_gorev' => Gorev::gecikmis()->count(),
            'yaklasan_gorev' => Gorev::yaklasan()->count(),
            'beklemede_gorev' => Gorev::where('gorev_durumu', 'beklemede')->count(),
        ];

        $son_gorevler = Gorev::with(['user', 'atayan', 'proje'])
            ->latest()
            ->limit(10)
            ->get();

        $yaklasan_gorevler = Gorev::with(['user', 'atayan', 'proje'])
            ->yaklasan()
            ->orderBy('bitis_tarihi')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'istatistikler' => $istatistikler,
                'son_gorevler' => $son_gorevler,
                'yaklasan_gorevler' => $yaklasan_gorevler,
            ],
            'message' => 'Dashboard verileri getirildi',
        ]);
    }

    /**
     * Raporlar
     */
    public function raporlar(Request $request): JsonResponse
    {
        $baslangic = $request->get('baslangic', now()->startOfMonth());
        $bitis = $request->get('bitis', now()->endOfMonth());

        $raporlar = [
            'tarih_araligi' => [
                'baslangic' => $baslangic,
                'bitis' => $bitis,
            ],
            'durum_dagilimi' => Gorev::selectRaw('gorev_durumu, COUNT(*) as sayi')
                ->whereBetween('created_at', [$baslangic, $bitis])
                ->groupBy('gorev_durumu')
                ->get(),
            'oncelik_dagilimi' => Gorev::selectRaw('oncelik, COUNT(*) as sayi')
                ->whereBetween('created_at', [$baslangic, $bitis])
                ->groupBy('oncelik')
                ->get(),
            'kullanici_performansi' => Gorev::selectRaw('user_id, COUNT(*) as toplam_gorev,
                SUM(CASE WHEN gorev_durumu = "tamamlandi" THEN 1 ELSE 0 END) as tamamlanan_gorev')
                ->whereBetween('created_at', [$baslangic, $bitis])
                ->with('user:id,name')
                ->groupBy('user_id')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $raporlar,
            'message' => 'Raporlar oluşturuldu',
        ]);
    }

    /**
     * İstatistikler
     */
    public function istatistikler(): JsonResponse
    {
        $istatistikler = [
            'genel' => [
                'toplam_gorev' => Gorev::count(),
                'aktif_gorev' => Gorev::whereIn('gorev_durumu', ['beklemede', 'devam_ediyor'])->count(),
                'tamamlanan_gorev' => Gorev::where('gorev_durumu', 'tamamlandi')->count(),
                'iptal_gorev' => Gorev::where('gorev_durumu', 'iptal')->count(),
            ],
            'performans' => [
                'ortalama_tamamlanma_suresi' => Gorev::where('gorev_durumu', 'tamamlandi')
                    ->whereNotNull('gerceklesen_sure')
                    ->avg('gerceklesen_sure'),
                'ortalama_tahmini_sure' => Gorev::whereNotNull('tahmini_sure')
                    ->avg('tahmini_sure'),
                'verimlilik_orani' => $this->calculateVerimlilik(),
            ],
            'zaman_dagilimi' => [
                'bu_hafta' => Gorev::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'bu_ay' => Gorev::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'bu_yil' => Gorev::whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()])->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $istatistikler,
            'message' => 'İstatistikler getirildi',
        ]);
    }

    /**
     * AI Görev Önerisi
     */
    public function aiGorevOnerisi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'gorev_adi' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'proje_id' => 'nullable|exists:projeler,id',
        ]);

        // AI önerisi burada yapılacak
        // Şimdilik basit öneri
        $oneri = [
            'oncelik' => 'orta',
            'tahmini_sure' => 480, // 8 saat
            'oneri_kullanici' => User::whereHas('roles', function ($q) {
                $q->where('name', 'danisman');
            })->inRandomOrder()->first()->id,
            'aciklama' => 'AI tarafından önerilen görev parametreleri',
        ];

        return response()->json([
            'success' => true,
            'data' => $oneri,
            'message' => 'AI görev önerisi oluşturuldu',
        ]);
    }

    /**
     * AI Performans Analizi
     */
    public function aiPerformansAnalizi(Request $request): JsonResponse
    {
        $userId = $request->get('user_id', auth()->id());
        $period = $request->get('period', 'month');

        // AI analizi burada yapılacak
        $analiz = [
            'kullanici_id' => $userId,
            'donem' => $period,
            'performans_skoru' => rand(70, 95),
            'oneri' => 'Görevleri daha hızlı tamamlamak için zaman yönetimi teknikleri kullanabilirsiniz.',
            'guclu_yanlar' => ['Hızlı öğrenme', 'Takım çalışması'],
            'gelistirilmesi_gereken_alanlar' => ['Zaman yönetimi', 'Detay odaklılık'],
        ];

        return response()->json([
            'success' => true,
            'data' => $analiz,
            'message' => 'AI performans analizi tamamlandı',
        ]);
    }

    /**
     * AI Otomatik Atama
     */
    public function aiOtomatikAtama(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'gorev_id' => 'required|exists:gorevler,id',
        ]);

        $gorev = Gorev::find($validated['gorev_id']);

        // AI atama algoritması burada yapılacak
        $oneri_kullanici = User::whereHas('roles', function ($q) {
            $q->where('name', 'danisman');
        })->inRandomOrder()->first();

        $gorev->update(['user_id' => $oneri_kullanici->id]);
        $gorev->addTakip("AI tarafından {$oneri_kullanici->name} kullanıcısına otomatik atandı");

        return response()->json([
            'success' => true,
            'data' => [
                'gorev' => $gorev->load(['user', 'atayan']),
                'atama_nedeni' => 'AI algoritması tarafından en uygun kullanıcı seçildi',
            ],
            'message' => 'Görev AI tarafından otomatik atandı',
        ]);
    }

    /**
     * Verimlilik hesapla
     */
    private function calculateVerimlilik(): float
    {
        $tamamlananGorevler = Gorev::where('gorev_durumu', 'tamamlandi')
            ->whereNotNull('tahmini_sure')
            ->whereNotNull('gerceklesen_sure')
            ->get();

        if ($tamamlananGorevler->isEmpty()) {
            return 0;
        }

        $toplamTahmini = $tamamlananGorevler->sum('tahmini_sure');
        $toplamGerceklesen = $tamamlananGorevler->sum('gerceklesen_sure');

        return $toplamTahmini > 0 ? round(($toplamTahmini / $toplamGerceklesen) * 100, 2) : 0;
    }
}
