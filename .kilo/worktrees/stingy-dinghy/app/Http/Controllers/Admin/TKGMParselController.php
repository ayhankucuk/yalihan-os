<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-catch
 */

use App\Services\Integrations\TKGMService;
use App\Services\Integrations\TkgmBulkQueryService;
use App\Services\Cache\ControllerCacheMutationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * TKGM Parsel Sorgulama Controller
 * Tapu Kadastro Genel Müdürlüğü parsel sorgulama uygulaması
 * Context7 Standard: C7-TKGM-PARSEL-2025-10-17
 * @sab-ignore-thin
 */
class TKGMParselController extends AdminController
{
    protected $tkgmService;
    protected TkgmBulkQueryService $bulkQueryService;
    protected ControllerCacheMutationService $cacheMutationService;

    public function __construct(
        TKGMService $tkgmService,
        TkgmBulkQueryService $bulkQueryService,
        ControllerCacheMutationService $cacheMutationService
    )
    {
        $this->tkgmService = $tkgmService;
        $this->bulkQueryService = $bulkQueryService;
        $this->cacheMutationService = $cacheMutationService;
    }

    /**
     * TKGM Parsel Sorgulama Ana Sayfası
     * GET /admin/tkgm-parsel
     */
    public function index()
    {
        $recentQueries = Cache::get('tkgm_recent_queries_'.auth()->id(), []);

        return view('admin.tkgm-parsel.index', [
            'recentQueries' => $recentQueries,
            'pageTitle' => 'TKGM Parsel Sorgulama',
            'breadcrumbs' => [
                ['name' => 'Admin Panel', 'url' => route('admin.dashboard.index')],
                ['name' => 'TKGM Parsel Sorgulama', 'active' => true], // context7-ignore
            ],
        ]);
    }

    /**
     * Parsel Sorgulama API
     * POST /admin/api/tkgm-parsel/query
     */
    public function query(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ada' => 'required|string|max:20',
            'parsel' => 'required|string|max:20',
            'il' => 'required|string|max:50',
            'ilce' => 'required|string|max:50',
            'mahalle' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parsel bilgileri eksik veya hatalı',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // TKGM servisini çağır
            $result = $this->tkgmService->parselSorgula(
                $request->ada,
                $request->parsel,
                $request->il,
                $request->ilce,
                $request->mahalle
            );

            // Başarılı sorguları cache'e kaydet
            if ($result['success']) {
                $this->saveRecentQuery($request->all(), $result);
            }

            // Log kaydı
            Log::info('TKGM parsel sorgulaması', [
                'user_id' => auth()->id(),
                'ada' => $request->ada,
                'parsel' => $request->parsel,
                'il' => $request->il,
                'ilce' => $request->ilce,
                'success' => $result['success'],
                'response_time' => $result['response_time'] ?? null,
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('TKGM parsel sorgulama hatası', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Parsel sorgulaması sırasında bir hata oluştu',
                'error_code' => 'QUERY_ERROR',
            ], 500);
        }
    }

    /**
     * Toplu Parsel Sorgulama
     * POST /admin/api/tkgm-parsel/bulk-query
     */
    public function bulkQuery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'queries' => 'required|array|min:1|max:50',
            'queries.*.ada' => 'required|string|max:20',
            'queries.*.parsel' => 'required|string|max:20',
            'queries.*.il' => 'required|string|max:50',
            'queries.*.ilce' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Toplu sorgu verileri hatalı',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->bulkQueryService->bulkQuery($request->input('queries'));

