<?php

namespace App\Http\Controllers;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-thin
 */

use App\Http\Resources\IlanPublicResource;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Ulke;
use App\Services\CurrencyConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Services\AI\YalihanCortex;
use App\Services\Analytics\IlanAnalizService;

class IlanPublicController extends Controller
{
    public function __construct(
        protected YalihanCortex $cortex,
        protected IlanAnalizService $ilanAnalizService,
        protected \App\Services\Publication\InternationalListingService $internationalListingService,
        protected \App\Services\Publication\PublicListingService $publicListingService,
        protected \App\Services\Publication\ListingPresentationService $presentationService,
        protected \App\Services\Publication\ListingStatsService $statsService
    ) {}

    /**
     * Frontend İlan Listesi
     */
    public function index(Request $request, CurrencyConversionService $currencyConversionService)
    {
        $filters = $request->only([
            'kategori', 'kategori_slug', 'islem_tipi',
            'il', 'ilce', 'mahalle', 'min_fiyat', 'max_fiyat', 'search',
            'sort_by', 'sort_order', 'yayin_tipi', 'alt_kategori',
            'min_m2', 'max_m2', 'oda_sayisi', 'havuz_var', 'imar_durumu',
            'mulk_tipi', 'akilli_ev', 'guvenlik', 'otopark', 'spor_salonu',
        ]);

        $query = $this->publicListingService->getFilteredQuery($filters);

        // ✅ EAGER LOADING: Select optimization ile birlikte
        $query->select([
            'id',
            'baslik',
            'fiyat',
            'para_birimi',
            'yayin_durumu',
            'ana_kategori_id',
            'alt_kategori_id',
            'yayin_tipi_id',
            'il_id',
            'ilce_id',
            'mahalle_id',
            'slug',
            'oda_sayisi',
            'net_m2',
            'danisman_id',
            'created_at',
            'updated_at',
        ]);

        $query->with([
            'il:id,il_adi',
            'ilce:id,ilce_adi',
            'mahalle:id,mahalle_adi',
            'kategori:id,name',
            'anaKategori:id,name,slug',
            'altKategori:id,name,slug,parent_id',
            'yayinTipi:id,yayin_tipi',
            'fotograflar',
            'danisman:id,name,whatsapp_numara,telefon',
        ]);

        $ilanlar = $query->paginate(12);

        // Para birimi dönüşümü (Consistency: Index page should also support currency)
        $currency = strtoupper(session('currency', $currencyConversionService->getDefault()));
        $this->presentationService->applyCurrencyConversions($ilanlar->items(), $currency);

        // API response
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => IlanPublicResource::collection($ilanlar->items()),
                'meta' => [
                    'current_page' => $ilanlar->currentPage(),
                    'last_page' => $ilanlar->lastPage(),
                    'per_page' => $ilanlar->perPage(),
                    'total' => $ilanlar->total(),
                    'currency' => $currency
                ],
            ]);
        }

        // Filtreler için veriler — kategori ve il sayıları ile birlikte
        $aktifDurum = IlanDurumu::YAYINDA->value;
        $kategoriler = IlanKategori::whereNull('parent_id')
            ->withCount(['anaKategoriIlanlar as ilan_sayisi' => function ($q) use ($aktifDurum) {
                $q->where('yayin_durumu', $aktifDurum);
            }])
            ->with(['children' => function ($q) use ($aktifDurum) {
                $q->withCount(['altKategoriIlanlar as ilan_sayisi' => function ($qq) use ($aktifDurum) {
                    $qq->where('yayin_durumu', $aktifDurum);
                }])
                ->orderBy('display_order')->orderBy('name');
            }])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        // İller — hiyerarşik: il → ilçe (ilan_sayisi > 0) → mahalle (ilan_sayisi > 0)
        $iller = Il::select(['id', 'il_adi'])
            ->withCount(['ilanlar as ilan_sayisi' => function ($q) use ($aktifDurum) {
                $q->where('yayin_durumu', $aktifDurum);
            }])
            ->with(['ilceler' => function ($q) use ($aktifDurum) {
                $q->select(['id', 'il_id', 'ilce_adi'])
                  ->withCount(['ilanlar as ilan_sayisi' => function ($qq) use ($aktifDurum) {
                      $qq->where('yayin_durumu', $aktifDurum);
                  }])
                  ->with(['mahalleler' => function ($m) use ($aktifDurum) {
                      $m->select(['id', 'ilce_id', 'mahalle_adi', 'display_order'])
                        ->withCount(['ilanlar as ilan_sayisi' => function ($mm) use ($aktifDurum) {
                            $mm->where('yayin_durumu', $aktifDurum);
                        }])
                        ->having('ilan_sayisi', '>', 0)
                        ->orderBy('display_order')
                        ->orderBy('mahalle_adi');
                  }])
                  ->having('ilan_sayisi', '>', 0)
                  ->orderByDesc('ilan_sayisi');
            }])
            ->having('ilan_sayisi', '>', 0)
            ->orderByDesc('ilan_sayisi')
            ->orderBy('il_adi')
            ->get();

        // İlçeler — Alpine.js cascade için JSON (eski uyumluluk — hâlâ kullanılıyor)
        $ilceler = Ilce::orderBy('ilce_adi')->get(['id', 'il_id', 'ilce_adi']);

        // Bodrum mahalleleri — ilce_id=1 (Bodrum), öne çıkan bölgeler önce, ilan sayısıyla birlikte
        // Aktif kategori varsa ona göre filtrele; yoksa tüm kategoriler
        $bodrumKategoriId = null;
        if ($request->filled('kategori_slug')) {
            $bodrumKategoriId = IlanKategori::where('slug', $request->input('kategori_slug'))->value('id');
        } elseif ($request->filled('kategori')) {
            $bodrumKategoriId = (int) $request->input('kategori');
        }

        $bodrumMahalleleri = Mahalle::where('ilce_id', 1)
            ->withCount(['ilanlar as ilan_sayisi' => function ($q) use ($aktifDurum, $bodrumKategoriId) {
                $q->where('yayin_durumu', $aktifDurum);
                if ($bodrumKategoriId) {
                    $q->where('ana_kategori_id', $bodrumKategoriId);
                }
            }])
            ->having('ilan_sayisi', '>', 0)
            ->orderBy('display_order')
            ->orderByDesc('ilan_sayisi')
            ->orderBy('mahalle_adi')
            ->get(['id', 'mahalle_adi', 'display_order']);

        // kategori_slug → seçili kategori ID'sine çevir (dropdown için)
        $selectedKategoriId = $request->input('kategori');
        if (!$selectedKategoriId && $request->filled('kategori_slug')) {
            $selectedKategoriId = IlanKategori::where('slug', $request->input('kategori_slug'))
                ->value('id');
        }

        // Seçili kategorinin alt kategorileri
        $altKategoriler = collect();
        if ($selectedKategoriId) {
            $secilenKat = $kategoriler->firstWhere('id', $selectedKategoriId);
            $altKategoriler = $secilenKat ? $secilenKat->children : collect();
        }

        // @sab-ignore-naming: View layer variables (not DB columns)
        return view('frontend.ilanlar.index', compact(
            'ilanlar', 'kategoriler', 'iller', 'ilceler', 'bodrumMahalleleri',
            'currency', 'selectedKategoriId', 'altKategoriler'
        ));
    }

    /**
     * Portfolio Index
     */
    public function portfolio(Request $request, CurrencyConversionService $currencyConversionService)
    {
        $filters = $request->only(['kategori', 'il', 'search']);
        $query = $this->publicListingService->getPortfolioQuery($filters);

        $currency = strtoupper(session('currency', $currencyConversionService->getDefault()));

        // EAGER LOADING: Select optimization ile birlikte
        $query->select([
            'id',
            'baslik',
            'aciklama',
            'fiyat',
            'para_birimi',
            'yayin_durumu',
            'ana_kategori_id',
            'il_id',
            'ilce_id',
            'slug',
            'oda_sayisi',
            'banyo_sayisi',
            'brut_m2',
            'net_m2',
            'created_at',
            'updated_at',
        ]);

        $query->with([
            'il:id,il_adi',
            'ilce:id,ilce_adi',
            'anaKategori:id,name',
            'fotograflar:id,ilan_id,dosya_yolu,kapak_fotografi,display_order',
            'etiketler:id,name,slug,type,icon,color,bg_color',
        ]);

        $properties = $query->orderBy('created_at', 'desc')->paginate(12); // context7-ignore

        // Apply presentation logic via direct service
        $this->presentationService->applyCurrencyConversions($properties->items(), $currency);

        // Stats via direct service
        $stats = $this->statsService->getPortfolioStats();

        // Filtreler için veriler
        $kategoriler = IlanKategori::whereNull('parent_id')->orderBy('name')->get(); // context7-ignore
        $iller = Il::orderBy('il_adi')->get();

        return view('frontend.portfolio.index', compact('properties', 'stats', 'kategoriler', 'iller', 'currency'));
    }

    /**
     * Uluslararası Portföy Listesi
     */
    /**
     * Uluslararası Portföy Listesi
     */
    public function international(Request $request, CurrencyConversionService $currencyConversionService)
    {
        // 1. Prepare Filters
        $filters = $request->only([
            'country', 'city', 'citizenship',
            'min_price', 'max_price',
            'property_type', 'delivery',
            'min_area', 'max_area', 'type', // context7-ignore
            'search',
        ]);

        // 2. Get Query from Service
        $query = $this->internationalListingService->getFilteredQuery($filters);

        // 3. Get Static Data & Options
        $categoryTabs = [
            ['label' => 'Satılık', 'value' => 'sale'],
            ['label' => 'Kiralık', 'value' => 'rent'],
            ['label' => 'Yazlık', 'value' => 'seasonal'],
            ['label' => 'Vatandaşlığa Uygun', 'value' => 'citizenship'],
        ];

        $filterOptions = $this->internationalListingService->getFilterOptions();
        $citizenshipPrograms = $this->internationalListingService->getCitizenshipPrograms();
        $faqs = $this->internationalListingService->getFaqs();

        // 4. Calculate Stats
        $stats = $this->internationalListingService->getStatistics($query);

        // 5. Get Currency
        $currency = strtoupper(session('currency', $currencyConversionService->getDefault()));

        // 6. Get Featured Listings (Paginated & Converted)
        $featured = $this->internationalListingService->getFeaturedListings($query, $currency);

        // 7. View
        return view('frontend.ilanlar.international', [
            'featured' => $featured,
            'categoryTabs' => $categoryTabs,
            'activeTab' => $filters['type'] ?? 'sale', // context7-ignore
            'filters' => $filterOptions,
            'selectedFilters' => $filters,
            'stats' => $stats,
            'citizenshipPrograms' => $citizenshipPrograms,
            'faqs' => $faqs,
            'currency' => $currency,
        ]);
    }

    /**
     * Frontend İlan Karşılaştırma
     */
    public function compare(Request $request, CurrencyConversionService $currencyConversionService)
    {
        $ids = $request->query('ids');
        if (!$ids) {
            return redirect()->route('ilanlar.index')->with('warning', 'Karşılaştırmak için ilan seçmediniz.');
        }

        $idArray = is_array($ids) ? $ids : explode(',', $ids);
        
        // En fazla 4 ilan karşılaştırılabilir
        if (count($idArray) > 4) {
            $idArray = array_slice($idArray, 0, 4);
        }

        $ilanlar = Ilan::with([
            'il:id,il_adi',
            'ilce:id,ilce_adi',
            'mahalle:id,mahalle_adi',
            'kategori:id,name',
            'altKategori:id,name',
            'fotograflar',
            'ozellikler'
        ])
        ->whereIn('id', $idArray)
        ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
        ->get();

        if ($ilanlar->isEmpty()) {
            return redirect()->route('ilanlar.index')->with('warning', 'Geçerli ilan bulunamadı.');
        }

        $currency = session()->get('currency', 'TRY');

        return view('frontend.ilanlar.karsilastirma', compact('ilanlar', 'currencyConversionService', 'currency'));
    }

    /**
     * Frontend İlan Detayı
     */
    public function show($id, CurrencyConversionService $currencyConversionService)
    {
        $ilan = Ilan::with([
            'il:id,il_adi',
            'ilce:id,ilce_adi',
            'mahalle:id,mahalle_adi',
            'kategori:id,name,slug',
            'anaKategori:id,name,slug',
            'altKategori:id,name,slug',
            'danisman:id,name,email,telefon,whatsapp_numara,baslik,instagram_profil',
            'ilanSahibi:id,ad,soyad,telefon',
            'arsaDetail',
            'fotograflar' => function ($q) {
                $q->select('id', 'ilan_id', 'dosya_yolu', 'display_order', 'kapak_fotografi')
                    ->orderBy('display_order'); // context7-ignore
            },
        ])
            ->byYayinDurumu(IlanDurumu::YAYINDA->value) // Use standardized scope for visibility
            ->findOrFail($id);

        $currency = strtoupper(session('currency', $currencyConversionService->getDefault()));

        // Single Ilan conversion
        $this->presentationService->applyCurrencyConversions([$ilan], $currency);

        // API response
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => new IlanPublicResource($ilan),
                'meta' => ['currency' => $currency]
            ]);
        }

        // Benzer ilanlar (ListingNavigationService kullanıyoruz artık)
        $navigationService = app(\App\Services\ListingNavigationService::class);
        $similar = $navigationService->getSimilar($ilan, 4);

        // Similar listings conversion
        $this->presentationService->applyCurrencyConversions($similar, $currency);

        // Danışmanın diğer aktif ilanları (max 4, mevcut ilan hariç)
        $danismanDigerIlanlar = collect();
        if ($ilan->danisman_id) {
            $danismanDigerIlanlar = Ilan::where('danisman_id', $ilan->danisman_id)
                ->where('id', '!=', $ilan->id)
                ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
                ->with([
                    'ilce:id,ilce_adi',
                    'anaKategori:id,name',
                    'fotograflar' => fn($q) => $q->select('id','ilan_id','dosya_yolu','display_order')->orderBy('display_order')->limit(1), // context7-ignore
                ])
                ->latest()
                ->limit(4)
                ->get();
        }

        // Cortex AI Zekası: Listing Health ve Detaylı Analiz
        $cortexHealth = $this->cortex->analyzeListingHealth($ilan->id);
        $cortexAnalysis = $this->ilanAnalizService->getDetayliRapor($ilan->id);

        // ── SEO & OG Image ──────────────────────────────────────────
        $kapakFoto = $ilan->fotograflar->firstWhere('kapak_fotografi', true)
            ?? $ilan->fotograflar->first();
        $mainImage = $kapakFoto
            ? \Illuminate\Support\Facades\Storage::url($kapakFoto->dosya_yolu)
            : asset('images/default-property.jpg');

        // SEO & OG — WhatsApp/sosyal medya paylaşım optimizasyonu
        $ilceAdiSeo  = is_object($ilan->getRelation('ilce') ?? null) ? $ilan->getRelation('ilce')->ilce_adi : 'Bodrum';
        $fiyatSeo    = $ilan->fiyat ? number_format($ilan->fiyat, 0, ',', '.') . ' ' . ($ilan->para_birimi ?? '₺') : '';
        $seo = [
            'title'       => $ilan->baslik . ' — ' . $ilceAdiSeo . ' | Yalıhan Emlak',
            'description' => trim(($fiyatSeo ? $fiyatSeo . ' — ' : '') . $ilceAdiSeo . ' · ' . \Illuminate\Support\Str::limit(strip_tags($ilan->aciklama ?? ''), 120)),
            'og_type'     => 'og:product',
            'og_image'    => $mainImage ? url($mainImage) : asset('images/og-image.jpg'),
            'og_locale'   => app()->getLocale() === 'tr' ? 'tr_TR' : 'en_US',
            'canonical'   => route('ilanlar.show', $ilan->id),
            'robots'      => 'index, follow',
        ];

        // "Yazlık Kiralık" kategorisi kontrolü (Kategori slug veya Yayin tipi)
        $isYazlik = ($ilan->kategori && str_contains(strtolower($ilan->kategori->slug), 'yazlik')) 
                 || ($ilan->altKategori && str_contains(strtolower($ilan->altKategori->slug), 'yazlik'))
                 || ($ilan->anaKategori && str_contains(strtolower($ilan->anaKategori->slug), 'yazlik'))
                 || str_contains(strtolower($ilan->kategori_adi ?? ''), 'yazlık');

        $viewName = $isYazlik ? 'frontend.ilanlar.show-yazlik' : 'frontend.ilanlar.show';

        return view($viewName, compact('ilan', 'similar', 'danismanDigerIlanlar', 'cortexHealth', 'cortexAnalysis', 'currency', 'seo', 'mainImage'));
    }

    /**
     * Danışman İlanları
     */
    public function danismanIlanlari($id, CurrencyConversionService $currencyConversionService)
    {
        $ilanlar = Ilan::with(['il', 'ilce', 'kategori'])
            ->byYayinDurumu(IlanDurumu::YAYINDA->value) // Context7 compliant!
            ->where('danisman_id', $id)
            ->orderBy('created_at', 'desc') // context7-ignore
            ->paginate(12);

        $currency = strtoupper(session('currency', $currencyConversionService->getDefault()));
        $this->presentationService->applyCurrencyConversions($ilanlar->items(), $currency);

        return view('frontend.ilanlar.danisman', compact('ilanlar', 'currency'));
    }

    /**
     * Kategori İlanları
     */
    public function kategoriIlanlari($kategoriId, CurrencyConversionService $currencyConversionService)
    {
        $kategori = IlanKategori::findOrFail($kategoriId);

        $query = Ilan::with(['il', 'ilce', 'kategori'])
            ->byYayinDurumu(IlanDurumu::YAYINDA->value); // Context7 compliant!

        // Context7: Determine if filtering by Main or Sub category
        if ($kategori->parent_id === null) {
            $query->where('ana_kategori_id', $kategoriId);
        } else {
            // Check both alt_kategori_id (legacy map) and direct equality just in case
            $query->where(function($q) use ($kategoriId) {
                $q->where('alt_kategori_id', $kategoriId);
            });
        }

        $ilanlar = $query->orderBy('created_at', 'desc') // context7-ignore
            ->paginate(12);

        $currency = strtoupper(session('currency', $currencyConversionService->getDefault()));
        $this->presentationService->applyCurrencyConversions($ilanlar->items(), $currency);

        return view('frontend.ilanlar.kategori', compact('ilanlar', 'kategori', 'currency'));
    }
}
