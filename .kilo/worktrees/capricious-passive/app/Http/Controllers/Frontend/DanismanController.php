<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class DanismanController extends Controller
{
    /**
     * Danışmanlar listesi sayfası
     * Context7 Compliance: Gerçek verilerle danışman listesi
     */
    public function index(Request $request)
    {
        // Danışman rolüne sahip kullanıcıları çek
        $query = User::whereHas('roles', function ($q) {
            $q->where('name', 'danisman');
        })->with(['roles']);

        // Filtreleme
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        // Departman filtresi
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        // Pozisyon filtresi
        if ($request->filled('position')) {
            $query->where('position', $request->position);
        }

        // Status filtresi
        if ($request->filled('danisman_durumu')) {
            if ($request->danisman_durumu === 'aktif') {
                // Context7: Boolean field only, no phantom aktiflik_label
                $query->where('aktiflik_durumu', 1);
            } elseif ($request->danisman_durumu === 'pasif') {
                // Context7: Boolean field only, no phantom aktiflik_label
                $query->where('aktiflik_durumu', 0);
            }
        }

        // Sıralama
        $sort = $request->get('sort', 'name_asc');
        switch ($sort) {
            case 'name_desc':
                $query->orderBy('name', 'desc'); // context7-ignore
                break;
            case 'created_desc':
                $query->orderBy('created_at', 'desc'); // context7-ignore
                break;
            case 'created_asc':
                $query->orderBy('created_at', 'asc'); // context7-ignore
                break;
            default:
                $query->orderBy('name', 'asc'); // context7-ignore
        }

        $danismanlar = $query->paginate(12)->withQueryString();

        // İstatistikler
        $danismanIds = User::whereHas('roles', function ($q) {
            $q->where('name', 'danisman');
        })->pluck('id')->toArray();

        $stats = [
            'total' => count($danismanIds),
            // Context7: Boolean aktiflik_durumu only, no phantom aktiflik_label
            'aktiflik_durumu' => User::whereHas('roles', function ($q) {
                $q->where('name', 'danisman');
            })->where('aktiflik_durumu', 1)->count(),
            // Context7: Ilan uses yayin_durumu, not aktiflik_durumu
            'toplam_ilan' => \App\Models\Ilan::whereIn('danisman_id', $danismanIds)
                ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
                ->count(),
        ];

        // Departman ve pozisyon seçenekleri
        $departments = config('danisman.departments', []);
        $positions = config('danisman.positions', []);

        return view('frontend.danismanlar.index', compact('danismanlar', 'stats', 'departments', 'positions'));
    }

    /**
     * Danışman detay sayfası
     */
    public function show($id)
    {
        $danisman = User::whereHas('roles', function ($q) {
            $q->where('name', 'danisman');
        })->with(['roles', 'onayliDanismanYorumlari' => function ($query) {
            $query->with('kisi')->latest()->limit(10);
        }])->findOrFail($id);

        // Danışmanın aktif ilanları
        // Context7: Ilan uses yayin_durumu, not aktiflik_durumu
        $ilanlar = \App\Models\Ilan::where('danisman_id', $danisman->id)
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->with(['il', 'ilce', 'anaKategori', 'fotograflar'])
            ->latest()
            ->limit(6)
            ->get();

        // Performans istatistikleri
        // Context7: Ilan uses yayin_durumu, not aktiflik_durumu
        $performans = [
            'toplam_ilan' => \App\Models\Ilan::where('danisman_id', $danisman->id)->count(),
            'aktif_ilan' => \App\Models\Ilan::where('danisman_id', $danisman->id)->where('yayin_durumu', IlanDurumu::YAYINDA->value)->count(),
            'toplam_yorum' => $danisman->danismanYorumlari()->count(),
            'onayli_yorum' => $danisman->onayliDanismanYorumlari()->count(),
            'ortalama_rating' => $danisman->onayliDanismanYorumlari()->avg('rating') ?? 0,
        ];

        return view('frontend.danismanlar.show', compact('danisman', 'ilanlar', 'performans'));
    }
}
