<?php

namespace App\Modules\Emlak\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Emlak\Models\Proje;
use App\Modules\Emlak\Models\ProjeGorsel;
use App\Modules\Emlak\Models\ProjeTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProjeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projeler = Proje::with(['translations', 'gorseller'])->latest()->paginate(10);

        return view('emlak::projeler.index', compact('projeler'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // İl listesi - gerçek uygulamada bir API'den çekilebilir
        $iller = ['İstanbul', 'Ankara', 'İzmir', 'Antalya', 'Muğla', 'Bursa', 'Adana'];

        return view('emlak::projeler.create', compact('iller'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Form doğrulama
        $validated = $request->validate([
            'gelistirici_adi' => 'required|string|max:255',
            'proje_adi' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'tamamlanma_tarihi' => 'nullable|date',
            'proje_statusu' => 'required|in:Planlama,İnşaat,Tamamlandı',
            'adres_il' => 'required|string|max:255',
            'adres_ilce' => 'required|string|max:255',
            'adres_mahalle' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'one_cikan' => 'boolean',
            'gorseller' => 'nullable|array',
            'gorseller.*' => 'image|mimes:jpeg,png,jpg,gif|max:5048',
        ]);

        try {
            DB::beginTransaction();

            // Proje oluştur
            $proje = Proje::create([
                'gelistirici_adi' => $validated['gelistirici_adi'],
                'tamamlanma_tarihi' => $validated['tamamlanma_tarihi'] ?? null,
                'proje_statusu' => $validated['proje_statusu'],
                'one_cikan' => $request->boolean('one_cikan'),
                'adres_il' => $validated['adres_il'],
                'adres_ilce' => $validated['adres_ilce'],
                'adres_mahalle' => $validated['adres_mahalle'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ]);

            // Proje çevirisini oluştur (şu an için sadece status dil)
            ProjeTranslation::create([
                'proje_id' => $proje->id,
                'locale' => app()->getLocale(),
                'proje_adi' => $validated['proje_adi'],
                'aciklama' => $validated['aciklama'] ?? null,
            ]);

            // Görselleri yükle
            if ($request->hasFile('gorseller')) {
                $sira = 0;
                foreach ($request->file('gorseller') as $gorsel) {
                    $path = $gorsel->store('projeler/'.$proje->id, 'public');

                    ProjeGorsel::create([
                        'proje_id' => $proje->id,
                        'dosya_yolu' => $path,
                        'sira' => $sira++,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.projeler.index')
                ->with('success', 'Proje başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Proje oluşturulurken bir hata oluştu: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Proje $proje)
    {
        $proje->load(['translations', 'gorseller', 'ilanlar']);

        return view('emlak::projeler.show', compact('proje'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Proje $proje)
    {
        $proje->load(['translations', 'gorseller']);

        // İl listesi
        $iller = ['İstanbul', 'Ankara', 'İzmir', 'Antalya', 'Muğla', 'Bursa', 'Adana'];

        return view('emlak::projeler.edit', compact('proje', 'iller'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Proje $proje)
    {
        // Form doğrulama
        $validated = $request->validate([
            'gelistirici_adi' => 'required|string|max:255',
            'proje_adi' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'tamamlanma_tarihi' => 'nullable|date',
            'proje_statusu' => 'required|in:Planlama,İnşaat,Tamamlandı',
            'adres_il' => 'required|string|max:255',
            'adres_ilce' => 'required|string|max:255',
            'adres_mahalle' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'one_cikan' => 'boolean',
            'yeni_gorseller' => 'nullable|array',
            'yeni_gorseller.*' => 'image|mimes:jpeg,png,jpg,gif|max:5048',
            'silinecek_gorseller' => 'nullable|array',
            'silinecek_gorseller.*' => 'integer|exists:proje_gorselleri,id',
        ]);

        try {
            DB::beginTransaction();

            // Proje güncelle
            $proje->update([
                'gelistirici_adi' => $validated['gelistirici_adi'],
                'tamamlanma_tarihi' => $validated['tamamlanma_tarihi'] ?? null,
                'proje_statusu' => $validated['proje_statusu'],
                'one_cikan' => $request->boolean('one_cikan'),
                'adres_il' => $validated['adres_il'],
                'adres_ilce' => $validated['adres_ilce'],
                'adres_mahalle' => $validated['adres_mahalle'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ]);

            // Proje çevirisini güncelle
            $translation = $proje->translations()->where('locale', app()->getLocale())->first();
            if ($translation) {
                $translation->update([
                    'proje_adi' => $validated['proje_adi'],
                    'aciklama' => $validated['aciklama'] ?? null,
                ]);
            } else {
                // Çeviri yoksa oluştur
                ProjeTranslation::create([
                    'proje_id' => $proje->id,
                    'locale' => app()->getLocale(),
                    'proje_adi' => $validated['proje_adi'],
                    'aciklama' => $validated['aciklama'] ?? null,
                ]);
            }

            // Silinecek görselleri işle
            if ($request->has('silinecek_gorseller') && is_array($request->silinecek_gorseller)) {
                $silinecekGorseller = ProjeGorsel::whereIn('id', $request->silinecek_gorseller)
                    ->where('proje_id', $proje->id)
                    ->get();

                foreach ($silinecekGorseller as $gorsel) {
                    // Dosyayı storage'dan sil
                    if (Storage::disk('public')->exists($gorsel->dosya_yolu)) {
                        Storage::disk('public')->delete($gorsel->dosya_yolu);
                    }
                    // Veritabanı kaydını sil
                    $gorsel->delete();
                }
            }

            // Yeni görselleri yükle
            if ($request->hasFile('yeni_gorseller')) {
                // Mevcut en yüksek sıra numarasını bul
                $sonSira = $proje->gorseller()->max('sira') ?? -1;

                foreach ($request->file('yeni_gorseller') as $gorsel) {
                    $path = $gorsel->store('projeler/'.$proje->id, 'public');

                    ProjeGorsel::create([
                        'proje_id' => $proje->id,
                        'dosya_yolu' => $path,
                        'sira' => ++$sonSira,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.projeler.index')
                ->with('success', 'Proje başarıyla güncellendi.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Proje güncellenirken bir hata oluştu: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Proje $proje)
    {
        try {
            // Eager load gorseller to prevent N+1 queries during deletion
            $proje->load('gorseller');

            // Görselleri sil
            foreach ($proje->gorseller as $gorsel) {
                if (Storage::disk('public')->exists($gorsel->dosya_yolu)) {
                    Storage::disk('public')->delete($gorsel->dosya_yolu);
                }
            }

            // İlişkili kayıtları cascade ile silecek
            $proje->delete();

            return redirect()->route('admin.projeler.index')
                ->with('success', 'Proje başarıyla silindi.');
        } catch (\Exception $e) {
            return back()->with('error', 'Proje silinirken bir hata oluştu: '.$e->getMessage());
        }
    }
}
