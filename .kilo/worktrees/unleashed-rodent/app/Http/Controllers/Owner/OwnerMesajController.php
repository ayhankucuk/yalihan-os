<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Mesaj;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * OwnerMesajController
 *
 * Mülk sahibi paneli - Danışmanla iletişim modülü.
 *
 * SAB v6.1.2 — Owner Portal Sprint (Task #17)
 */
class OwnerMesajController extends Controller
{
    public function __construct(
        private \App\Application\Shared\Services\TenantContextResolver $tenantResolver
    ) {}

    /**
     * Mesajlaşma ekranını gösterir.
     */
    public function index(Request $request): View
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        $user = auth()->user();
        
        // Mülk sahibinin gönderdiği veya aldığı tüm mesajlar
        $tumMesajlar = Mesaj::with(['gonderen', 'alici'])
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($user) {
                $q->where('gonderen_id', $user->id)
                  ->orWhere('alici_id', $user->id);
            })
            ->orderBy('created_at', 'asc') // context7-ignore
            ->get();
            
        // Benzersiz danışman listesi (Kiminle konuşmuş/konuşabilir)
        // Öncelikle danışmanları bulmak için mevcut mesajlardaki karşı tarafları toplayalım
        $danismanIds = $tumMesajlar->map(function ($mesaj) use ($user) {
            return $mesaj->gonderen_id === $user->id ? $mesaj->alici_id : $mesaj->gonderen_id;
        })->unique();
        
        $danismanlar = User::whereIn('id', $danismanIds)->get();

        // Seçili sohbet var mı? URL'den al
        $seciliDanismanId = $request->get('danisman_id', $danismanlar->first()->id ?? null);
        
        $sohbetMesajlari = collect();
        $seciliDanisman = null;

        if ($seciliDanismanId) {
            $seciliDanisman = User::find($seciliDanismanId);
            
            // Seçili danışmanla olan sohbeti filtrele
            $sohbetMesajlari = $tumMesajlar->filter(function ($mesaj) use ($user, $seciliDanismanId) {
                return ($mesaj->gonderen_id === $user->id && $mesaj->alici_id == $seciliDanismanId) ||
                       ($mesaj->alici_id === $user->id && $mesaj->gonderen_id == $seciliDanismanId);
            })->values();

            // Okunmamış mesajları okundu olarak işaretle
            Mesaj::where('tenant_id', $tenantId)
                ->where('alici_id', $user->id)
                ->where('gonderen_id', $seciliDanismanId)
                ->where('okundu_mu', false)
                ->update(['okundu_mu' => true]);
        }

        return view('owner.mesajlar.index', compact('danismanlar', 'sohbetMesajlari', 'seciliDanismanId', 'seciliDanisman'));
    }

    /**
     * Yeni mesaj gönderir.
     */
    public function store(Request $request)
    {
        $request->validate([
            'alici_id' => 'required|exists:users,id',
            'icerik' => 'required|string|max:1000',
        ]);

        $tenantId = $this->tenantResolver->resolve()->tenantId;
        $user = auth()->user();

        Mesaj::create([
            'tenant_id' => $tenantId,
            'gonderen_id' => $user->id,
            'alici_id' => $request->alici_id,
            'icerik' => $request->icerik,
            'okundu_mu' => false,
        ]);

        // TODO: Danışmana "Yeni mesajınız var" bildirimi veya e-postası gönderilecek.

        return redirect()->route('owner.mesajlar.index', ['danisman_id' => $request->alici_id])
            ->with('basarili', 'Mesajınız başarıyla gönderildi.');
    }
}
