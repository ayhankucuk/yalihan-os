<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Actions\Admin\Dashboard\CreateDashboardWidgetAction;
use App\Actions\Admin\Dashboard\DeleteDashboardWidgetAction;
use App\Actions\Admin\Dashboard\UpdateDashboardWidgetAction;
use App\Enums\TalepDurumu;
use App\Http\Requests\Admin\DashboardWidgetRequest;
use App\Models\Ilan;
use App\Models\Talep;
use App\Models\User;
use App\Services\Cache\CacheHelper;
use App\Services\Logging\LogService;
use App\Services\Analytics\CortexAnalyticsService;
use App\Services\AI\AiWalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class DashboardController extends AdminController
{
    public function __construct(
        private readonly CortexAnalyticsService $analyticsService,
        private readonly AiWalletService $aiWalletService
    ) {}

    public function index()
    {
        try {
            $dashboardData = $this->getDashboardData();
            return view('admin.dashboard.index', $dashboardData);
        } catch (\Exception $e) {
            LogService::error('Dashboard error', [], $e);
            return view('admin.dashboard.index', [
                'quickStats' => $this->getEmptyStats(),
                'recentIlanlar' => [],
                'recentUsers' => [],
            ]);
        }
    }

    public function create()
    {
        $widgetTypes = [
            'stat' => 'İstatistik Widget',
            'chart' => 'Grafik Widget',
            'table' => 'Tablo Widget',
            'activity' => 'Aktivite Widget',
        ];
        $dataSources = [
            'ilanlar' => 'İlanlar',
            'musteriler' => 'Müşteriler',
            'talepler' => 'Talepler',
            'satislar' => 'Satışlar',
        ];
        return view('admin.dashboard.create', compact('widgetTypes', 'dataSources'));
    }

    public function store(
        DashboardWidgetRequest $request,
        CreateDashboardWidgetAction $action
    ): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $widget = $action->handle($validated, (int) Auth::id());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Widget created', 'data' => $widget], 201);
        }
        return redirect()->route('admin.dashboard.index')->with('success', 'Widget created');
    }

    public function update(
        DashboardWidgetRequest $request,
        int $id,
        UpdateDashboardWidgetAction $action
    ): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $widget = $action->handle($id, $validated, (int) Auth::id());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Widget updated', 'data' => $widget]);
        }
        return redirect()->route('admin.dashboard.index')->with('success', 'Widget updated');
    }

    public function destroy(
        int $id,
        DeleteDashboardWidgetAction $action
    ): RedirectResponse|JsonResponse
    {
        $action->handle($id, (int) Auth::id());

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Widget deleted']);
        }
        return redirect()->route('admin.dashboard.index')->with('success', 'Widget deleted');
    }

    public function getDashboardStats()
    {
        return response()->json(['success' => true, 'data' => $this->getDashboardData()]);
    }

    public function refresh()
    {
        return response()->json(['success' => true, 'message' => 'Refreshed', 'data' => $this->getDashboardData()]);
    }

    private function getDashboardData()
    {
        $recentIlanlar = Ilan::with([
            'ilanSahibi:id,ad,soyad',
            'il:id,il_adi',
            'ilce:id,ilce_adi',
            'kategori:id,name',
        ])
            ->latest()->limit(5)->get();

        $recentUsers = User::with(['roles:id,name'])->latest()->limit(5)->get();

        $start = now()->subDays(6)->startOfDay()->toDateString();
        $topViewed = $this->analyticsService->getTopViewed($start);

        // Fetch high-level KPIs from the CQRS Projection Service
        $projectionService = app(\App\Services\Analytics\ReadModels\DashboardProjectionService::class);
        $kpiSummary = $projectionService->getKpiSummary();

        return [
            'quickStats' => [
                'total_ilanlar' => $kpiSummary['active_listings'] ?? 0, // Migrated to CQRS Read-Model // context7-ignore
                'active_ilanlar' => $kpiSummary['active_listings'] ?? 0, // Migrated to CQRS Read-Model // context7-ignore
                'talep_toplam' => Talep::count(),
                'talep_aktif' => Talep::byDurum(TalepDurumu::AKTIF->value)->count(),
                'total_kullanicilar' => User::count(),
                'total_danismanlar' => User::whereHas('roles', fn($query) => $query->where('name', 'danisman'))->count(),
                'top_viewed' => $topViewed,
                'category_perf' => $this->analyticsService->getCategoryPerformance($start),
                'location_perf' => $this->analyticsService->getLocationPerformance($start),
                'advisor_perf' => $this->analyticsService->getAdvisorPerformance($start),
                'ai_balance' => $this->aiWalletService->getBalance(config('ai.defaults.tenant_id', 1)),
                'portfolio_value' => $kpiSummary['portfolio_value'] ?? 0, // Added from Projection
            ],
            'recentIlanlar' => $recentIlanlar,
            'recentUsers' => $recentUsers,
        ];
    }

    private function getEmptyStats()
    {
        return ['total_ilanlar' => 0, 'active_ilanlar' => 0, 'total_kullanicilar' => 0, 'total_danismanlar' => 0]; // context7-ignore
    }
}
