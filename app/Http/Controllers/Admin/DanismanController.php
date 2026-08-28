<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IlanDurumu;
use App\Models\User;
use App\Services\Danisman\DanismanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 🏢 DanismanController
 *
 * Thin controller managing Danışman (Advisor) web lifecycle.
 * All querying, filtering, statistics, and business rules are delegated to DanismanService.
 *
 * @SAB SEALED 🛡️ (Thin Controller Governance)
 */
class DanismanController extends AdminController
{
    public function __construct(
        private readonly DanismanService $danismanService
    ) {}

    /**
     * Display paginated danışman list.
     */
    public function index(Request $request): Response|View
    {
        $filters = [
            'search' => $request->get('search'),
            'aktiflik_durumu' => $request->get('aktiflik_durumu'),
            'online' => $request->get('online'),
            'sort' => $request->get('sort'),
        ];

        $danismanlar = $this->danismanService->getDanismanList($filters, 20);
        $danismanlar->appends($request->query());

        $istatistikler = $this->danismanService->getDanismanStats();
        $aktiflik_secenekleri = ['1' => IlanDurumu::YAYINDA->value, '0' => 'Pasif'];

        return $this->renderIfExists('admin.danisman.index', compact('danismanlar', 'istatistikler', 'filters', 'aktiflik_secenekleri'));
    }

    /**
     * Show danışman creation form.
     */
    public function create(): Response|View
    {
        return $this->renderIfExists('admin.danisman.create', []);
    }

    /**
     * Display danışman detail page with tabs, metrics, and portfolio.
     */
    public function show(Request $request, User $danisman): View
    {
        if (! $danisman->hasRole('danisman')) {
            abort(404, 'Bu kullanıcı bir danışman değil');
        }

        $detailData = $this->danismanService->getDanismanDetailData($danisman, $request);

        return view('admin.danisman.show', $detailData);
    }

    /**
     * Show danışman edit form.
     */
    public function edit(User $danisman): View
    {
        if (! $danisman->hasRole('danisman')) {
            abort(404, 'Bu kullanıcı bir danışman değil');
        }

        return view('admin.danisman.edit', compact('danisman'));
    }

    /**
     * Store a newly created danışman.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'nullable|string|max:20',
            'telefon' => 'nullable|string|max:20',
            'uzmanlik_alanlari' => 'nullable|array',
            'uzmanlik_alanlari.*' => 'string|max:100',
            'deneyim_yili' => 'nullable|integer|min:0|max:50',
            'title' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100|in:danisman,asistan,broker',
            'office_address' => 'nullable|string|max:500',
            'password' => 'required|string|min:8|confirmed',
            'lisans_no' => 'nullable|string|max:50',
            'aktiflik_durumu' => 'required|string|in:taslak,onay_bekliyor,aktif,satildi,kiralandi,pasif,arsivlendi,1,0',
            'aktiflik_notu' => 'nullable|string|max:50',
        ]);

        try {
            $this->danismanService->createDanisman($validated);

            return redirect()
                ->route('admin.danisman.index')
                ->with('success', 'Danışman başarıyla oluşturuldu.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Danışman oluşturulurken hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing danışman.
     */
    public function update(Request $request, User $danisman): RedirectResponse
    {
        if (! $danisman->hasRole('danisman')) {
            abort(404, 'Bu kullanıcı bir danışman değil');
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'ad' => 'required_without:name|string|max:100',
            'soyad' => 'required_without:name|string|max:100',
            'email' => 'required|email|unique:users,email,' . $danisman->id,
            'phone_number' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'title' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100|in:danisman,asistan,broker',
            'bio' => 'nullable|string',
            'lisans_no' => 'nullable|string|max:50',
            'uzmanlik_alanlari' => 'nullable|array',
            'uzmanlik_alanlari.*' => 'string|max:100',
            'deneyim_yili' => 'nullable|integer|min:0|max:50',
            'office_address' => 'nullable|string|max:500',
            'office_phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'expertise_summary' => 'nullable|string',
            'certificates_info' => 'nullable|string',
            'instagram_profile' => 'nullable|url|max:255',
            'linkedin_profile' => 'nullable|url|max:255',
            'facebook_profile' => 'nullable|url|max:255',
            'twitter_profile' => 'nullable|url|max:255',
            'youtube_channel' => 'nullable|url|max:255',
            'website' => 'nullable|url|max:255',
            'aktiflik_durumu' => 'nullable|string|in:taslak,onay_bekliyor,aktif,satildi,kiralandi,pasif,arsivlendi,1,0',
            'aktiflik_notu' => 'nullable|string|max:50',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        try {
            $this->danismanService->updateDanisman(
                $danisman,
                $validated,
                $request->file('profile_photo')
            );

            return redirect()
                ->route('admin.danisman.show', $danisman)
                ->with('success', 'Danışman bilgileri başarıyla güncellendi.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Danışman güncellenirken hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Delete a danışman.
     */
    public function destroy(User $danisman): RedirectResponse
    {
        if (! $danisman->hasRole('danisman')) {
            return redirect()
                ->route('admin.danisman.index')
                ->with('error', 'Bu kullanıcı bir danışman değil.');
        }

        $danismanAdi = $danisman->name;
        $this->danismanService->deleteDanisman($danisman);

        return redirect()
            ->route('admin.danisman.index')
            ->with('success', $danismanAdi . ' başarıyla silindi.');
    }

    /**
     * Toggle active/passive status.
     */
    public function toggleDurum(User $danisman): JsonResponse
    {
        if (! $danisman->hasRole('danisman')) {
            return response()->json(['success' => false, 'message' => 'Bu kullanıcı bir danışman değil'], 404);
        }

        $this->danismanService->toggleStatus($danisman);

        return response()->json([
            'success' => true,
            'aktiflik_durumu' => $danisman->aktiflik_durumu,
            'message' => $danisman->aktiflik_durumu ? 'Danışman aktif edildi' : 'Danışman pasif edildi',
        ]);
    }

    /**
     * Toggle active status (Context7 canonical alias).
     */
    public function toggleAktiflikDurumu(User $danisman): JsonResponse
    {
        return $this->toggleDurum($danisman);
    }

    /**
     * Touch online activity timestamp.
     */
    public function updateOnlineDurumu(User $danisman): JsonResponse
    {
        $this->danismanService->touchOnline($danisman);

        return response()->json(['success' => true, 'message' => 'Online durum güncellendi']);
    }

    public function search(Request $request): JsonResponse
    {
        return response()->json(['items' => []]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        return response()->json(['success' => true]);
    }

    public function performanceReport(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'report' => []]);
    }

    private function renderIfExists(string $view, array $data): Response|View
    {
        if (view()->exists($view)) {
            return response()->view($view, $data);
        }

        return response('Danışman sayfaları hazır değil', 200);
    }
}
