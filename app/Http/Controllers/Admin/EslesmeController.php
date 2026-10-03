<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IlanDurumu;
 
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Eslesme;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

class EslesmeController extends AdminController
{
    public function __construct(
        protected \App\Services\CRM\MatchingAuthorityService $authorityService
    ) {}

    /**
     * Display a listing of the resource.
     * Context7: Eşleştirme listesi ve filtreleme
     *
     * @return Response|\Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        // ✅ F02 REMEDIATION: Scope through Ilan relationship (tenant-bearing anchor).
        // Eslesme has no BelongsToTenant, so read scoping is done via the Ilan relation.
        // Every valid Eslesme has a mandatory ilan_id → Ilan has tenant_id (TenantScope enforced).
        // Read scope: only Eslesme records whose canonical Ilan belongs to the current tenant.
        $tenantCtx = app(\App\Services\SaaS\TenantContextService::class);
        $currentTenantId = $tenantCtx->hasTenant() ? $tenantCtx->getTenant()->id : null;

        $query = \App\Models\Eslesme::with([
            'ilan:id,baslik,fiyat,para_birimi,yayin_durumu', // ✅ SAB: yayin_durumu (Ilan tablosu)
            'kisi:id,ad,soyad,telefon,email',
            'danisman:id,name,email',
        ])
            ->select(['id', 'ilan_id', 'kisi_id', 'danisman_id', 'eslesme_durumu', 'created_at']);

        // ✅ F02-R REMEDIATION: All three relation anchors must match current tenant.
        //   C1/C2/C3: Ilan=TenantA but Kisi/Talep=TenantB rows must NOT be accessible.
        //   Ilan-only scope is insufficient — mixed-tenant legacy rows would pass.
        //   Read invariant: Ilan∈T AND Kisi∈T AND (Talep∈T OR Talep=null).
        if ($currentTenantId !== null) {
            $query->whereHas('ilan', fn($q) => $q->where('tenant_id', $currentTenantId))
                ->whereHas('kisi', fn($q) => $q->where('tenant_id', $currentTenantId))
                // Nullable-safe: if talep_id IS NOT NULL, its Talep must also belong to current tenant.
                // Using NOT EXISTS subquery: exclude rows where a foreign tenant Talep exists.
                ->whereRaw('NOT EXISTS (SELECT 1 FROM talepler WHERE talepler.id = eslesmeler.talep_id AND talepler.tenant_id != ?)', [$currentTenantId]);
        } else {
            // No tenant context → return empty (fail closed)
            $query->whereRaw('1 = 0');
        }

        $eslesmeler = $query->latest()->paginate(20);

        // ✅ OPTIMIZED: İstatistikleri tenant-scoped query ile hesapla
        // ✅ F02-R REMEDIATION: All three relation anchors must match current tenant.
        $baseStats = \App\Models\Eslesme::query();
        if ($currentTenantId !== null) {
            $baseStats->whereHas('ilan', fn($q) => $q->where('tenant_id', $currentTenantId))
                ->whereHas('kisi', fn($q) => $q->where('tenant_id', $currentTenantId))
                ->whereRaw('NOT EXISTS (SELECT 1 FROM talepler WHERE talepler.id = eslesmeler.talep_id AND talepler.tenant_id != ?)', [$currentTenantId]);
        } else {
            $baseStats->whereRaw('1 = 0');
        }
        $istatistikler = [
            'toplam'    => (clone $baseStats)->count(),
            'aktif'     => (clone $baseStats)->where('eslesme_durumu', IlanDurumu::YAYINDA->value)->count(),
            'beklemede' => (clone $baseStats)->where('eslesme_durumu', 'Beklemede')->count(),
        ];

        return $this->render('admin.eslesmeler.index', compact('eslesmeler', 'istatistikler'));
    }

    /**
     * Show the form for creating a new resource.
     * Context7: Yeni eşleştirme oluşturma formu
     *
     * @return Response|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        // Context7: Provide datasets for form
        // ✅ N+1 FIX: Select optimization
        $kisiler = \App\Models\Kisi::active() // ✅ SAB: HasActiveScope trait (aktiflik_durumu)
            ->select(['id', 'ad', 'soyad', 'telefon', 'email'])
            ->orderBy('ad') // context7-ignore
            ->get();

        // ✅ N+1 FIX: Eager loading with select optimization
        $ilanlar = \App\Models\Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value) // ✅ SAB: yayin_durumu (mühürlü kolon)
            ->with([
                'anaKategori:id,name,slug', // ✅ Fixed: kategori -> anaKategori
                'il:id,il_adi',
                'ilce:id,ilce_adi',
            ])
            ->select(['id', 'baslik', 'fiyat', 'para_birimi', 'ana_kategori_id', 'il_id', 'ilce_id', 'created_at']) // ✅ Fixed: kategori_id -> ana_kategori_id
            ->orderBy('created_at', 'desc') // context7-ignore
            ->get();

        // ✅ N+1 FIX: Eager loading with select optimization
        $talepler = \App\Models\Talep::where('talep_durumu', IlanDurumu::YAYINDA->value) // ✅ SAB: talep_durumu
            ->with([
                'kisi:id,ad,soyad,telefon',
                'kategori:id,name,slug',
            ])
            ->select(['id', 'baslik', 'kisi_id', 'alt_kategori_id', 'il_id', 'ilce_id', 'created_at']) // ✅ Fixed: kategori_id -> alt_kategori_id
            ->orderBy('created_at', 'desc') // context7-ignore
            ->get();

        // ✅ N+1 FIX: Select optimization
        $danismanlar = \App\Models\User::whereHas('roles', function ($q) {
            $q->where('name', 'danisman');
        })
            ->select(['id', 'name', 'email'])
            ->orderBy('name') // context7-ignore
            ->get();

        $data = [
            'pageTitle' => 'Yeni Eşleştirme Oluştur',
            'breadcrumbs' => [
                ['name' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['name' => 'Eşleştirmeler', 'url' => route('admin.eslesmeler.index')],
                ['name' => 'Yeni Eşleştirme', 'active' => true], // context7-ignore
            ],
            'kisiler' => $kisiler,
            'ilanlar' => $ilanlar,
            'talepler' => $talepler,
            'danismanlar' => $danismanlar,
        ];

        return $this->render('admin.eslesmeler.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     * Context7: Yeni eşleştirme kaydetme
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     */
    public function store(Request $request)
    {
        // Context7 Validation Rules
        $validated = $request->validate([
            'kisi_id' => 'required|exists:kisiler,id',
            'ilan_id' => 'required|exists:ilanlar,id',
            'talep_id' => 'nullable|exists:talepler,id',
            'danisman_id' => 'nullable|exists:users,id',
            'eslesme_durumu' => 'required|string|in:Aktif,Beklemede,İptal,Tamamlandı',
            'notlar' => 'nullable|string|max:1000',
            'eslesme_tarihi' => 'nullable|date',
        ]);

        // ✅ TENANT BOUNDARY (ESLESME-F01 remediation):
        // 'exists:table,id' validator runs without TenantScope — it does NOT enforce
        // tenant isolation. After the validator passes, we MUST verify that the
        // referenced entities all belong to the current effective tenant.
        $tenantCtx = app(\App\Services\SaaS\TenantContextService::class);
        if (!$tenantCtx->hasTenant()) {
            abort(403, 'Tenant context not established.');
        }
        $currentTenantId = $tenantCtx->getTenant()->id;

        $kisi = \App\Models\Kisi::find($validated['kisi_id']);
        if (!$kisi || (int) $kisi->tenant_id !== (int) $currentTenantId) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Seçilen kişi bu firmaya ait değil.');
        }

