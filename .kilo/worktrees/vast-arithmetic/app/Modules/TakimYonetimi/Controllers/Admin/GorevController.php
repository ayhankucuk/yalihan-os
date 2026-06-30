<?php

namespace App\Modules\TakimYonetimi\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Modules\TakimYonetimi\Services\GorevService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GorevController extends Controller
{
    public function __construct(
        private readonly GorevService $gorevService,
    ) {}

    public function index()
    {
        $gorevler = Gorev::with(['admin', 'danisman', 'musteri', 'proje'])->paginate(15);

        // Context7: Spatie Permission ile danışman rolünü kontrol et
        $danismanlar = \App\Models\User::whereHas('roles', function ($q) {
            $q->where('name', 'danisman');
        })->get();

        // Context7: İstatistikler
        $istatistikler = [
            'bekleyen' => Gorev::where('gorev_durumu', 'beklemede')->count(),
            'devam_eden' => Gorev::where('gorev_durumu', 'devam_ediyor')->count(),
            'tamamlanan' => Gorev::where('gorev_durumu', 'tamamlandi')->count(),
        ];

        // ✅ SAB: View için gerekli değişkenler
        $gorevDurumu = request('gorev_durumu'); // Filter için

        return view('admin.takim-yonetimi.gorevler.index',
            compact('gorevler', 'danismanlar', 'istatistikler', 'gorevDurumu'));
    }

