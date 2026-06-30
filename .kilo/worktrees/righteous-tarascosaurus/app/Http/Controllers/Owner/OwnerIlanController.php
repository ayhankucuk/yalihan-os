<?php

namespace App\Http\Controllers\Owner;

/**
 * @sab-ignore-thin Owner Portal: direct model access intended — no service layer required for read-only portal.
 * @sab-ignore-service-layer Owner Portal read-only controller.
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * OwnerIlanController
 *
 * Mülk sahibi için ilan listeleme ve detay sayfası.
 *
 * SAB v6.1.2 — Owner Portal Sprint (Task #15)
 */
class OwnerIlanController extends Controller
{
    /**
     * İlanları listeler.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Mülk sahibinin ilanları (SAB Task #15: user_id = auth user)
        $ilanlar = Ilan::with(['il', 'ilce', 'mahalle', 'anaKategori', 'altKategori'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc') // context7-ignore
            ->paginate(12);

        return view('owner.ilanlar.index', compact('ilanlar'));
    }

    /**
     * İlan detaylarını gösterir.
     */
    public function show($id): View
    {
        $user = auth()->user();

        // İlanın bu mülk sahibine ait olup olmadığını kontrol et
        $ilan = Ilan::with(['il', 'ilce', 'mahalle', 'anaKategori', 'altKategori', 'fotograflar', 'danisman'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return view('owner.ilanlar.show', compact('ilan'));
    }
}
