<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\IlanPriceHistory; // Added this line
use App\Services\Cache\ControllerCacheMutationService;
use App\Services\AI\YalihanCortex;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

/**
 * My Listings Controller
 *
 * Context7: İlanlarım - Kullanıcının kendi ilanlarını yönetir
 * ✅ REFACTORED: YalihanCortex merkezi AI sistemi kullanılıyor
 */
use App\Services\Ilan\IlanSearchService;
use App\Services\CRM\ChurnRiskService;
use App\Services\IlanService;

/**
 * My Listings Controller
 *
 * Context7: İlanlarım - Kullanıcının kendi ilanlarını yönetir
 * ✅ REFACTORED: YalihanCortex merkezi AI sistemi kullanılıyor
 * ✅ REFACTORED: Query Authority (P1) implemented
 */
class MyListingsController extends AdminController
{
    protected YalihanCortex $cortex;
    protected ChurnRiskService $churnService;
    protected IlanService $ilanService;
    protected IlanSearchService $searchService;

    public function __construct(
        YalihanCortex $cortex,
        ChurnRiskService $churnService,
        IlanService $ilanService,
        IlanSearchService $searchService
    ) {
        $this->cortex = $cortex;
        $this->churnService = $churnService;
        $this->ilanService = $ilanService;
        $this->searchService = $searchService;
    }
    /**
     * Display user's own listings (İlanlarım sayfası)
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Please login first');
        }

        // ── 1. QUERY AUTHORITY: Search listings via canonical service ──
        $listings = $this->searchService->searchMyListings($user->id, $request->all());

        // ── 2. LOGIC AUTHORITY: Attach Churn Risk (Batch calculation) ──
        $listings = $this->churnService->attachRiskToPaginator($listings);

        // ── 3. DATA AGGREGATION ──
        $stats = $this->ilanService->calculateDanismanIlanStats($user->id);

        $cacheKey = 'my_listings_categories_'.$user->id;
        $categories = Cache::remember($cacheKey, 3600, function () {
            return IlanKategori::select('id', 'name', 'icon')
                ->whereNotNull('parent_id')
                ->where('aktiflik_durumu', true)
                ->orderBy('name')
                ->get();
        });

        $aiAnalysis = null;
        if ($request->has('ai_analysis') && $request->ai_analysis) {
            $aiAnalysis = $this->cortex->analyzeMyListings($user->id);
        }

        return view('admin.ilanlar.ilanlarim', compact('listings', 'stats', 'categories', 'aiAnalysis', 'cacheKey'));
    }

    /**
     * Search listings via AJAX
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $user = Auth::user();

        // ── 1. QUERY AUTHORITY ──
        $listings = $this->searchService->searchMyListings($user->id, $request->all());

        // ── 2. LOGIC AUTHORITY ──
        $listings = $this->churnService->attachRiskToPaginator($listings);

        // ── 3. DATA TRANSFORMATION ──
        return response()->json([
            'success' => true,
            'data' => [
                'data' => $listings->getCollection()->map(fn($ilan) => [
                    'id'            => $ilan->id,
                    'baslik'        => $ilan->baslik,
                    'fiyat'         => $ilan->fiyat,
                    'para_birimi'   => $ilan->para_birimi,
                    'yayin_durumu'  => $ilan->yayin_durumu,
                    'goruntulenme'  => $ilan->goruntulenme,
                    'referans_no'   => $ilan->referans_no,
                    'alt_kategori'  => $ilan->altKategori,
                    'ana_kategori'  => $ilan->anaKategori,
                    'il'            => $ilan->il,
                    'ilce'          => $ilan->ilce,
                    'fotograflar'   => $ilan->fotograflar,
                    'created_at'    => $ilan->created_at?->toIso8601String(),
                    'updated_at'    => $ilan->updated_at?->toIso8601String(),
                    'churn_risk'    => $ilan->churn_risk,
                ])->values(),
                'current_page' => $listings->currentPage(),
                'last_page'    => $listings->lastPage(),
                'per_page'     => $listings->perPage(),
                'total'        => $listings->total(),
            ],
        ]);
    }

    /**
     * Bulk actions (delete, activate, deactivate)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate,draft',
            'ids' => 'required|array',
            'ids.*' => 'exists:ilanlar,id',
        ]);

        $user = Auth::user();
        $action = $request->action;
        $ids = $request->ids;

        // Verify all listings belong to current user
        $listings = $this->ilanService->getDanismanIlanlar($user->id)
            ->whereIn('id', $ids)
            ->get();

        if ($listings->count() !== count($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Some listings do not belong to you',
            ], 403);
        }

        // Perform action
        switch ($action) {
            case 'delete':
                $this->ilanService->bulkDeleteForDanisman($ids, $user->id);
                $message = count($ids).' listings deleted successfully';
                break;

            case 'activate':
                $this->ilanService->bulkChangeStatusForDanisman(
                    $ids,
                    $user->id,
                    IlanDurumu::YAYINDA->value
                ); // ✅ SAB: yayin_durumu + Enum
                $message = count($ids).' listings activated successfully';
                break;

            case 'deactivate':
                $this->ilanService->bulkChangeStatusForDanisman(
                    $ids,
                    $user->id,
                    IlanDurumu::PASIF->value
                ); // ✅ SAB: yayin_durumu + Enum
                $message = count($ids).' listings deactivated successfully';
                break;

            case 'draft':
                $this->ilanService->bulkChangeStatusForDanisman(
                    $ids,
                    $user->id,
                    IlanDurumu::TASLAK->value
                ); // ✅ SAB: yayin_durumu + Enum
                $message = count($ids).' listings moved to draft';
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action',
                ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Get statistics via AJAX
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $user = Auth::user();

        // Context7: Status değerleri düzeltildi (active→Aktif, vb.)
        $stats = $this->ilanService->getDanismanRawStats($user->id);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Export listings to Excel/PDF
     *
     * Context7 Standardı: C7-MYLISTINGS-EXPORT-2025-11-05
     *
     * GET /admin/my-listings/export?format=excel|pdf
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'nullable|in:excel,pdf',
        ]);

        $user = Auth::user();
        $format = $request->input('format', 'excel');

        // ── 1. QUERY AUTHORITY: Get all listings for export ──
        $listings = $this->searchService->getAllMyListingsForExport($user->id, $request->all());

        if ($format === 'pdf') {
            return $this->exportPdf($listings, $user);
        }

        return $this->exportExcel($listings, $user);
    }

    /**
     * Export to Excel
     */
    protected function exportExcel($listings, $user)
    {
        $data = [
            ['İlanlarım - Excel Raporu'],
            ['Danışman', $user->name],
            ['Email', $user->email],
            ['Tarih', now()->format('d.m.Y H:i')],
            ['Toplam İlan', $listings->count()],
            [''],
            [
                'ID', 'Referans No', 'Başlık', 'Kategori', 'İl', 'İlçe',
                'Fiyat', 'Para Birimi', 'Durum', 'Görüntülenme', 'Oluşturulma Tarihi'
            ],
        ];

        foreach ($listings as $listing) {
            $data[] = [
                $listing->id,
                $listing->referans_no ?? '-',
                $listing->baslik ?? 'Başlıksız',
                $listing->altKategori?->name ?? $listing->anaKategori?->name ?? '-',
                $listing->il?->il_adi ?? '-',
                $listing->ilce?->ilce_adi ?? '-',
                $listing->fiyat ?? 0,
                $listing->para_birimi ?? 'TL',
                $listing->yayin_durumu ?? IlanDurumu::YAYINDA->value,
                $listing->goruntulenme ?? 0,
                $listing->created_at?->format('d.m.Y H:i') ?? '-',
            ];
        }

        $dosyaAdi = 'Ilanlarim_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromArray
        {
            protected $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function array(): array
            {
                return $this->data;
            }
        }, $dosyaAdi);
    }

    /**
     * Export to PDF
     */
    protected function exportPdf($listings, $user)
    {
        $data = [
            'listings' => $listings,
            'user' => $user,
            'tarih' => now()->format('d.m.Y H:i'),
        ];

        $pdf = Pdf::loadView('admin.ilanlar.exports.my-listings-pdf', $data);

        $dosyaAdi = 'Ilanlarim_'.now()->format('Ymd_His').'.pdf';

        return $pdf->download($dosyaAdi);
    }

    /**
     * API: İlanlarım AI Analizi
     *
     * GET /api/admin/my-listings/ai-analysis
     * ✅ REFACTORED: YalihanCortex kullanılıyor
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function aiAnalysis(Request $request)
    {
        try {
            $user = Auth::user();
            $options = [
                'days' => $request->input('days', 30),
                'include_recommendations' => $request->input('include_recommendations', true),
            ];

            $result = $this->cortex->analyzeMyListings($user->id, $options);

            return response()->json([
                'success' => $result['success'] ?? false,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'AI analizi başarısız oldu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ NEW: Invalidate category cache when IlanKategori changes
     * Call this from IlanKategori model Observer or event listener
     *
     * @param int $userId
     * @return void
     */
    public static function invalidateCategoryCache(int $userId): void
    {
        app(ControllerCacheMutationService::class)->forget('my_listings_categories_'.$userId);
    }
}
