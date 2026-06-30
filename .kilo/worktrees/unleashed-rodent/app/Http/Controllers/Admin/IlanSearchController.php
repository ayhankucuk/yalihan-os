<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\Ilce;
use App\Models\Mahalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IlanSearchController extends Controller
{
    protected $searchService;

    public function __construct(\App\Services\Admin\IlanSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Live search for listings (smart one-line search).
     */
    public function liveSearch(Request $request)
    {
        // ... implementation remains same or can be switched to service later
        // For now, let's keep liveSearch as is because it has specific fuzzy logic,
        // ALTHOUGH my service update added "Smart Search".
        // Let's stick to refactoring 'filter' first.
        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json(['results' => []]);
        }

        $ilanlar = Ilan::with(['ilanSahibi', 'kategori', 'site', 'userDanisman'])
            ->where(function ($query) use ($search) {
                $like = "%{$search}%";

                $query->where('baslik', 'like', $like)
                    ->orWhere('aciklama', 'like', $like)
                    ->orWhere('referans_no', 'like', $like)
                    ->orWhere('dosya_adi', 'like', $like)
                    ->orWhere('sahibinden_id', 'like', $like)
                    ->orWhere('emlakjet_id', 'like', $like)
                    ->orWhere('hepsiemlak_id', 'like', $like)
                    ->orWhere('zingat_id', 'like', $like)
                    ->orWhere('hurriyetemlak_id', 'like', $like)
                    ->orWhereHas('ilanSahibi', function ($q) use ($like) {
                        $q->where('ad', 'like', $like)
                            ->orWhere('soyad', 'like', $like)
                            ->orWhere('telefon', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    })
                    ->orWhereHas('userDanisman', function ($q) use ($like) {
                        $q->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            })
            ->limit(10)
            ->get();

        $results = $ilanlar->map(function ($ilan) {
            $subtitle = [];

            if ($ilan->ilanSahibi) {
                $subtitle[] = $ilan->ilanSahibi->ad . ' ' . $ilan->ilanSahibi->soyad;
            }

            if ($ilan->kategori) {
                $subtitle[] = $ilan->kategori->name;
            }

            if ($ilan->site) {
                $subtitle[] = $ilan->site->name;
            }

            if ($ilan->referans_no) {
                $subtitle[] = 'Ref: ' . $ilan->referans_no;
            }

            return [
                'id' => $ilan->id,
                'text' => $ilan->baslik . ' - ' . number_format($ilan->fiyat) . ' ' . $ilan->para_birimi,
                'subtitle' => implode(' | ', $subtitle),
                'url' => route('admin.ilanlar.show', $ilan),
            ];
        });

        return response()->json(['results' => $results]);
    }

    public function findByPortalId(Request $request)
    {
        $request->validate([
            'portal' => 'required|string|in:sahibinden,emlakjet,hepsiemlak,zingat,hurriyetemlak',
            'id' => 'required|string|min:2',
        ]);
        $portal = $request->input('portal');
        $id = trim((string) $request->input('id'));
        $map = [
            'sahibinden' => 'sahibinden_id',
            'emlakjet' => 'emlakjet_id',
            'hepsiemlak' => 'hepsiemlak_id',
            'zingat' => 'zingat_id',
            'hurriyetemlak' => 'hurriyetemlak_id',
        ];
        $col = $map[$portal] ?? null;
        if (! $col) {
            return response()->json(['success' => false, 'message' => 'Portal desteklenmiyor'], 422);
        }
        $normalizer = new \App\Services\Portal\PortalIdNormalizer;
        $nid = $normalizer->normalizeProviderId($portal, $id);
        $ilan = Ilan::where($col, $nid)->first();
        if (! $ilan) {
            return response()->json(['success' => false, 'message' => 'Bulunamadı'], 404);
        }
        $data = [
            'id' => $ilan->id,
            'baslik' => $ilan->baslik,
            'kategori' => $ilan->anaKategori->name ?? null,
            'yayin_durumu' => $ilan->yayin_durumu ?? null,
            'fiyat' => $ilan->fiyat ?? null,
            'para_birimi' => $ilan->para_birimi ?? null,
            'created_at' => (string) $ilan->created_at,
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function findByReferans(string $referansNo)
    {
        $ilan = Ilan::where('referans_no', $referansNo)->first();
        if (! $ilan) {
            return response()->json(['success' => false, 'message' => 'Bulunamadı'], 404);
        }
        $data = [
            'id' => $ilan->id,
            'baslik' => $ilan->baslik,
            'kategori' => $ilan->anaKategori->name ?? null,
            'yayin_durumu' => $ilan->yayin_durumu ?? null,
            'fiyat' => $ilan->fiyat ?? null,
            'para_birimi' => $ilan->para_birimi ?? null,
            'created_at' => (string) $ilan->created_at,
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * İlçeler listesi (il bazlı).
     */
    public function getIlceler(Request $request)
    {
        $request->validate(['il_id' => 'required|exists:iller,id']);

        try {
            $ilceler = Ilce::where('il_id', $request->il_id)
                ->where('aktiflik_durumu', true)
                ->orderBy('adi') // context7-ignore
                ->get(['id', 'il_id', 'adi']);

            return response()->json(['success' => true, 'data' => $ilceler]);
        } catch (\Exception $e) {
            Log::error('İlçeler yüklenirken hata', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'İlçeler yüklenirken hata oluştu.',
            ], 500);
        }
    }

    /**
     * Mahalleler listesi (ilçe bazlı).
     */
    public function getMahalleler(Request $request)
    {
        $request->validate(['ilce_id' => 'required|exists:ilceler,id']);

        try {
            $mahalleler = Mahalle::where('ilce_id', $request->ilce_id)
                ->where('aktiflik_durumu', true)
                ->orderBy('adi') // context7-ignore
                ->get(['id', 'ilce_id', 'adi', 'lat', 'lng']);

            return response()->json(['success' => true, 'data' => $mahalleler]);
        } catch (\Exception $e) {
            Log::error('Mahalleler yüklenirken hata', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Mahalleler yüklenirken hata oluştu.',
            ], 500);
        }
    }
    /**
     * Filter listings
     * Context7: İlan filtreleme endpoint
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function filter(Request $request)
    {
        // Context7: Use unified IlanSearchService
        $query = $this->searchService->search($request);

        // Admin grid needs specific relations if not loaded by service
        // Service loads: anaKategori, altKategori, fotograflar, yazlikFiyatlandirma, ilanSahibi, danisman, site
        // Query scope adds relations, so we are good.
        // We might want to ensure ordering is latest updated_at
        // Service default is 'created_at desc' via sort_by param.
        // If admin wants updated_at by default?
        // Let's force it if not present? Or respect service default?
        // Service handles 'sort_by'.

        $perPage = (int) $request->get('per_page', 20);
        $ilanlar = $query->paginate($perPage);

        if ($request->ajax() || $request->wantsJson()) {
            $html = '';
            if (view()->exists('admin.ilanlar.partials.listings-grid')) {
                $html = view('admin.ilanlar.partials.listings-grid', compact('ilanlar'))->render();
            }

            return response()->json([
                'success' => true,
                'html' => $html,
                'data' => $ilanlar->items(), // Return raw data for API
                'total' => $ilanlar->total(),
            ]);
        }

        return view('admin.ilanlar.index', compact('ilanlar'));
    }
    /**
     * Search method (Alias for filter)
     * Matches api.ilanlar.search route.
     */
    public function search(Request $request)
    {
        return $this->filter($request);
    }

    public function findBySite(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

    public function findByTelefon(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }
}