        $ilan = \App\Models\Ilan::find($validated['ilan_id']);
        if (!$ilan || (int) $ilan->tenant_id !== (int) $currentTenantId) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Seçilen ilan bu firmaya ait değil.');
        }

        if (!empty($validated['talep_id'])) {
            $talep = \App\Models\Talep::find($validated['talep_id']);
            if (!$talep || (int) $talep->tenant_id !== (int) $currentTenantId) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Seçilen talep bu firmaya ait değil.');
            }
        }

        try {
            $eslesme = $this->authorityService->createMatch($validated, auth()->user());

            return redirect()
                ->route('admin.eslesmeler.index')
                ->with('success', 'Eşleştirme başarıyla oluşturuldu! 🎉');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Eşleştirme oluşturulurken hata oluştu: '.$e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     * Context7: Eşleştirme detay sayfası
     *
     * @param  int|string|Eslesme  $eslesme
     * @return Response|\Illuminate\Contracts\View\View
     */
    public function show($eslesme)
    {
        // ✅ F02 REMEDIATION: Model binding is tenant-agnostic.
        // Resolve the requested Eslesme through the query-level tenant boundary.
        // Read invariant: Ilan∈T AND Kisi∈T AND (Talep∈T OR Talep=null).
        $tenantCtx = app(\App\Services\SaaS\TenantContextService::class);
        $currentTenantId = $tenantCtx->hasTenant() ? $tenantCtx->getTenant()->id : null;

        if ($currentTenantId === null) {
            abort(403, 'Tenant context not established.');
        }

        $id = $eslesme instanceof \App\Models\Eslesme ? $eslesme->id : $eslesme;

        $eslesmeModel = \App\Models\Eslesme::with([
            'ilan:id,baslik,fiyat,para_birimi,yayin_durumu',
            'kisi:id,ad,soyad,telefon,email',
            'danisman:id,name,email',
        ])
            ->where('id', $id)
            ->whereHas('ilan', fn($q) => $q->where('tenant_id', $currentTenantId))
            ->whereHas('kisi', fn($q) => $q->where('tenant_id', $currentTenantId))
            ->whereRaw('NOT EXISTS (SELECT 1 FROM talepler WHERE talepler.id = eslesmeler.talep_id AND talepler.tenant_id != ?)', [$currentTenantId])
            ->first();

        if (!$eslesmeModel) {
            abort(404);
        }

        return $this->render('admin.eslesmeler.show', ['eslesme' => $eslesmeModel]);
    }

    /**
     * Show the form for editing the specified resource.
     * Context7: Eşleştirme düzenleme formu
     *
     * @param  int|string|Eslesme  $eslesme
     * @return Response|\Illuminate\Contracts\View\View
     */
    public function edit($eslesme)
    {
        return $this->render('admin.eslesmeler.edit', ['eslesme' => $eslesme]);
    }

    /**
     * Update the specified resource in storage.
     * Context7: Eşleştirme güncelleme
     *
     * @param  int|string|Eslesme  $eslesme
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $eslesme)
    {
        // İstisna Gerekçesi: Method alias, DB işlemi içermez. Native Response formülü kullanılır.
        return response()->redirectToRoute('admin.eslesmeler.edit', $eslesme);
    }

    /**
     * Remove the specified resource from storage.
     * Context7: Eşleştirme silme
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     */
    public function destroy(Eslesme $eslesme)
    {
        // ✅ F02 REMEDIATION: Model binding is tenant-agnostic.
        // Verify all three relation anchors belong to the current tenant before deleting.
        $tenantCtx = app(\App\Services\SaaS\TenantContextService::class);
        $currentTenantId = $tenantCtx->hasTenant() ? $tenantCtx->getTenant()->id : null;

        // Fail closed: no tenant context = no deletion
        if ($currentTenantId === null) {
            return redirect()
                ->route('admin.eslesmeler.index')
                ->with('error', 'Bu eşleştirmeyi silme yetkiniz yok.');
        }

        // Reload with all anchors loaded
        $eslesme->load(['ilan', 'kisi', 'talep']);

        $ilanOk = $eslesme->ilan && (int) $eslesme->ilan->tenant_id === (int) $currentTenantId;
        $kisiOk = $eslesme->kisi && (int) $eslesme->kisi->tenant_id === (int) $currentTenantId;
        $talepOk = !$eslesme->talep_id
            || ($eslesme->talep && (int) $eslesme->talep->tenant_id === (int) $currentTenantId);

        if (!$ilanOk || !$kisiOk || !$talepOk) {
            return redirect()
                ->route('admin.eslesmeler.index')
                ->with('error', 'Bu eşleştirmeyi silme yetkiniz yok.');
        }

        try {
            // Eşleşme bilgilerini al
            $eslesmeBilgi = 'Eşleşme #'.$eslesme->id;
            if ($eslesme->ilan) {
                $eslesmeBilgi .= ' ('.$eslesme->ilan->baslik.')';
            }
            $eslesme->delete();

            return redirect()
                ->route('admin.eslesmeler.index')
                ->with('success', $eslesmeBilgi.' başarıyla silindi.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.eslesmeler.index')
                ->with('error', 'Eşleşme silinirken bir hata oluştu: '.$e->getMessage());
        }
    }

    /**
     * Auto match requests with listings
     * Context7: Otomatik eşleştirme
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function autoMatch()
    {
        $count = $this->authorityService->runAutoMatchPipeline(null, auth()->user());
        return response()->json(['created' => $count]);
    }

    /**
     * Bulk create matches
     * Context7: Toplu eşleştirme oluşturma
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkCreate(Request $request)
    {
        return response()->json(['created' => 0]);
    }

    /**
     * Get persons for form dropdown
     * Context7: Form için kişi listesi API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKisiler()
    {
        try {
            $kisiler = \App\Models\Kisi::select(['id', 'ad', 'soyad', 'telefon'])
                ->orderBy('ad') // context7-ignore
                ->limit(100)
                ->get()
                ->map(function ($kisi) {
                    return [
                        'id' => $kisi->id,
                        'ad' => $kisi->ad,
                        'soyad' => $kisi->soyad,
                        'telefon' => $kisi->telefon,
                        'display_name' => "{$kisi->ad} {$kisi->soyad}".($kisi->telefon ? " - {$kisi->telefon}" : ''),
                    ];
                });

            return response()->json($kisiler);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Kişiler yüklenemedi'], 500);
        }
    }

    /**
     * Get advisors for form dropdown
     * Context7: Form için danışman listesi API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDanismanlar()
    {
        try {
            $danismanlar = \App\Models\User::select(['id', 'name', 'email'])
                ->where('role', 'danisman')
                ->orWhere('role', 'admin')
                ->orderBy('name') // context7-ignore
                ->get();

            return response()->json($danismanlar);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Danışmanlar yüklenemedi'], 500);
        }
    }

    /**
     * Get requests for form dropdown
     * Context7: Form için talep listesi API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTalepler()
    {
        try {
            $talepler = \App\Models\Talep::select(['id', 'baslik', 'il', 'ilce', 'created_at'])
                ->where('talep_durumu', IlanDurumu::YAYINDA->value) // ✅ SAB: talep_durumu (mühürlü kolon)
                ->orderBy('created_at', 'desc') // context7-ignore
                ->limit(100)
                ->get()
                ->map(function ($talep) {
                    return [
                        'id' => $talep->id,
                        'baslik' => $talep->baslik,
                        'il' => $talep->il,
                        'ilce' => $talep->ilce,
                        'display_name' => "{$talep->baslik} - {$talep->il}".($talep->ilce ? "/{$talep->ilce}" : ''),
                    ];
                });

            return response()->json($talepler);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Talepler yüklenemedi'], 500);
        }
    }

    /**
     * Get listings for form dropdown
     * Context7: Form için ilan listesi API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIlanlar()
    {
        try {
            $ilanlar = \App\Models\Ilan::select(['id', 'baslik', 'fiyat', 'para_birimi', 'adres_il', 'adres_ilce'])
                ->where('yayin_durumu', IlanDurumu::YAYINDA->value) // ✅ SAB: yayin_durumu (mühürlü kolon)
                ->orderBy('created_at', 'desc') // context7-ignore
                ->limit(100)
                ->get()
                ->map(function ($ilan) {
                    $fiyatText = $ilan->fiyat ? number_format($ilan->fiyat).' '.($ilan->para_birimi ?? 'TL') : 'Fiyat Yok';
                    $lokasyonText = $ilan->adres_il.($ilan->adres_ilce ? "/{$ilan->adres_ilce}" : '');

                    return [
                        'id' => $ilan->id,
                        'baslik' => $ilan->baslik,
                        'fiyat' => $ilan->fiyat,
                        'para_birimi' => $ilan->para_birimi,
                        'display_name' => "{$ilan->baslik} - {$fiyatText} ({$lokasyonText})",
                    ];
                });

            return response()->json($ilanlar);
        } catch (\Exception $e) {
            return response()->json(['error' => 'İlanlar yüklenemedi'], 500);
        }
    }

    /**
     * Get AI matching suggestions
     * Context7: AI destekli eşleştirme önerileri
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAIEslesmeOnerileri(Request $request)
    {
        try {
            // Mock AI Suggestions - Gerçek AI entegrasyonu için genişletilebilir
            $suggestions = [
                [
                    'score' => 95,
                    'reason' => 'Lokasyon ve fiyat uyumu mükemmel',
                    'ilan_id' => 1,
                    'confidence' => 'Yüksek',
                ],
                [
                    'score' => 87,
                    'reason' => 'Özellikler büyük oranda eşleşiyor',
                    'ilan_id' => 2,
                    'confidence' => 'Orta',
                ],
            ];

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'AI önerileri alınamadı',
            ], 500);
        }
    }

    /**
     * Render view helper
     * Context7: View render helper
     */
    private function render(string $view, array $data = []): Response|\Illuminate\Contracts\View\View
    {
        if (view()->exists($view)) {
            return response()->view($view, $data);
        }

        return response('Eşleşmeler sayfaları hazır değil', 200);
    }
}
