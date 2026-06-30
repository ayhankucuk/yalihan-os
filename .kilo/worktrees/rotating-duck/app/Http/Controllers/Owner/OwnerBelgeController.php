<?php

namespace App\Http\Controllers\Owner;

/**
 * @sab-ignore-thin Owner Portal: direct model access intended — no service layer required for read-only portal.
 * @sab-ignore-service-layer Owner Portal read-only controller.
 */

use App\Http\Controllers\Controller;
use App\Models\Belge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * OwnerBelgeController
 *
 * Mülk sahibi paneli - Belgelerim modülü.
 *
 * SAB v6.1.2 — Owner Portal Sprint (Task #18)
 */
class OwnerBelgeController extends Controller
{
    public function __construct(
        private \App\Application\Shared\Services\TenantContextResolver $tenantResolver
    ) {}

    /**
     * Mülk sahibinin belgelerini listeler.
     */
    public function index(Request $request): View
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        $user = auth()->user();
        
        // Kullanıcıya ait tüm belgeler
        $belgeler = Belge::with('ilan')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc') // context7-ignore
            ->get();
            
        // Türlerine göre grupla (Arayüzde kolaylık sağlamak için)
        $grupluBelgeler = $belgeler->groupBy('belge_turu');
        
        return view('owner.belgeler.index', compact('belgeler', 'grupluBelgeler'));
    }

    /**
     * Belge dosyasını güvenli bir şekilde indirir.
     */
    public function download($id): StreamedResponse
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        $user = auth()->user();
        
        // Belgeyi bul ve kullanıcıya ait olduğundan emin ol
        $belge = Belge::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Dosya mevcut mu kontrol et
        if (!Storage::disk('public')->exists($belge->dosya_yolu)) {
            abort(404, 'İlgili dosya bulunamadı.');
        }

        // Güvenli indirme
        return Storage::disk('public')->download(
            $belge->dosya_yolu, 
            $belge->baslik . '.' . $belge->dosya_tipi
        );
    }
}
