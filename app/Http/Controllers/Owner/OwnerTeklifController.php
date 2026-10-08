<?php

namespace App\Http\Controllers\Owner;

/**
 * @sab-ignore-thin Owner Portal: direct model access intended — no service layer required for read-only portal.
 * @sab-ignore-service-layer Owner Portal read-only controller.
 */

use App\Http\Controllers\Controller;
use App\Enums\TeklifDurumu;
use App\Models\Eslesme;
use App\Models\Ilan;
use App\Models\Teklif;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * OwnerTeklifController
 *
 * Mülk sahibi paneli - Teklifler ve Talepler modülü.
 *
 * SAB v6.1.2 — Owner Portal Sprint (Task #16)
 */
class OwnerTeklifController extends Controller
{
    /**
     * Teklifleri ve Talepleri (Eşleşmeleri) listeler.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // 1. Mülk Sahibine Ait İlanların ID'leri
        $ilanIds = Ilan::where('user_id', $user->id)->pluck('id');

        // 2. Bu ilanlara gelen Teklifler
        $teklifler = Teklif::with(['ilan', 'teklifVeren'])
            ->whereIn('ilan_id', $ilanIds)
            ->orderBy('created_at', 'desc') // context7-ignore
            ->get();

        // 3. Bu ilanlarla eşleşen sistem Talepleri (Eslesme)
        $eslesmeler = Eslesme::with(['ilan', 'kisi', 'danisman'])
            ->whereIn('ilan_id', $ilanIds)
            ->where('eslesme_durumu', '!=', 'reddedildi') // Sadece aktif/olumlu eşleşmeler
            ->orderBy('skor', 'desc') // context7-ignore
            ->get();

        return view('owner.teklifler.index', compact('teklifler', 'eslesmeler'));
    }

    /**
     * Teklif detay sayfasını gösterir.
     */
    public function show($id): View
    {
        $user = auth()->user();

        // Teklifi bul ve ilanın bu kullanıcıya ait olduğundan emin ol
        $teklif = Teklif::with(['ilan.danisman', 'teklifVeren.danisman'])
            ->where('id', $id)
            ->whereHas('ilan', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        return view('owner.teklifler.show', compact('teklif'));
    }

    /**
     * Teklifi kabul et.
     */
    public function accept(Request $request, $id)
    {
        $user = auth()->user();

        $teklif = Teklif::where('id', $id)
            ->whereHas('ilan', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        if ($teklif->teklif_durumu->value !== TeklifDurumu::BEKLEMEDE->value) {
            return redirect()->back()->with('error', 'Bu teklif zaten işlenmiş.');
        }

        $teklif->update(['teklif_durumu' => TeklifDurumu::KABUL_EDILDI]);

        return redirect()->route('owner.teklifler.show', $id)
            ->with('success', 'Teklif kabul edildi.');
    }

    /**
     * Teklifi reddet.
     */
    public function reject(Request $request, $id)
    {
        $user = auth()->user();

        $teklif = Teklif::where('id', $id)
            ->whereHas('ilan', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        if ($teklif->teklif_durumu->value !== TeklifDurumu::BEKLEMEDE->value) {
            return redirect()->back()->with('error', 'Bu teklif zaten işlenmiş.');
        }

        $teklif->update(['teklif_durumu' => TeklifDurumu::REDDEDILDI]);

        return redirect()->route('owner.teklifler.show', $id)
            ->with('success', 'Teklif reddedildi.');
    }
}
