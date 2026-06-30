<?php

namespace App\Modules\TakimYonetimi\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proje;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

class ProjeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-settings');
    }

    public function index()
    {
        $projeler = Proje::with(['user', 'takim'])->paginate(15);

        return view('takimyonetimi::admin.projeler.index', compact('projeler'));
    }

    public function create()
    {
        $users = \App\Models\User::orderBy('name')->get();
        return view('takimyonetimi::admin.projeler.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'proje_adi' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'baslangic_tarihi' => 'required|date',
            'bitis_tarihi' => 'nullable|date|after:baslangic_tarihi',
            'islem_durumu' => 'required|in:planlama,devam_ediyor,tamamlandi,iptal,beklemede',
            'oncelik' => 'required|in:dusuk,orta,yuksek,kritik',
            'user_id' => 'required|exists:users,id',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $proje = Proje::create($validated);

        return redirect()->route('admin.takim.projeler.index')
            ->with('success', 'Proje başarıyla oluşturuldu.');
    }

    public function show(Proje $proje)
    {
        $proje->load(['user', 'takim', 'gorevler.user']);

        return view('takimyonetimi::admin.projeler.show', compact('proje'));
    }

    public function edit(Proje $proje)
    {
        $users = \App\Models\User::orderBy('name')->get();
        return view('takimyonetimi::admin.projeler.edit', compact('proje', 'users'));
    }

    public function update(Request $request, Proje $proje)
    {
        $validated = $request->validate([
            'proje_adi' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'baslangic_tarihi' => 'required|date',
            'bitis_tarihi' => 'nullable|date|after:baslangic_tarihi',
            'islem_durumu' => 'required|in:planlama,devam_ediyor,tamamlandi,iptal,beklemede',
            'oncelik' => 'required|in:dusuk,orta,yuksek,kritik',
            'user_id' => 'required|exists:users,id',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $proje->update($validated);

        return redirect()->route('admin.takim.projeler.index')
            ->with('success', 'Proje başarıyla güncellendi.');
    }

    public function destroy(Proje $proje)
    {
        $proje->delete();

        return redirect()->route('admin.takim.projeler.index')
            ->with('success', 'Proje başarıyla silindi.');
    }

    public function addTask(Request $request, Proje $proje)
    {
        // Görev ekleme işlemi
        return response()->json(['success' => true]);
    }

    public function rapor(Proje $proje)
    {
        // Proje raporu
        return view('takimyonetimi::admin.projeler.rapor', compact('proje'));
    }

    public function updateStatus(Request $request, Proje $proje)
    {
        $validated = $request->validate([
            'islem_durumu' => 'required|in:planlama,devam_ediyor,tamamlandi,iptal,beklemede',
        ]);

        $proje->update([
            'islem_durumu' => $validated['islem_durumu'],
        ]);

        return ResponseService::success([
            'proje_id' => $proje->id,
            'islem_durumu' => $validated['islem_durumu'],
        ], 'Proje durumu güncellendi');
    }

    public function gorevler(Proje $proje)
    {
        $proje->load('gorevler');

        return ResponseService::success([
            'proje_id' => $proje->id,
            'gorevler' => $proje->gorevler ?? [],
        ], 'Proje görevleri');
    }

    public function updateProgress(Request $request, Proje $proje)
    {
        $validated = $request->validate([
            'ilerleme_yuzdesi' => 'required|integer|min:0|max:100',
        ]);

        $proje->update([
            'ilerleme_yuzdesi' => $validated['ilerleme_yuzdesi'],
        ]);

        return ResponseService::success([
            'proje_id' => $proje->id,
            'ilerleme_yuzdesi' => (int) $validated['ilerleme_yuzdesi'],
        ], 'Proje ilerleme bilgisi güncellendi');
    }
}
