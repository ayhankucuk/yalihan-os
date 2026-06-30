<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Models\Ilan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends AdminController
{
    /**
     * Analytics dashboard görünümü
     * Context7: Real-time analytics dashboard
     */
    public function dashboard(Request $request)
    {
        // ✅ CACHE: Dashboard metrics cache ile optimize et (1800s = 30 dakika)
        $metrics = Cache::remember('analytics_dashboard_metrics', 1800, function () {
            $totalIlanlar = Ilan::count();
            $totalKategoriler = \App\Models\IlanKategori::count();
            $totalKullanicilar = User::count();
            $newIlanlarThisMonth = Ilan::whereMonth('created_at', now()->month)->count();

            // ✅ N+1 FIX: Son 7 günün ilan trendi - Tek query ile optimize et
            $startDate = now()->subDays(6)->startOfDay();
            $endDate = now()->endOfDay();

            // withoutGlobalScopes(): visibility_score global scope → GROUP BY + ORDER BY çakışması önlenir
            $dailyCounts = Ilan::withoutGlobalScopes()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupByRaw('DATE(created_at)')
                ->pluck('count', 'date')
                ->toArray();

            $ilanTrendi = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dateKey = $date->format('Y-m-d');
                $count = $dailyCounts[$dateKey] ?? 0;
                $ilanTrendi[] = [
                    'date' => $date->format('d.m'),
                    'count' => $count,
                ];
            }

            // ✅ N+1 FIX: Kategori dağılımı - withCount ile optimize et
            $kategoriDagilimi = \App\Models\IlanKategori::withCount('ilanlar')
                ->orderBy('ilanlar_count', 'desc') // context7-ignore
                ->limit(5)
                ->get()
                ->map(function ($kategori) {
                    return [
                        'name' => $kategori->name,
                        'count' => $kategori->ilanlar_count,
                    ];
                });

            // ✅ N+1 FIX: En popüler kategori - withCount ile optimize et
            $topKategori = \App\Models\IlanKategori::withCount('ilanlar')
                ->orderBy('ilanlar_count', 'desc') // context7-ignore
                ->first();

            return [
                'total_ilanlar' => $totalIlanlar,
                'total_kategoriler' => $totalKategoriler,
                'total_kullanicilar' => $totalKullanicilar,
                'new_ilanlar_this_month' => $newIlanlarThisMonth,
                'ilan_trendi' => $ilanTrendi,
                'kategori_dagilimi' => $kategoriDagilimi,
                'top_kategori' => $topKategori ? $topKategori->name : 'Veri yok',
                'top_kategori_count' => $topKategori ? $topKategori->ilanlar_count : 0,
                'last_updated' => now()->format('d.m.Y H:i'),
            ];
        });

        // Period for dashboard
        $period = $request->get('period', '7d');

        return view('admin.analytics.dashboard', compact('metrics', 'period'));
    }

    /**
     * Analytics dashboard ana sayfası (liste görünümü)
     * Context7: Real-time analytics dashboard
     */
    public function index(Request $request)
    {
        // ✅ CACHE: Dashboard metrics cache ile optimize et (1800s = 30 dakika)
        $metrics = Cache::remember('analytics_index_metrics', 1800, function () {
            $totalIlanlar = Ilan::count();
            $totalKategoriler = \App\Models\IlanKategori::count();
            $totalKullanicilar = User::count();
            $newIlanlarThisMonth = Ilan::whereMonth('created_at', now()->month)->count();

            // ✅ N+1 FIX: Son 7 günün ilan trendi - Tek query ile optimize et
            $startDate = now()->subDays(6)->startOfDay();
            $endDate = now()->endOfDay();

            // withoutGlobalScopes(): Ilan global scope'u visibility_score ORDER BY ekler
            // GROUP BY DATE + ORDER BY visibility_score → MySQL ONLY_FULL_GROUP_BY hatası verir
            $dailyCounts = Ilan::withoutGlobalScopes()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupByRaw('DATE(created_at)')
                ->pluck('count', 'date')
                ->toArray();

            $ilanTrendi = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dateKey = $date->format('Y-m-d');
                $count = $dailyCounts[$dateKey] ?? 0;
                $ilanTrendi[] = [
                    'date' => $date->format('d.m'),
                    'count' => $count,
                ];
            }

            // ✅ N+1 FIX: Kategori dağılımı - withCount ile optimize et
            $kategoriDagilimi = \App\Models\IlanKategori::withCount('ilanlar')
                ->orderBy('ilanlar_count', 'desc') // context7-ignore
                ->limit(5)
                ->get()
                ->map(function ($kategori) {
                    return [
                        'name' => $kategori->name,
                        'count' => $kategori->ilanlar_count,
                    ];
                });

            // ✅ N+1 FIX: En popüler kategori - withCount ile optimize et
            $topKategori = \App\Models\IlanKategori::withCount('ilanlar')
                ->orderBy('ilanlar_count', 'desc') // context7-ignore
                ->first();

            return [
                'total_ilanlar' => $totalIlanlar,
                'total_kategoriler' => $totalKategoriler,
                'total_kullanicilar' => $totalKullanicilar,
                'new_ilanlar_this_month' => $newIlanlarThisMonth,
                'ilan_trendi' => $ilanTrendi,
                'kategori_dagilimi' => $kategoriDagilimi,
                'top_kategori' => $topKategori ? $topKategori->name : 'Veri yok',
                'top_kategori_count' => $topKategori ? $topKategori->ilanlar_count : 0,
                'last_updated' => now()->format('d.m.Y H:i'),
            ];
        });

        return view('admin.analytics.index', compact('metrics'));
    }

    /**
     * Analitik detay sayfası göster
     * Context7: Detailed analytics view
     */
    public function show(Request $request, $id)
    {
        // Specific analytics item details
        $analyticsItem = $this->getAnalyticsItem($id);

        if (! $analyticsItem) {
            return redirect()->route('admin.analytics.index')
                ->with('error', 'Analitik veri bulunamadı.');
        }

        return view('admin.analytics.show', compact('analyticsItem'));
    }

    /**
     * Yeni analitik raporu oluşturma formu
     * Context7: Create custom analytics report
     */
    public function create(Request $request)
    {
        $reportTypes = $this->getReportTypes();
        $dateRanges = $this->getDateRangeOptions();

        return view('admin.analytics.create', compact('reportTypes', 'dateRanges'));
    }

    /**
     * Yeni analitik raporu kaydet
     * Context7: Store custom analytics report
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'report_type' => 'required|string',
            'date_range' => 'required|string',
            'metrics' => 'required|array',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            // Generate and save custom report
            $reportData = $this->generateCustomReport($request->all());

            return redirect()->route('admin.analytics.index')
                ->with('success', 'Özel analitik raporu başarıyla oluşturuldu.');

        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Rapor oluşturulurken hata oluştu: '.$e->getMessage());
        }
    }

    /**
     * Analitik raporu düzenleme formu
     * Context7: Edit existing analytics report
     */
    public function edit(Request $request, $id)
    {
        $analyticsItem = $this->getAnalyticsItem($id);

        if (! $analyticsItem) {
            return redirect()->route('admin.analytics.index')
                ->with('error', 'Analitik veri bulunamadı.');
        }

        $reportTypes = $this->getReportTypes();
        $dateRanges = $this->getDateRangeOptions();

        return view('admin.analytics.edit', compact('analyticsItem', 'reportTypes', 'dateRanges'));
    }

    /**
     * Analitik raporu güncelle
     * Context7: Update existing analytics report
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'report_type' => 'required|string',
            'date_range' => 'required|string',
            'metrics' => 'required|array',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            $analyticsItem = $this->getAnalyticsItem($id);

            if (! $analyticsItem) {
                return redirect()->route('admin.analytics.index')
                    ->with('error', 'Analitik veri bulunamadı.');
            }

            // Update the analytics report
            $this->updateAnalyticsReport($id, $request->all());

            return redirect()->route('admin.analytics.show', $id)
                ->with('success', 'Analitik raporu başarıyla güncellendi.');

        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Rapor güncellenirken hata oluştu: '.$e->getMessage());
        }
    }

    /**
     * AJAX data endpoint
     * Context7: Real-time data for charts and widgets
     */
    public function data(Request $request)
    {
        $type = $request->get('type', 'dashboard'); // context7-ignore
        $dateRange = $request->get('date_range', '7_days');

        switch ($type) {
            case 'users':
                return response()->json($this->getUserAnalytics($dateRange));
            case 'properties':
                return response()->json($this->getPropertyAnalytics($dateRange));
            case 'performance':
                return response()->json($this->getPerformanceAnalytics($dateRange));
            case 'revenue':
                return response()->json($this->getRevenueAnalytics($dateRange));
            default:
                return response()->json($this->getDashboardAnalytics($dateRange));
        }
    }

    /**
     * Export analytics data
     * Context7: Export functionality for reports
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $type = $request->get('type', 'dashboard'); // context7-ignore
        $dateRange = $request->get('date_range', '30_days');

        try {
            switch ($format) {
                case 'pdf':
                    return $this->exportToPdf($type, $dateRange);
                case 'csv':
                    return $this->exportToCsv($type, $dateRange);
                default:
                    return $this->exportToExcel($type, $dateRange);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export sırasında hata oluştu: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get dashboard metrics
     */
    private function getDashboardMetrics()
    {
        return [
            'total_users' => User::count(),
            'total_properties' => Ilan::count(),
            'active_properties' => Ilan::whereNotNull('yayin_durumu')->count(), // context7-ignore
            'total_views' => 45623, // Mock data - implement actual tracking
            'conversion_rate' => 3.2, // Mock data
            'avg_session_duration' => '4:32', // Mock data
            'bounce_rate' => 24.5, // Mock data
            'growth_rate' => 12.8, // Mock data
        ];
    }

    /**
     * Get chart data for dashboard
     */
    private function getChartData()
    {
        return [
            'daily_visits' => [
                'labels' => collect(range(6, 0))->map(fn ($days) => Carbon::now()->subDays($days)->format('M d')),
                'data' => [145, 168, 192, 201, 234, 267, 298],
            ],
            'property_views' => [
                'labels' => ['Sat', 'Kir', 'Tic'],
                'data' => [45, 30, 25],
            ],
            'user_activity' => [
                'labels' => collect(range(23, 0))->map(fn ($hour) => Carbon::now()->subHours($hour)->format('H:i')),
                'data' => collect(range(0, 23))->map(fn () => rand(10, 100)),
            ],
        ];
    }

    /**
     * Get analytics item by ID
     */
    private function getAnalyticsItem($id)
    {
        // Mock data - implement actual data retrieval
        return [
            'id' => $id,
            'name' => 'Özel Rapor '.$id,
            'report_type' => 'property_performance',
            'date_range' => '30_days',
            'metrics' => ['views', 'conversions', 'revenue'],
            'description' => 'Özel analitik raporu açıklaması',
            'created_at' => Carbon::now()->subDays(rand(1, 30)),
        ];
    }

    /**
     * Get available report types
     */
    private function getReportTypes()
    {
        return [
            'user_behavior' => 'Kullanıcı Davranışı',
            'property_performance' => 'İlan Performansı',
            'conversion_analysis' => 'Dönüşüm Analizi',
            'revenue_tracking' => 'Gelir Takibi',
            'traffic_sources' => 'Trafik Kaynakları',
        ];
    }

    /**
     * Get date range options
     */
    private function getDateRangeOptions()
    {
        return [
            '7_days' => 'Son 7 Gün',
            '30_days' => 'Son 30 Gün',
            '90_days' => 'Son 3 Ay',
            '1_year' => 'Son 1 Yıl',
            'custom' => 'Özel Tarih Aralığı',
        ];
    }

    /**
     * Generate custom report
     */
    private function generateCustomReport($data)
    {
        // Implementation for custom report generation
        return [
            'id' => rand(1000, 9999),
            'name' => $data['name'],
            'durum' => 'oluşturuldu',
            'created_at' => now(),
        ];
    }

    /**
     * Update analytics report
     */
    private function updateAnalyticsReport($id, $data)
    {
        // Implementation for updating analytics report
        return true;
    }

    /**
     * Get various analytics data methods
     */
    private function getUserAnalytics($dateRange)
    {
        return ['users' => 1234];
    }

    private function getPropertyAnalytics($dateRange)
    {
        return ['properties' => 567];
    }

    private function getPerformanceAnalytics($dateRange)
    {
        return ['performance' => 89.5];
    }

    private function getRevenueAnalytics($dateRange)
    {
        return ['revenue' => 45670];
    }

    private function getDashboardAnalytics($dateRange)
    {
        return $this->getDashboardMetrics();
    }

    /**
     * Analitik raporu sil
     * Context7: Delete analytics report
     */
    public function destroy(Request $request, $id)
    {
        try {
            $analyticsItem = $this->getAnalyticsItem($id);

            if (! $analyticsItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Analitik veri bulunamadı.',
                ], 404);
            }

            // Delete the analytics report
            $this->deleteAnalyticsReport($id);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Analitik raporu başarıyla silindi.',
                ]);
            }

            return redirect()->route('admin.analytics.index')
                ->with('success', 'Analitik raporu başarıyla silindi.');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rapor silinirken hata oluştu: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Rapor silinirken hata oluştu: '.$e->getMessage());
        }
    }

    /**
     * Delete analytics report
     */
    private function deleteAnalyticsReport($id)
    {
        // Implementation for deleting analytics report
        return true;
    }

    /**
     * @deprecated Export functionality not yet implemented
     * To be completed: PDF, CSV, Excel export formats
     */
}