    public function create()
    {
        // Context7: Spatie Permission ile role-based user filtering
        $danismanlar = \App\Models\User::whereHas('roles', function ($q) {
            $q->where('name', 'danisman');
        })->get();
        $adminler = \App\Models\User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'super_admin']);
        })->get();

        // Context7: Müşteriler - Kisi modelinden çek (CRM için)
        $musteriler = \App\Models\Kisi::orderBy('ad')->limit(100)->get();

        // Projeleri getir
        $projeler = \App\Models\Proje::all();

        return view('admin.takim-yonetimi.gorevler.create',
            compact('danismanlar', 'adminler', 'musteriler', 'projeler'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'baslik' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'deadline' => 'nullable|date',
            'gorev_durumu' => 'required|in:beklemede,devam_ediyor,tamamlandi,iptal,askida',
            'oncelik' => 'required|in:dusuk,normal,yuksek,acil',
            'tip' => 'nullable|string',
            'admin_id' => 'required|exists:users,id',
            'danisman_id' => 'nullable|exists:users,id',
            'kisi_id' => 'nullable|exists:users,id',
            'proje_id' => 'nullable|exists:projeler,id',
            'tahmini_sure' => 'nullable|integer|min:0',
        ]);

        if (isset($validated['deadline'])) {
            $validated['bitis_tarihi'] = $validated['deadline'];
            unset($validated['deadline']);
        }
        if (array_key_exists('danisman_id', $validated)) {
            $validated['atanan_user_id'] = $validated['danisman_id'];
            unset($validated['danisman_id']);
        }
        if (array_key_exists('admin_id', $validated)) {
            $validated['olusturan_user_id'] = $validated['admin_id'];
            unset($validated['admin_id']);
        }
        if (array_key_exists('tip', $validated)) {
            $validated['gorev_tipi'] = $validated['tip'];
            unset($validated['tip']);
        }

        $gorev = Gorev::create($validated);

        return redirect()->route('admin.takim.gorevler.index')
            ->with('success', 'Görev başarıyla oluşturuldu.');
    }

    public function show(Gorev $gorev)
    {
        $gorev->load(['admin', 'danisman', 'musteri', 'proje', 'takip', 'dosyalar']);

        return view('admin.takim-yonetimi.gorevler.show', compact('gorev'));
    }

    public function edit(Gorev $gorev)
    {
        // Context7: Spatie Permission ile role-based user filtering
        $danismanlar = \App\Models\User::whereHas('roles', function ($q) {
            $q->where('name', 'danisman');
        })->get();
        $adminler = \App\Models\User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'super_admin']);
        })->get();

        // Context7: Müşteriler - Kisi modelinden çek (CRM için)
        $musteriler = \App\Models\Kisi::orderBy('ad')->limit(100)->get();

        // Projeleri getir
        $projeler = \App\Models\Proje::all();

        return view('admin.takim-yonetimi.gorevler.edit',
            compact('gorev', 'danismanlar', 'adminler', 'musteriler', 'projeler'));
    }

    public function update(Request $request, Gorev $gorev)
    {
        $validated = $request->validate([
            'baslik' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'deadline' => 'nullable|date',
            'gorev_durumu' => 'required|in:beklemede,devam_ediyor,tamamlandi,iptal,askida',
            'oncelik' => 'required|in:dusuk,normal,yuksek,acil',
            'tip' => 'nullable|string',
            'admin_id' => 'required|exists:users,id',
            'danisman_id' => 'nullable|exists:users,id',
            'kisi_id' => 'nullable|exists:users,id',
            'proje_id' => 'nullable|exists:projeler,id',
            'tahmini_sure' => 'nullable|integer|min:0',
        ]);

        if (isset($validated['deadline'])) {
            $validated['bitis_tarihi'] = $validated['deadline'];
            unset($validated['deadline']);
        }
        if (array_key_exists('danisman_id', $validated)) {
            $validated['atanan_user_id'] = $validated['danisman_id'];
            unset($validated['danisman_id']);
        }
        if (array_key_exists('admin_id', $validated)) {
            $validated['olusturan_user_id'] = $validated['admin_id'];
            unset($validated['admin_id']);
        }
        if (array_key_exists('tip', $validated)) {
            $validated['gorev_tipi'] = $validated['tip'];
            unset($validated['tip']);
        }

        $gorev->update($validated);

        return redirect()->route('admin.takim.gorevler.show', $gorev)
            ->with('success', 'Görev başarıyla güncellendi.');
    }

    public function destroy(Gorev $gorev)
    {
        $gorev->delete();

        return redirect()->route('admin.takim.gorevler.index')
            ->with('success', 'Görev başarıyla silindi.');
    }

    public function atama(Request $request, Gorev $gorev)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $this->gorevService->atamaYap($gorev, $validated['user_id']);

        // Context7: ResponseService kullan (response()->json() YASAK)
        return ResponseService::success([
            'gorev_id' => $gorev->id,
            'danisman_id' => $gorev->atanan_user_id,
        ], 'Görev başarıyla atandı');
    }

    public function updateStatus(Request $request, Gorev $gorev)
    {
        $validated = $request->validate([
            'gorev_durumu' => 'required|in:bekliyor,beklemede,devam_ediyor,tamamlandi,iptal,askida',
        ]);

        $this->gorevService->durumGuncelle($gorev, $validated['gorev_durumu']);

        // Context7: ResponseService kullan
        return ResponseService::success([
            'gorev_id' => $gorev->id,
            'gorev_durumu' => $gorev->gorev_durumu,
        ], 'Görev durumu başarıyla güncellendi');
    }

    /* Context7: durumGuncelle renamed to updateStatus — English naming standard */

    public function rapor(Gorev $gorev)
    {
        return view('admin.takim-yonetimi.gorevler.rapor', compact('gorev'));
    }

    public function addFile(Request $request, Gorev $gorev)
    {
        // Dosya ekleme işlemi
        // Context7: ResponseService kullan (response()->json() YASAK)
        return ResponseService::success(null, 'Dosya başarıyla eklendi');
    }

    public function deleteFile(Gorev $gorev, $dosyaId)
    {
        // Dosya silme işlemi
        // Context7: ResponseService kullan (response()->json() YASAK)
        return ResponseService::success(null, 'Dosya başarıyla silindi');
    }

    public function dashboard()
    {
        $istatistikler = [
            'toplam_gorev' => Gorev::count(),
            'tamamlanan_gorev' => Gorev::where('gorev_durumu', 'tamamlandi')->count(),
            'devam_eden_gorev' => Gorev::where('gorev_durumu', 'devam_ediyor')->count(),
            'gecikmis_gorev' => Gorev::gecikmis()->count(),
        ];

        return view('admin.takim-yonetimi.dashboard', compact('istatistikler'));
    }

    public function raporlar()
    {
        return view('admin.takim-yonetimi.raporlar');
    }

    /**
     * Kanban Board - Personel bazlı görev görselleştirme
     */
    public function board(Request $request)
    {
        // Tüm personeli getir (id, name)
        $users = \App\Models\User::select(['id', 'name', 'email'])
            ->whereHas('roles', function ($q) {
                $q->where('name', 'danisman');
            })
            ->orWhereHas('takimUyesi', function ($q) {
                $q->where('pozisyon', 'danisman');
            })
            ->orderBy('name')
            ->get();

        // Görevleri getir
        $gorevQuery = Gorev::with(['danisman', 'admin', 'musteri', 'proje'])
            ->whereIn('gorev_durumu', ['bekliyor', 'beklemede', 'devam_ediyor', 'tamamlandi']);

        // Filtreleme: Eğer user_id filtresi varsa sadece o kişiye ait görevleri getir
        $selectedUserId = $request->get('user_id');
        if ($selectedUserId) {
            $gorevQuery->where('atanan_user_id', $selectedUserId);
        } else {
            // Admin değilse sadece kendi görevlerini görsün
            $user = auth()->user();
            $isAdmin = $user && ($user->role_id == 1 || $user->role_id == 2); // 1: SuperAdmin, 2: Admin
            if (!$isAdmin) {
                $gorevQuery->where('atanan_user_id', auth()->id());
            }
        }

        // Status'e göre grupla ve sırala (Context7: display_order standardı)
        $filterCallback = function ($q) use ($selectedUserId) {
            if ($selectedUserId) {
                $q->where('atanan_user_id', $selectedUserId);
            } else {
                $user = auth()->user();
                $isAdmin = $user && ($user->role_id == 1 || $user->role_id == 2);
                if (!$isAdmin) {
                    $q->where('atanan_user_id', auth()->id());
                }
            }
        };

        // Bekleyenler: display_order varsa onu kullan, yoksa created_at (Context7 standardı)
        $bekleyenlerQuery = Gorev::with(['danisman', 'admin', 'musteri', 'proje'])
            ->whereIn('gorev_durumu', ['bekliyor', 'beklemede']);
        $filterCallback($bekleyenlerQuery);

        // display_order field'ı kontrol et (Schema'dan)
        $hasDisplayOrder = Schema::hasColumn('gorevler', 'display_order');
        if ($hasDisplayOrder) {
            $bekleyenler = $bekleyenlerQuery->latest('display_order')->get();
        } else {
            $bekleyenler = $bekleyenlerQuery->latest('created_at')->get();
        }

        // İşlemdekiler: updated_at'e göre sırala
        $islemdekiler = Gorev::with(['danisman', 'admin', 'musteri', 'proje'])
            ->where('gorev_durumu', 'devam_ediyor');
        $filterCallback($islemdekiler);
        $islemdekiler = $islemdekiler->latest('updated_at')->get();

        // Tamamlananlar: updated_at'e göre sırala, son 20 tanesi
        $tamamlananlar = Gorev::with(['danisman', 'admin', 'musteri', 'proje'])
            ->where('gorev_durumu', 'tamamlandi');
        $filterCallback($tamamlananlar);
        $tamamlananlar = $tamamlananlar->latest('updated_at')->limit(20)->get();

        return view('admin.takim.gorevler.board', compact(
            'users',
            'selectedUserId',
            'bekleyenler',
            'islemdekiler',
            'tamamlananlar'
        ));
    }

    public function topluGorevAta(Request $request)
    {
        $validated = $request->validate([
            'gorev_ids' => 'required|array|min:1',
            'gorev_ids.*' => 'required|integer|exists:gorevler,id',
            'danisman_id' => 'required|integer|exists:users,id',
        ]);

        $updatedCount = Gorev::whereIn('id', $validated['gorev_ids'])
            ->update(['atanan_user_id' => $validated['danisman_id']]);

        return ResponseService::success([
            'updated_count' => $updatedCount,
        ], 'Toplu görev atama tamamlandı');
    }

    public function gecmis(Gorev $gorev)
    {
        $gorev->load(['takip' => function ($query) {
            $query->latest('created_at');
        }]);

        return ResponseService::success([
            'gorev_id' => $gorev->id,
            'gecmis' => $gorev->takip,
        ], 'Görev geçmişi getirildi');
    }

    public function istatistikler()
    {
        return ResponseService::success([
            'toplam_gorev' => Gorev::count(),
            'bekleyen' => Gorev::where('gorev_durumu', 'beklemede')->count(),
            'devam_eden' => Gorev::where('gorev_durumu', 'devam_ediyor')->count(),
            'tamamlanan' => Gorev::where('gorev_durumu', 'tamamlandi')->count(),
        ], 'Görev istatistikleri getirildi');
    }
}
