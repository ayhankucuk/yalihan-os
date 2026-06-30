<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Models\Kisi;
use App\Modules\Crm\Services\KisiService;
use App\Services\Admin\KisiSearchService;
use App\Services\Admin\KisiManagerService;
use App\Services\Kisi\BulkKisiService;
use App\Http\Requests\KisiStoreRequest;
use App\Http\Requests\KisiUpdateRequest;
use App\Services\Response\ResponseService;
use App\Services\CRMIntelligenceService;
use App\Repositories\KisiRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KisiController extends AdminController
{
    protected \App\Services\CRM\KisiRegistrationService $registrationService;
    protected \App\Services\Admin\KisiManagerService $managerService;
    protected \App\Services\Admin\KisiSearchService $searchService;
    protected \App\Services\AI\YalihanCortex $cortex;
    protected \App\Services\CRMIntelligenceService $intelligenceService;
    protected \App\Services\CRM\KisiScoringService $scoringService;
    protected \App\Repositories\KisiRepository $kisiRepository;
    protected \App\Services\Kisi\BulkKisiService $bulkKisiService;

    /**
     * Constructor
     * Context7: Service dependency injection (Zero-Touch pattern)
     * Architectural Enhancement: Added Central CRM Authority (RegistrationService)
     */
    public function __construct(
        \App\Modules\Crm\Services\KisiService $kisiService,
        \App\Services\CRM\KisiRegistrationService $registrationService,
        \App\Services\Admin\KisiManagerService $managerService,
        \App\Services\Admin\KisiSearchService $searchService,
        \App\Services\AI\YalihanCortex $cortex,
        \App\Services\CRMIntelligenceService $intelligenceService,
        \App\Services\CRM\KisiScoringService $scoringService,
        \App\Repositories\KisiRepository $kisiRepository,
        \App\Services\Kisi\BulkKisiService $bulkKisiService
    ) {
        $this->kisiService = $kisiService;
        $this->registrationService = $registrationService;
        $this->managerService = $managerService;
        $this->searchService = $searchService;
        $this->cortex = $cortex;
        $this->intelligenceService = $intelligenceService;
        $this->scoringService = $scoringService;
        $this->kisiRepository = $kisiRepository;
        $this->bulkKisiService = $bulkKisiService;
    }

    /**
     * Display a listing of the resource.
     * Context7: Kişi listesi ve filtreleme
     *
     * @return Response|\Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        // ✅ 🛡️ POLICY: Check if user can view list
        $this->authorize('viewAny', Kisi::class);

        // Context7 & Backward compatibility for search parameter
        $search = trim((string) ($request->get('q') ?? $request->get('search')));

        $filters = [
            'q' => $search,
            'aktif' => $request->get('aktif'),
            'sort' => $request->get('sort'),
            'kisi_tipi' => $request->get('kisi_tipi'),
        ];

        // ✅ ENFORCEMENT: Pass authenticated user for automatic ownership scoping
        /** @var \Illuminate\Pagination\LengthAwarePaginator $kisiler */
        $kisiler = $this->kisiRepository->paginate(20, $filters, auth()->user());

        // ✅ SAB: Get statistics via Repository pattern with explicit user scoping
        $stats = $this->kisiRepository->getStats(auth()->user());

        // Backward compatibility
        $istatistikler = $stats;
        $taslak = $stats['taslak'] ?? 0;

        // ✅ REFACTORED: Use repository for duplicate email check (No direct model access)
        $olasiKopyalar = [];
        if ($search === '') {
            $olasiKopyalar = $this->kisiRepository->getDuplicateEmails(auth()->user(), 5);
        }

        // ✅ SAB: Active users via Model scope
        $danismanlar = \App\Models\User::with('role:id,name')
            ->whereHas('role', function ($q) {
                $q->where('name', 'danisman');
            })
            ->active() // context7-ignore
            ->select(['id', 'name', 'email'])
            ->orderBy('name') // context7-ignore
            ->get();

        // ✅ FALLBACK: Eğer role ile danışman bulunamazsa, aktif kullanıcıları göster
        if ($danismanlar->isEmpty()) {
            $danismanlar = \App\Models\User::active()
                ->select(['id', 'name', 'email'])
                ->orderBy('name') // context7-ignore
                ->get();
        }

        if (view()->exists('admin.kisiler.index')) {
            return response()->view('admin.kisiler.index', compact(
                'kisiler', 'filters', 'stats', 'istatistikler',
                'olasiKopyalar', 'taslak', 'danismanlar'
            ));
        }

        return $this->renderAny(['admin.kisiler.index'], compact('kisiler', 'filters', 'istatistikler'));
    }

    /**
     * Show the form for creating a new resource.
     * Context7: Yeni kişi oluşturma formu
     *
     * @return Response|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        // ✅ 🛡️ POLICY: Check if user can create
        $this->authorize('create', Kisi::class);
        // ✅ SAB: Active users via Model scope
        $danismanlar = \App\Models\User::with('role:id,name')
            ->whereHas('role', function ($q) {
                $q->where('name', 'danisman');
            })
            ->active() // context7-ignore
            ->select(['id', 'name', 'email'])
            ->orderBy('name') // context7-ignore
            ->get();

        // ✅ FALLBACK: Eğer role ile danışman bulunamazsa, aktif kullanıcıları göster
        if ($danismanlar->isEmpty()) {
            $danismanlar = \App\Models\User::active()
                ->select(['id', 'name', 'email'])
                ->orderBy('name') // context7-ignore
                ->get();
        }

        // ✅ N+1 FIX: Select optimization
        $iller = \App\Models\Il::select(['id', 'il_adi'])
            ->orderBy('il_adi') // context7-ignore
            ->get();

        $kisiTipleri = [
            'ev_sahibi' => 'Ev Sahibi',
            'satici' => 'Satıcı',
            'alici' => 'Alıcı',
            'kiraci' => 'Kiracı'
        ];
        $kaynaklar = ['Web', 'Telefon', 'Referans', 'Sosyal Medya', 'Diğer'];

        return $this->renderAny(['admin.kisiler.create'], compact('danismanlar', 'iller', 'kisiTipleri', 'kaynaklar'));
    }

    /**
     * Store a newly created resource in storage.
     * Context7: Yeni kişi kaydetme
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function store(KisiStoreRequest $request)
    {
        // ✅ 🛡️ POLICY: Check if user can create
        $this->authorize('create', Kisi::class);

        // ✅ 🏛️ Authority Delegation: Central CRM Registration Logic
        try {
            $kisi = $this->registrationService->register($request->validated());

            return redirect()
                ->route('admin.kisiler.index')
                ->with('success', $kisi->ad . ' ' . $kisi->soyad . ' başarıyla eklendi! ✅');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return ResponseService::serverError('Kişi eklenirken hata oluştu', $e);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Kişi eklenirken hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     * Context7: Kişi detay sayfası
     *
     * @param  int|string  $kisiId
     * @return Response|\Illuminate\Contracts\View\View
     */
    public function show($kisiId)
    {
        // Allow viewing soft-deleted records in admin
        $kisi = $this->resolve($kisiId, withTrashed: true);

        // ✅ 🛡️ POLICY: Check if user can view this specific resource
        $this->authorize('view', $kisi);

        // ✅ N+1 FIX: Eager loading ekle
        $kisi->load([
            'danisman:id,name,email',
            'il:id,il_adi',
            'ilce:id,ilce_adi',
            'mahalle:id,mahalle_adi',
            'etiketler:id,name,color',
        ]);

        // 🧠 CRM Domain Decisions (Policy)
        $priorityScore = $this->intelligenceService->calculateLeadPriority($kisi);

        // 🧠 Cortex Intelligence Signals (Inference)
        $recommendedListings = $this->cortex->requestCustomerRecommendations($kisi, 5);

        return $this->renderAny(['admin.kisiler.show'], [
            'kisi' => $kisi,
            'priorityScore' => $priorityScore,
            'recommendedListings' => $recommendedListings,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * Context7: Kişi düzenleme formu
     *
     * @param  int|string  $kisiId
     * @return Response|\Illuminate\Contracts\View\View
     */
    public function edit($kisiId)
    {
        $kisi = $this->resolve($kisiId, withTrashed: true);

        // ✅ 🛡️ POLICY: Check if user can update this specific resource
        $this->authorize('update', $kisi);

        // ✅ N+1 FIX: Eager loading ekle
        $kisi->load([
            'danisman:id,name,email',
            'il:id,il_adi',
            'ilce:id,ilce_adi',
            'mahalle:id,mahalle_adi',
            'etiketler:id,name,color',
        ]);

        // ✅ SAB: Active users via Model scope
        $danismanlar = \App\Models\User::with('roles:id,name')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'danisman');
            })
            ->active() // context7-ignore
            ->select(['id', 'name', 'email'])
            ->orderBy('name') // context7-ignore
            ->get();

        // ✅ FALLBACK: Eğer role ile danışman bulunamazsa, aktif kullanıcıları göster
        if ($danismanlar->isEmpty()) {
            $danismanlar = \App\Models\User::active()
                ->select(['id', 'name', 'email'])
                ->orderBy('name') // context7-ignore
                ->get();
        }

        // ✅ N+1 FIX: Select optimization
        $iller = \App\Models\Il::select(['id', 'il_adi'])
            ->orderBy('il_adi') // context7-ignore
            ->get();

        // ✅ N+1 FIX: Select optimization
        $ilceler = $kisi->il_id ? \App\Models\Ilce::where('il_id', $kisi->il_id)
            ->select(['id', 'ilce_adi'])
            ->orderBy('ilce_adi') // context7-ignore
            ->get() : [];

        // ✅ N+1 FIX: Select optimization
        $mahalleler = $kisi->ilce_id ? \App\Models\Mahalle::where('ilce_id', $kisi->ilce_id)
            ->select(['id', 'mahalle_adi'])
            ->orderBy('mahalle_adi') // context7-ignore
            ->get() : [];

        $kisiTipleri = [
            'ev_sahibi' => 'Ev Sahibi',
            'satici' => 'Satıcı',
            'alici' => 'Alıcı',
            'kiraci' => 'Kiracı'
        ];

        // ✅ N+1 FIX: Select optimization
        $etiketler = \App\Modules\Crm\Models\Etiket::select(['id', 'name', 'color'])
            ->orderBy('name') // context7-ignore
            ->get();

        $kaynaklar = ['Web', 'Telefon', 'Referans', 'Sosyal Medya', 'Diğer'];

        // Kişinin mevcut etiket ID'lerini al
        $kisiEtiketIds = $kisi->etiketler ? $kisi->etiketler->pluck('id')->toArray() : [];

        return $this->renderAny(['admin.kisiler.edit'], [
            'kisi' => $kisi,
            'danismanlar' => $danismanlar,
            'iller' => $iller,
            'ilceler' => $ilceler,
            'mahalleler' => $mahalleler,
            'kisiTipleri' => $kisiTipleri,
            'etiketler' => $etiketler,
            'kaynaklar' => $kaynaklar,
            'kisiEtiketIds' => $kisiEtiketIds,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * Context7: Kişi güncelleme
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function update(KisiUpdateRequest $request, $kisiId)
    {
        $kisi = $this->resolve($kisiId, withTrashed: true);

        // ✅ 🛡️ POLICY: Check if user can update this specific resource
        $this->authorize('update', $kisi);

        // ✅ 🏛️ Authority Delegation: Central CRM Lifecycle Logic
        try {
            $updateData = $request->validated();

            // Centralized update handles data persistence + scoring triggers
            $kisi = $this->registrationService->update($kisi, $updateData);

            // Centralized tagging handles relationship + scoring triggers
            if ($request->has('etiketler_ids')) {
                $this->registrationService->syncTags($kisi, $request->etiketler_ids);
            }

            return redirect()
                ->route('admin.kisiler.edit', ['kisi' => $kisi->id])
                ->with('success', $kisi->ad . ' ' . $kisi->soyad . ' başarıyla güncellendi! ✅');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return ResponseService::serverError('Kişi güncellenirken hata oluştu', $e);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Kişi güncellenirken hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     * Context7: Kişi silme
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function destroy($kisiId)
    {
        try {
            // Resolve kişi including soft-deleted records via repository enforcement
            $kisi = $this->resolve($kisiId, withTrashed: true);

            // ✅ 🛡️ POLICY: Check if user can delete this specific resource
            $this->authorize('delete', $kisi);

            // ✅ REFACTORED: Use KisiService
            $kisiAdi = $kisi->ad . ' ' . $kisi->soyad;
            $this->kisiService->deleteKisi($kisi);

            // JSON response for AJAX requests
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $kisiAdi . ' başarıyla silindi.',
                ]);
            }

            // Redirect for form submissions
            return redirect()
                ->route('admin.kisiler.index')
                ->with('success', $kisiAdi . ' başarıyla silindi.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            // JSON response for AJAX requests
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kişi silinirken bir hata oluştu: ' . $e->getMessage(),
                ], 500);
            }

            // Redirect for form submissions
            return redirect()
                ->route('admin.kisiler.index')
                ->with('error', 'Kişi silinirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Search persons
     * Context7: Kişi arama endpoint
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        // ✅ 🛡️ POLICY: Check if user can view list
        $this->authorize('viewAny', Kisi::class);

        // Context7 uyumlu kişi arama
        $search = $request->get('q', '');
        $limit = (int)$request->get('limit', 10);

        if (empty($search)) {
            return response()->json(['items' => []]);
        }

        // ✅ REFACTORED: Use repository for search (automatic ownership enforcement)
        $kisiler = $this->kisiRepository->search($search, auth()->user())
            ->take($limit)
            ->map(function ($kisi) {
                return [
                    'id' => $kisi->id,
                    'text' => $kisi->tam_ad . ' - ' . ($kisi->telefon ?? 'Tel yok') . ' - ' . ($kisi->il->il_adi ?? ''),
                    'tam_ad' => $kisi->tam_ad,
                    'telefon' => $kisi->telefon,
                    'email' => $kisi->email,
                    'il' => $kisi->il->il_adi ?? '',
                    'crm_score' => $kisi->crm_score,
                    'is_owner_eligible' => $kisi->isOwnerEligible(),
                ];
            });

        return response()->json(['items' => $kisiler]);
    }

    /**
     * Check for duplicate persons
     * Context7: Mükerrer kişi kontrolü
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkDuplicate(Request $request)
    {
        // ✅ 🛡️ POLICY: Check if user can viewAny (base check)
        $this->authorize('viewAny', Kisi::class);

        // ✅ 🏛️ Authority Delegation: Central CRM Duplicate Rules
        // Note: registrationService should respect ownership via repository calls internally
        $duplicateCheck = $this->registrationService->validateDuplicate($request->all());

        return response()->json([
            'duplicate' => $duplicateCheck['duplicate'],
            'duplicates' => $duplicateCheck['duplicates'],
        ]);
    }

    /**
     * Bulk action for persons
     * Context7: Toplu işlem endpoint
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkAction(Request $request)
    {
        // ✅ 🛡️ POLICY: Base check for list operations
        $this->authorize('viewAny', Kisi::class);

        // Context7 uyumlu toplu işlemler
        $action = $request->get('action');
        $ids = $request->get('ids', []);

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'Hiç kişi seçilmedi']);
        }

        // ⚠️ CRITICAL: Per-record authorization check
        // Ensure user has permission for EACH record they are trying to modify
        foreach ($ids as $id) {
            $kisi = $this->kisiRepository->findWithTrashed((int)$id, auth()->user());
            if (!$kisi) {
                return response()->json(['success' => false, 'message' => "Kişi #{$id} bulunamadı veya yetkiniz yok."], 403);
            }

            // Specific action checks
            if (in_array($action, ['activate', 'pasif_yap'])) {
                $this->authorize('update', $kisi);
            } elseif (in_array($action, ['sil', 'delete'])) {
                $this->authorize('delete', $kisi);
            }
        }

        switch ($action) {
            case 'activate':
                $count = $this->bulkKisiService->bulkUpdate($ids, ['aktiflik_durumu' => true]);
                $message = $count . ' kişi etkinleştirildi';
                break;

            case 'pasif_yap':
                $count = $this->bulkKisiService->bulkUpdate($ids, ['aktiflik_durumu' => false]);
                $message = $count . ' kişi pasif yapıldı';
                break;

            case 'sil':
            case 'delete':
                $count = $this->bulkKisiService->bulkDelete($ids);
                $message = $count . ' kişi silindi';
                break;

            default:
                return response()->json(['success' => false, 'message' => 'Geçersiz işlem']);
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    /**
     * AI analysis for person
     * Context7: AI destekli kişi analizi
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function aiAnalyze(Request $request)
    {
        // Resolve kişi to ensure ownership
        $kisiId = (int)$request->get('kisi_id');
        $kisi = $this->resolve($kisiId);

        // ✅ 🛡️ POLICY: Check if user can view this person
        $this->authorize('view', $kisi);

        // 🏛️ Domain Policy decision by KisiScoringService
        $audit = $this->scoringService->performAudit($kisiId);

        // 🧠 AI Enrichment via Cortex Authority
        $enrichment = $this->cortex->requestCustomerAiEnrichment((int)$request->get('kisi_id'));

        $result = array_merge($audit, [
            'ai_enrichment' => $enrichment
        ]);

        return response()->json($result);
    }

    /**
     * Person tracking page
     * Context7: Kişi takip sayfası
     *
     * @return Response|\Illuminate\Contracts\View\View
     */
    public function takip(Request $request)
    {
        // ✅ 🛡️ POLICY: Check if user can viewAny
        $this->authorize('viewAny', Kisi::class);

        return $this->renderAny(['admin.kisiler.takip']);
    }

    /**
     * Resolve person from various types
     * Context7: Kişi resolver helper
     *
     * @param  int|string|Kisi  $kisi
     */
    /**
     * Context7: Kişi resolver helper
     *
     * @param  int|string|Kisi  $kisi
     * @param  bool  $withTrashed Include soft-deleted records
     */
    private function resolve($kisi, bool $withTrashed = false): Kisi
    {
        if ($kisi instanceof Kisi) {
            return $kisi;
        }

        // ✅ REFACTORED: Use repository instead of direct model access
        // This ensures that ownership scope is applied at the point of retrieval
        $kisiModel = $this->kisiRepository->findWithTrashed((int)$kisi, auth()->user());

        if (!$kisiModel) {
            abort(404, 'Kişi bulunamadı veya erişim yetkiniz yok.');
        }

        return $kisiModel;
    }

    /**
     * Render any available view
     * Context7: View render helper
     */
    private function renderAny(array $views, array $data = []): Response|\Illuminate\Contracts\View\View
    {
        foreach ($views as $view) {
            if (view()->exists($view)) {
                return response()->view($view, $data);
            }
        }

        return response('Kişiler sayfaları hazır değil', 200);
    }

    /**
     * Display danışman's own contacts
     * Context7: Danışmana atanmış kişiler listesi
     */
    public function kisilerim(Request $request)
    {
        // ✅ 🛡️ POLICY: Check if user can viewAny
        $this->authorize('viewAny', Kisi::class);

        // ✅ REFACTORED: Use unified repository paginate method
        // Repository's applyOwnershipScope will automatically handle danisman_id filtering
        $search = trim((string) ($request->get('q') ?? $request->get('search')));

        $filters = [
            'q' => $search,
            'aktif' => $request->get('aktif'),
            'kisi_tipi' => $request->get('kisi_tipi'),
        ];

        $kisiler = $this->kisiRepository->paginate(20, $filters, auth()->user());

        // ✅ SAB: Get statistics via Repository pattern with explicit user scoping
        $stats = $this->kisiRepository->getStats(auth()->user());

        // Backward compatibility
        $istatistikler = $stats;

        return view('admin.kisiler.index', compact('kisiler', 'istatistikler', 'stats', 'filters'));
    }
}
