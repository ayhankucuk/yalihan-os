<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Models\AnahtarYonetimi;
use App\Models\Ilan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnahtarYonetimiController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('can:manage-keys');
    }

    /**
     * Anahtar yönetimi listesi
     */
    public function index()
    {
        $anahtarlar = AnahtarYonetimi::with(['ilan', 'teslimEden', 'teslimAlan', 'creator'])
            ->orderBy('created_at', 'desc') // context7-ignore
            ->paginate(20);

        return view('admin.anahtar-yonetimi.index', compact('anahtarlar'));
    }

    /**
     * Yeni anahtar oluşturma formu
     */
    public function create()
    {
        $ilanlar = Ilan::where('yayin_durumu', 'Yayında')->select(['id', 'ana_baslik', 'fiyat'])->get();
        $kullanicilar = User::where('aktiflik_durumu', true)->select(['id', 'isim', 'email'])->get();

        return view('admin.anahtar-yonetimi.create', compact('ilanlar', 'kullanicilar'));
    }

    /**
     * Anahtar kaydetme
     */
    public function store(Request $request, \App\Actions\Admin\Key\StoreKeyAction $action)
    {
        $data = $request->validate([
            'ilan_id' => 'required|exists:ilanlar,id',
            'anahtar_durumu' => 'required|in:Beklemede,Hazır,Teslim Edildi,Geri Alındı,Kayıp',
            'teslim_tarihi' => 'nullable|date',
            'teslim_eden_kisi_id' => 'nullable|exists:users,id',
            'teslim_alan_kisi_id' => 'nullable|exists:users,id',
            'anahtar_konumu' => 'nullable|string|max:255',
            'anahtar_notlari' => 'nullable|string',
            'anahtar_tipi' => 'required|in:Ana Anahtar,Yedek Anahtar,Kodlu Anahtar,Kartlı Anahtar,Uzaktan Kumanda',
            'anahtar_sayisi' => 'required|integer|min:1',
            'anahtar_ozellikleri' => 'nullable|array',
        ]);

        $action->handle($data);

        return redirect()->route('admin.anahtar-yonetimi.index')
            ->with('success', 'Anahtar başarıyla oluşturuldu.');
    }

    /**
     * Anahtar detayları
     */
    public function show($id)
    {
        $anahtar = AnahtarYonetimi::with(['ilan', 'teslimEden', 'teslimAlan', 'creator', 'updater'])
            ->findOrFail($id);

        return view('admin.anahtar-yonetimi.show', compact('anahtar'));
    }

    /**
     * Anahtar düzenleme formu
     */
    public function edit($id)
    {
        $anahtar = AnahtarYonetimi::findOrFail($id);
        $ilanlar = Ilan::where('yayin_durumu', 'Yayında')->select(['id', 'ana_baslik', 'fiyat'])->get();
        $kullanicilar = User::where('aktiflik_durumu', true)->select(['id', 'isim', 'email'])->get();

        return view('admin.anahtar-yonetimi.edit', compact('anahtar', 'ilanlar', 'kullanicilar'));
    }

    /**
     * Anahtar güncelleme
     */
    public function update(Request $request, $id, \App\Actions\Admin\Key\UpdateKeyAction $action)
    {
        $anahtar = AnahtarYonetimi::findOrFail($id);

        $data = $request->validate([
            'anahtar_durumu' => 'required|in:Beklemede,Hazır,Teslim Edildi,Geri Alındı,Kayıp',
            'teslim_tarihi' => 'nullable|date',
            'teslim_eden_kisi_id' => 'nullable|exists:users,id',
            'teslim_alan_kisi_id' => 'nullable|exists:users,id',
            'anahtar_konumu' => 'nullable|string|max:255',
            'anahtar_notlari' => 'nullable|string',
            'anahtar_tipi' => 'required|in:Ana Anahtar,Yedek Anahtar,Kodlu Anahtar,Kartlı Anahtar,Uzaktan Kumanda',
            'anahtar_sayisi' => 'required|integer|min:1',
            'anahtar_ozellikleri' => 'nullable|array',
        ]);

        $action->handle($anahtar, $data);

        return redirect()->route('admin.anahtar-yonetimi.index')
            ->with('success', 'Anahtar başarıyla güncellendi.');
    }

    /**
     * Anahtar silme
     */
    public function destroy($id, \App\Actions\Admin\Key\DeleteKeyAction $action)
    {
        $anahtar = AnahtarYonetimi::findOrFail($id);
        $action->handle($anahtar);

        return redirect()->route('admin.anahtar-yonetimi.index')
            ->with('success', 'Anahtar başarıyla silindi.');
    }

    /**
     * Anahtar durumu güncelleme (AJAX)
     */
    public function updateDurum(Request $request, $id, \App\Actions\Admin\Key\UpdateKeyAction $action)
    {
        $anahtar = AnahtarYonetimi::findOrFail($id);

        $data = $request->validate([
            'anahtar_durumu' => 'required|in:Beklemede,Hazır,Teslim Edildi,Geri Alındı,Kayıp',
        ]);

        $data['teslim_tarihi'] = $data['anahtar_durumu'] === 'Teslim Edildi' ? now() : $anahtar->teslim_tarihi;

        $action->handle($anahtar, $data);

        return response()->json([
            'success' => true,
            'message' => 'Anahtar durumu başarıyla güncellendi.',
            'data' => $anahtar->fresh(),
        ]);
    }

    /**
     * Anahtar teslim etme
     */
    public function deliver(Request $request, $id, \App\Actions\Admin\Key\UpdateKeyAction $action)
    {
        $anahtar = AnahtarYonetimi::findOrFail($id);

        if (! $anahtar->canBeDelivered()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu anahtar teslim edilemez.',
            ], 400);
        }

        $data = $request->validate([
            'teslim_alan_kisi_id' => 'required|exists:users,id',
            'anahtar_notlari' => 'nullable|string',
        ]);

        $data['anahtar_durumu'] = 'Teslim Edildi';
        $data['teslim_tarihi'] = now();
        $data['teslim_eden_kisi_id'] = auth()->id();

        $action->handle($anahtar, $data);

        return response()->json([
            'success' => true,
            'message' => 'Anahtar başarıyla teslim edildi.',
            'data' => $anahtar->fresh(),
        ]);
    }
}