            // Log kaydı
            Log::info('TKGM toplu parsel sorgulaması', [
                'user_id' => auth()->id(),
                'count' => count($request->input('queries')),
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('TKGM toplu sorgulama hatası', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Toplu sorgulama sırasında bir hata oluştu',
            ], 500);
        }
    }

    /**
     * TKGM Sorgulama Geçmişi
     * GET /admin/api/tkgm-parsel/history
     */
    public function history(Request $request)
    {
        try {
            $userId = auth()->id();
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);

            // Cache'den geçmiş sorguları al
            $allQueries = Cache::get('tkgm_all_queries_'.$userId, []);

            // Sayfalama
            $total = count($allQueries);
            $offset = ($page - 1) * $perPage;
            $queries = array_slice($allQueries, $offset, $perPage);

            return response()->json([
                'success' => true,
                'data' => $queries,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Geçmiş sorguları alırken hata oluştu',
            ], 500);
        }
    }

    /**
     * TKGM Sorgulama İstatistikleri
     * GET /admin/api/tkgm-parsel/stats
     */
    public function stats()
    {
        try {
            $userId = auth()->id();
            $recentQueries = Cache::get('tkgm_recent_queries_'.$userId, []);
            $allQueries = Cache::get('tkgm_all_queries_'.$userId, []);

            $stats = [
                'total_queries' => count($allQueries),
                'recent_queries' => count($recentQueries),
                'success_rate' => $this->calculateSuccessRate($allQueries),
                'most_queried_locations' => $this->getMostQueriedLocations($allQueries),
                'daily_stats' => $this->getDailyStats($allQueries),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'İstatistikler alınırken hata oluştu',
            ], 500);
        }
    }

    /**
     * Son sorguları cache'e kaydet
     */
    private function saveRecentQuery($queryData, $result)
    {
        $userId = auth()->id();
        $cacheKey = 'tkgm_recent_queries_'.$userId;
        $allQueriesKey = 'tkgm_all_queries_'.$userId;

        $queryRecord = [
            'id' => uniqid(),
            'ada' => $queryData['ada'],
            'parsel' => $queryData['parsel'],
            'il' => $queryData['il'],
            'ilce' => $queryData['ilce'],
            'success' => $result['success'],
            'timestamp' => now()->toISOString(),
            'response_time' => $result['response_time'] ?? null,
        ];

        // Son 10 sorguyu cache'te tut
        $recentQueries = Cache::get($cacheKey, []);
        array_unshift($recentQueries, $queryRecord);
        $recentQueries = array_slice($recentQueries, 0, 10);
        $this->cacheMutationService->put($cacheKey, $recentQueries, 3600); // 1 saat

        // Tüm sorguları kaydet (son 100)
        $allQueries = Cache::get($allQueriesKey, []);
        array_unshift($allQueries, $queryRecord);
        $allQueries = array_slice($allQueries, 0, 100);
        $this->cacheMutationService->put($allQueriesKey, $allQueries, 86400); // 24 saat
    }

    /**
     * Başarı oranını hesapla
     */
    private function calculateSuccessRate($queries)
    {
        if (empty($queries)) {
            return 0;
        }

        $successCount = array_reduce($queries, function ($carry, $query) {
            return $carry + ($query['success'] ? 1 : 0);
        }, 0);

        return round(($successCount / count($queries)) * 100, 1);
    }

    /**
     * En çok sorgulanan lokasyonları bul
     */
    private function getMostQueriedLocations($queries)
    {
        $locations = [];

        foreach ($queries as $query) {
            $key = $query['il'].' / '.$query['ilce'];
            $locations[$key] = ($locations[$key] ?? 0) + 1;
        }

        arsort($locations);

        return array_slice($locations, 0, 5, true);
    }

    /**
     * Günlük istatistikleri hesapla
     */
    private function getDailyStats($queries)
    {
        $dailyStats = [];
        $today = now()->format('Y-m-d');

        foreach ($queries as $query) {
            $date = \Carbon\Carbon::parse($query['timestamp'])->format('Y-m-d');

            if (! isset($dailyStats[$date])) {
                $dailyStats[$date] = ['total' => 0, 'success' => 0];
            }

            $dailyStats[$date]['total']++;
            if ($query['success']) {
                $dailyStats[$date]['success']++;
            }
        }

        // Son 7 günü döndür
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $last7Days[$date] = $dailyStats[$date] ?? ['total' => 0, 'success' => 0];
        }

        return $last7Days;
    }
}
