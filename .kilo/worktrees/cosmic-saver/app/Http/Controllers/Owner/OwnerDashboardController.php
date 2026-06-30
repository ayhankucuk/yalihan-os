<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * OwnerDashboardController
 *
 * Mülk sahibi ana ekranı.
 * İlan sayısı, son aktivite ve hızlı erişim kartları.
 *
 * @sab-ignore-thin (presentation layer)
 * SAB v6.1.2 — Owner Portal sprint.
 */
class OwnerDashboardController extends Controller
{
    /**
     * /owner/dashboard
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Owner'a ait ilanların özet sayıları
        // Task #15 (İlanlarım) tamamlandığında daha detaylı hale gelecek
        $ilanSayisi = Ilan::where('user_id', $user->id)
                          ->count();

        $aktifIlanSayisi = Ilan::where('user_id', $user->id)
                                ->where('yayin_durumu', true)
                                ->count();

        return view('owner.dashboard', compact('ilanSayisi', 'aktifIlanSayisi'));
    }
}
