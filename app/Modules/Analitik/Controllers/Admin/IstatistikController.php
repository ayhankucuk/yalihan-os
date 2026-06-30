<?php

namespace App\Modules\Analitik\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\User;

class IstatistikController extends Controller
{
    public function index()
    {
        // 📊 2026 Ocak - Analitik Dashboard MVP
        // Context7 Standard: Minimal gerçek veri ile başla
        
        $stats = [
            // Toplam İlan (tüm mühürlü portföyler)
            'total_ilanlar' => Ilan::count(),
            
            // Aktif Kullanıcı (son 30 günde giriş yapan)
            'active_users' => User::where('last_activity_at', '>=', now()->subDays(30))
                ->orWhere('updated_at', '>=', now()->subDays(30))
                ->count(),
            
            // Aylık Gelir (finansal hesaplamalar yapılacak)
            'monthly_revenue' => 0,
            
            // Dönüşüm Oranı (İlan -> Satış analizi yapılacak)
            'conversion_rate' => 0,
            
            // Aylık Yeni İlan (Ocak 2026)
            'monthly_new_listings' => Ilan::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
            
            // Aktif Danışman (aktiflik_durumu = true)
            'active_advisors' => User::whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'danisman', 'super-admin']);
            })->where('aktiflik_durumu', true)->count(),
            
            // Bekleyen İlanlar (onay bekleyen)
            'pending_listings' => Ilan::where('yayin_durumu', 'beklemede')
                ->orWhere('yayin_durumu', 'taslak')
                ->count(),
        ];

        return view('admin.analitik.istatistikler.index', compact('stats'));
    }

    public function genel()
    {
        return view('admin.analitik.istatistikler.genel');
    }

    public function ilan()
    {
        return view('admin.analitik.istatistikler.ilan');
    }

    public function satis()
    {
        return view('admin.analitik.istatistikler.satis');
    }

    public function finans()
    {
        return view('admin.analitik.istatistikler.finans');
    }

    public function musteri()
    {
        return view('admin.analitik.istatistikler.musteri');
    }
}
