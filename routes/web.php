<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AiMonitorController;
use App\Http\Controllers\Admin\CustomerProfileController;
use App\Http\Controllers\Admin\FeatureController;
use App\Http\Controllers\Admin\IlanKategoriController;
use App\Http\Controllers\Admin\IlanRaporController;
use App\Http\Controllers\Admin\KisiController;
use App\Http\Controllers\Admin\SystemMonitorController;
use App\Http\Controllers\Admin\TalepPortfolyoController;
use App\Http\Controllers\Admin\TKGMParselController;
use App\Http\Controllers\Advisor\AdvisorAnalyticsController;
use App\Http\Controllers\Advisor\AdvisorCommandCenterController;
use App\Http\Controllers\Advisor\BuyerMatchController;
use App\Http\Controllers\Advisor\BuyerMatchQueueController;
use App\Http\Controllers\Advisor\ConversationalAdvisorController;
use App\Http\Controllers\Advisor\CopilotController;
use App\Http\Controllers\Advisor\DealRadarController;
use App\Http\Controllers\Advisor\MarketIntelligenceAdvisorController;
use App\Http\Controllers\Advisor\MarketValuationController;
use App\Http\Controllers\Advisor\OpportunityInboxController;
use App\Http\Controllers\Advisor\OwnerDiscoveryController;
use App\Http\Controllers\Advisor\PortfolioDoctorController;
use App\Http\Controllers\Advisor\PriceAdvisorController;
use App\Http\Controllers\Advisor\SellerStrategyController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogSitemapController;
use App\Http\Controllers\Frontend\DanismanController;
use App\Http\Controllers\Frontend\PreferenceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Ilan\PropertyFeatureController;
use App\Http\Controllers\IlanPublicController;
use App\Http\Controllers\Owner\OwnerAuthController;
use App\Http\Controllers\Owner\OwnerBelgeController;
use App\Http\Controllers\Owner\OwnerDashboardController;
use App\Http\Controllers\Owner\OwnerIlanController;
use App\Http\Controllers\Owner\OwnerIntelligenceController;
use App\Http\Controllers\Owner\OwnerMesajController;
use App\Http\Controllers\Owner\OwnerPhotoController;
use App\Http\Controllers\Owner\OwnerReportController;
use App\Http\Controllers\Owner\OwnerTeklifController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\ConversationalAdvisorPublicController;
use App\Http\Controllers\Public\IlanCalendarFeedController;
use App\Http\Controllers\Public\InternationalLandingController;
use App\Http\Controllers\SecureFileController;
use App\Models\Il;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Kisi;
use App\Models\User;
use App\Modules\TalepAnaliz\Controllers\TalepAnalizController;
use App\Services\AIService;
use App\Services\CurrencyConversionService;
use Illuminate\Http\Request;

// Preferences Routes (Language & Currency)
Route::prefix('preferences')->name('preferences.')->group(function () {
    Route::post('/locale', [PreferenceController::class, 'setLocale'])->name('locale');
    Route::post('/currency', [PreferenceController::class, 'setCurrency'])->name('currency');
});

// 🌐 International Landing Pages (Locale Prefixed)
Route::prefix('{locale}')->where(['locale' => '[a-z]{2}'])->group(function () {
    Route::get('/invest-in-turkey', [InternationalLandingController::class, 'investInTurkey'])->name('public.invest-in-turkey');
    Route::get('/golden-visa-greece', [InternationalLandingController::class, 'goldenVisaGreece'])->name('public.golden-visa-greece');
    Route::get('/uk-investment', [InternationalLandingController::class, 'ukPropertyInvestment'])->name('public.uk-investment');
    Route::get('/calculator', [InternationalLandingController::class, 'rentalIncomeCalculator'])->name('public.calculator');
});

// ✅ Public Calendar Feed (Phase Q)
Route::get('/calendar/ilan/{token}.ics', [IlanCalendarFeedController::class, 'show'])
    ->middleware(['throttle:60,1'])
    ->name('calendar.feed');

// 🔒 [YALIHAN_REPORTING_0206] Rapor Download - Signed URL (Public with Signature)
Route::get('/rapor/{ilan}/download', [IlanRaporController::class, 'show'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('rapor.download');

// Secure file routes
Route::middleware(['web', 'throttle:secure_file'])->group(function () {
    Route::get('/secure-file/{encodedPath}', [SecureFileController::class, 'serveSecureFile'])
        ->name('secure.file');
    Route::delete('/secure-file/{encodedPath}', [SecureFileController::class, 'deleteSecureFile'])
        ->name('secure.file.delete')
        ->middleware('auth');
});

// Yalihan Design System - Clean Version
Route::get('/yalihan', function () {
    return view('yaliihan-home-clean');
})->name('yalihan.home');

// Context7 demo route removed - use admin.dashboard

// Yalihan Design System - Component Version (Kaldırıldı)

// Yalihan Property Detail Page
Route::get('/yalihan/property/{id}', function ($id) {
    return view('yaliihan-property-detail', ['id' => $id]);
})->name('yalihan.property.detail');

// Yalihan Property Listing Page
Route::get('/yalihan/properties', function () {
    return view('yaliihan-property-listing');
})->name('yalihan.properties');

// TKGM Parsel Sorgulama - MOVED TO routes/api/v1/admin.php
// Route: POST /api/v1/admin/properties/tkgm-lookup
// This endpoint is now centralized in the API system

// Yalihan Contact Page
Route::get('/yalihan/contact', function () {
    return view('yaliihan-contact');
})->name('yalihan.contact');

use App\Http\Controllers\AI\AISearchController;
use App\Http\Controllers\VillaController;

// AI Explore (public)
Route::get('/ai/explore', [AISearchController::class, 'explore'])->name('ai.explore');

// ============================================
// 🏖️ PUBLIC VILLA LISTING (TatildeKirala Tarzı)
// ============================================
Route::prefix('yazliklar')->name('villas.')->group(function () {
    // Villa listing page
    Route::get('/', [VillaController::class, 'index'])->name('index');

    // Villa detail page
    Route::get('/{id}', [VillaController::class, 'show'])->name('show')->where('id', '[0-9]+');

    // Villa detail page
    Route::get('/deal-radar/{ilanId}', [DealRadarController::class, 'show'])->name('advisor.deal-radar.show');

    // AI Owner Discovery Engine
    Route::get('/owner-opportunities', [OwnerDiscoveryController::class, 'index'])->name('advisor.owner-opportunities');
    Route::post('/owner-opportunities/run', [OwnerDiscoveryController::class, 'runDiscovery'])->name('advisor.owner-opportunities.run');

    // AI Market Valuation Engine
    Route::get('/market-valuation', [MarketValuationController::class, 'index'])->name('advisor.market-valuation');
    Route::post('/market-valuation/fetch', [MarketValuationController::class, 'fetch'])->name('advisor.market-valuation.fetch');
    // AI Market Intelligence Engine
    Route::get('/market-intelligence', [MarketIntelligenceAdvisorController::class, 'index'])->name('advisor.market-intelligence');

    // Availability check (AJAX)
    Route::post('/check-availability', [VillaController::class, 'checkAvailability'])->name('check-availability');
});

// Simple ilan create route (for testing)
Route::get('/simple-create', function () {
    $anaKategoriler = IlanKategori::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);
    $kisiler = Kisi::where('aktiflik_durumu', 1)->get(); // ✅ SAB: durum → aktiflik_durumu
    $danismanlar = User::where('role_id', 2)->where('aktiflik_durumu', 1)->get();
    $iller = Il::orderBy('il_adi')->get();

    return view('admin.ilanlar.simple-create', compact('anaKategoriler', 'kisiler', 'danismanlar', 'iller'));
});

// İlan store route for live search form
// ✅ REFACTORED: Duplicate route removed. Official route is admin.ilanlar.store
// Route::post('/ilanlar', [\App\Http\Controllers\Admin\IlanCrudController::class, 'store'])->name('ilanlar.store');

// Hibrit Arama Demo
Route::get('/hybrid-search-demo', function () {
    return view('admin.test.hybrid-search-demo');
})->name('hybrid-search-demo');

// İlan başarı sayfası
Route::get('/ilan-success/{ilan}', function ($ilan) {
    $ilan = Ilan::with(['il', 'ilce', 'mahalle', 'ilanSahibi', 'danisman'])->find($ilan);

    return view('admin.ilanlar.success', compact('ilan'));
})->name('ilan.success')->middleware(['web', 'auth']);

// ===== STABLE-CREATE ROUTES REMOVED - USE /admin/ilanlar/create INSTEAD =====

// DEPRECATED: Old Photo upload endpoint - REMOVED (use /api/v1/admin/photos instead)
// This endpoint was marked as deprecated and has been removed
// Archived in routes/api/v1/admin.php if needed for backward compatibility

// Imports moved to the top of the file

// Auth rotalarını dahil et
require __DIR__.'/auth.php';

// Include validation routes - REMOVED (duplicate with admin.php validate routes)

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Ana sayfa - Yalihan Design System
Route::get('/', [HomeController::class, 'index'])->name('home');

// Test and demo routes removed - use production endpoints instead

// Modern Frontend Routes - Redirected to main
Route::prefix('modern')->name('modern.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('home');
    })->name('home');

    Route::get('/listings', function () {
        return redirect()->route('ilanlar.index');
    })->name('listings');

    Route::get('/listing/{id}', function ($id) {
        return redirect()->route('ilanlar.show', $id);
    })->name('listing.detail');
});

// Public Pages
Route::get('/hakkimizda', function () {
    return view('pages.about');
})->name('about');

Route::get('/iletisim', function () {
    return view('pages.contact');
})->name('contact');

// Frontend Danışmanlar Routes
Route::get('/danismanlar', function () {
    return redirect()->route('frontend.danismanlar.index');
})->name('advisors');

Route::prefix('danismanlar')->name('frontend.danismanlar.')->group(function () {
    Route::get('/', [DanismanController::class, 'index'])->name('index');
    Route::get('/{id}', [DanismanController::class, 'show'])->name('show');
});

// Public Ilan routes (only published)
Route::prefix('ilanlar')->name('ilanlar.')->group(function () {
    Route::get('/', [IlanPublicController::class, 'index'])->name('index');
    Route::get('/international', [IlanPublicController::class, 'international'])->name('international');
    Route::get('/kategori/{kategoriId}', [IlanPublicController::class, 'kategoriIlanlari'])->name('kategori');
    Route::get('/{id}', [IlanPublicController::class, 'show'])->name('show');
    /*
        Route::get('/{id}/calendar', [IlanPublicController::class, 'calendar'])->name('calendar');
        Route::get('/{id}/nearby', [IlanPublicController::class, 'nearby'])->name('ilan.nearby');
    */
    Route::get('/{id}/investment-report', function ($id) {
        $ilan = Ilan::findOrFail($id);

        return view('ilanlar.exports.investment-report', compact('ilan'));
    })->name('investment.report');
});

// Temiz kategori URL'leri — /arsa, /konut
Route::get('/arsa', function (Request $request, CurrencyConversionService $currency) {
    $request->merge(['kategori_slug' => 'arsa-arazi']);

    return app(IlanPublicController::class)->index($request, $currency);
})->name('arsa');

Route::get('/konut', function (Request $request, CurrencyConversionService $currency) {
    $request->merge(['kategori_slug' => 'konut']);

    return app(IlanPublicController::class)->index($request, $currency);
})->name('konut');

Route::get('/satilik', function (Request $request, CurrencyConversionService $currency) {
    $request->merge(['islem_tipi' => 'satis', 'kategori_slug' => 'konut']);

    return app(IlanPublicController::class)->index($request, $currency);
})->name('satilik');

Route::get('/kiralik', function (Request $request, CurrencyConversionService $currency) {
    $request->merge(['islem_tipi' => 'kiralama', 'kategori_slug' => 'konut']);

    return app(IlanPublicController::class)->index($request, $currency);
})->name('kiralik');

// AI Advisor (Public)
Route::get('/ai-advisor', [ConversationalAdvisorPublicController::class, 'index'])->name('public.conversational');
Route::post('/ai-advisor/query', [ConversationalAdvisorPublicController::class, 'query'])
    ->middleware('throttle:60,1')
    ->name('public.conversational.query');
Route::post('/ai-advisor/clear', [ConversationalAdvisorPublicController::class, 'clearHistory'])
    ->name('public.conversational.clear');

// Frontend Portfolio Routes
Route::prefix('portfolio')->name('frontend.portfolio.')->group(function () {
    Route::get('/', function () {
        $properties = Ilan::byYayinDurumu('Aktif')
            ->with(['il', 'ilce', 'etiketler'])
            ->paginate(12);

        $stats = [
            'total_properties' => Ilan::count(),
            'active_properties' => Ilan::byYayinDurumu('Aktif')->count(),
            'total_value' => Ilan::byYayinDurumu('Aktif')->sum('fiyat') / 1000000,
            'locations' => Il::count(),
        ];

        return view('frontend.portfolio.index', compact('properties', 'stats'));
    })->name('index');
});

/*
Route::get('/{id}', [IlanPublicController::class, 'show'])->name('detail');
// Danışman ilanları
Route::get('/danisman/{id}/ilanlar', [IlanPublicController::class, 'danismanIlanlari'])->name('danisman.ilanlar');
*/

// Özellikler API (Public)
/*
Route::get('/ozellikler/by-emlak-turu', [FeatureController::class, 'getByEmlakTuru'])
    ->name('ozellikler.by-emlak-turu');
*/

// Test endpoint
Route::get('/test-features', function () {
    return response()->json([
        'success' => true,
        'message' => 'Test endpoint çalışıyor',
        'features' => [],
    ]);
});

// Neo Location Selector Test - REMOVED
// Use /api/v1/location/* endpoints instead

// Test sayfaları
Route::get('/test-form-handler', function () {
    return response()->file(public_path('test-form-handler.html'));
})->name('test.form.handler');

// Not: Test API endpoints temizlendi. Ana location API kullanılıyor: /api/locations/*

/*
|--------------------------------------------------------------------------
| Blog Routes (Public)
|--------------------------------------------------------------------------
*/

// Blog routes temporarily disabled (Ghost routes - methods missing in BlogController)
/*
Route::group(['prefix' => 'blog'], function () {
    Route::get('/', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/search', [BlogController::class, 'search'])->name('blog.search');
    Route::get('/rss', [BlogController::class, 'rss'])->name('blog.rss');

    // Sitemap routes
    Route::get('/sitemap.xml', [BlogSitemapController::class, 'index'])->name('blog.sitemap.index');
    Route::get('/sitemap-posts.xml', [BlogSitemapController::class, 'posts'])->name('blog.sitemap.posts');
    Route::get('/sitemap-categories.xml', [BlogSitemapController::class, 'categories'])->name('blog.sitemap.categories');
    Route::get('/sitemap-tags.xml', [BlogSitemapController::class, 'tags'])->name('blog.sitemap.tags');

    // Archive routes
    Route::get('/archive/{year}', [BlogController::class, 'archive'])->name('blog.archive.year');
    Route::get('/archive/{year}/{month}', [BlogController::class, 'archive'])->name('blog.archive.month');
    Route::get('/load-more', [BlogController::class, 'loadMore'])->name('blog.load-more');

    // Category and Tag routes
    Route::get('/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
    Route::get('/tag/{slug}', [BlogController::class, 'tag'])->name('blog.tag');

    // Post routes
    Route::get('/{slug}', [BlogController::class, 'show'])->name('blog.show');
    Route::post('/{post}/comment', [BlogController::class, 'storeComment'])->name('blog.comment.store');

    // AJAX routes
    Route::post('/{post}/like', [BlogController::class, 'likePost'])->name('blog.post.like');
    Route::post('/comment/{comment}/like', [BlogController::class, 'likeComment'])->name('blog.comment.like');
    Route::post('/comment/{comment}/dislike', [BlogController::class, 'dislikeComment'])->name('blog.comment.dislike');
});
*/

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

// Test routes (development only)
if (config('app.env') === 'local') {
    Route::get('/test/emlak-loc-integration', function () {
        return view('test.emlak-loc-integration');
    });

    Route::get('/test/form-wizard-debug', function () {
        // İller verisini hazırla
        $iller = Il::where('active', 1)->orderBy('il_adi')->get();

        return view('test.form-wizard-debug', compact('iller'));
    });

    // Site/Apartman Live Search Test
    Route::get('/test/site-live-search', function () {
        return view('admin.test.site-live-search');
    })->name('test.site-live-search');
}

// TKGM Test Routes - REMOVED: Eski sistem temizlendi, yeni sistem routes/api/v1/admin.php'de

Route::get('/test-ollama-models', function () {
    $aiService = app(AIService::class);
    $modelsData = $aiService->getOllamaModels();
    $recommendations = $aiService->getModelRecommendations();

    return response()->json([
        'ollama_models' => $modelsData,
        'recommendations' => $recommendations,
        'timestamp' => now()->toISOString(),
    ]);
})->name('test-ollama-models');

Route::middleware('auth')->group(function () {
    // TKGM Parsel Sorgulama Sistemi
    Route::prefix('admin/tkgm-parsel')->name('admin.tkgm-parsel.')->middleware(['admin', 'role:admin'])->group(function () {
        Route::get('/', [TKGMParselController::class, 'index'])->name('index');
        Route::post('/query', [TKGMParselController::class, 'query'])->name('query');
        Route::post('/bulk-query', [TKGMParselController::class, 'bulkQuery'])->name('bulk-query');
        Route::get('/history', [TKGMParselController::class, 'history'])->name('history');
        Route::get('/stats', [TKGMParselController::class, 'stats'])->name('stats');
    });

    // Admin Talep Analiz (UI tarafında kullanılan admin.* route isimleri için alias)
    Route::prefix('admin/talep-analiz')->name('admin.talepler.analiz.')->group(function () {
        Route::get('/', [TalepAnalizController::class, 'index'])->name('index');
        Route::get('/{id}', [TalepAnalizController::class, 'analizEt'])->name('show');
        Route::post('/toplu-analiz', [TalepAnalizController::class, 'topluAnalizEt'])->name('toplu');
        Route::get('/{id}/rapor', [TalepAnalizController::class, 'raporOlustur'])->name('rapor');
    });
    // AI Settings (Admin) - REMOVED: Çakışan route, admin.php'de mevcut
    // Context7 & MCP: İlan Özellikleri
    Route::get('/admin/property/{propertyId}/features', [PropertyFeatureController::class, 'show'])->name('admin.property.features');
    // MCP & AI destekli müşteri profili
    Route::get('/admin/customer-profile/{customerId}', [CustomerProfileController::class, 'show'])->name('admin.customer-profile');

    // Talep Portfolyo (Admin) - Context7 uyumlu sayfa
    Route::middleware(['web', 'auth', 'admin', 'role:admin', 'throttle:60,1'])
        ->get('/admin/talep-portfolyo', [TalepPortfolyoController::class, 'index'])
        ->name('admin.talep-portfolyo.index');
    // Test Paneli (Context7 Template)
    // Profile Management
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });

    // Admin redirect
    Route::get('/admin', function () {
        return redirect()->route('admin.dashboard.index');
    });

    // Danışman Management (Outside admin prefix)
    Route::prefix('/danisman')->name('danisman.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\DanismanController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\DanismanController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\DanismanController::class, 'store'])->name('store');
        Route::get('/{danisman}', [App\Http\Controllers\Admin\DanismanController::class, 'show'])->name('show');
        Route::get('/{danisman}/edit', [App\Http\Controllers\Admin\DanismanController::class, 'edit'])->name('edit');
        Route::put('/{danisman}', [App\Http\Controllers\Admin\DanismanController::class, 'update'])->name('update');
        Route::delete('/{danisman}', [App\Http\Controllers\Admin\DanismanController::class, 'destroy'])->name('destroy');
        Route::post('/{danisman}/toggle-aktiflik-durumu', [App\Http\Controllers\Admin\DanismanController::class, 'toggleAktiflikDurumu'])->name('toggle-aktiflik-durumu');
        Route::post('/bulk-action', [App\Http\Controllers\Admin\DanismanController::class, 'bulkAction'])->name('bulk-action');
    });

    // CRM - Kişi Yönetimi (Public access)
    Route::resource('/kisiler', KisiController::class)->parameters(['kisiler' => 'kisi']);

    // İlan Kategori Yönetimi
    Route::prefix('/ilan-kategorileri')->name('ilan-kategorileri.')->group(function () {
        Route::get('/yayin-tipleri', [IlanKategoriController::class, 'getYayinTipleri'])->name('getYayinTipleri');
        // Route::get('/filter/{level}', [IlanKategoriController::class, 'filterByLevel'])->name('filterByLevel');
    });

    // Etiket Yönetimi - REMOVED (controller deleted, feature unused)

    // Talep Analiz Modülü
    Route::prefix('/talep-analiz')->name('talep-analiz.')->group(function () {
        Route::get('/', [TalepAnalizController::class, 'index'])->name('index');
        Route::get('/test', [TalepAnalizController::class, 'testSayfasi'])->name('test');
        Route::get('/{id}', [TalepAnalizController::class, 'analizEt'])->name('show');
        Route::post('/toplu-analiz', [TalepAnalizController::class, 'topluAnalizEt'])->name('toplu');
        Route::get('/{id}/rapor', [TalepAnalizController::class, 'raporOlustur'])->name('rapor');
    });

    // Özellikler Management
    /*
    Route::prefix('ozellikler')->name('ozellikler.')->group(function () {
        Route::get('/', [FeatureController::class, 'index'])->name('index');
        Route::get('/create', [FeatureController::class, 'create'])->name('create');
        Route::post('/', [FeatureController::class, 'store'])->name('store');
        Route::get('/{id}', [FeatureController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [FeatureController::class, 'edit'])->name('edit');
        Route::put('/{id}', [FeatureController::class, 'update'])->name('update');
        Route::delete('/{id}', [FeatureController::class, 'destroy'])->name('destroy');

        // Feature Categories - REMOVED (Controller deleted)
    });
    */

    // Kullanıcı Yönetimi - MOVED TO admin.php with proper AuthController
    // Route kullanıcılar yönetimi admin.php'ye taşındı

    // Settings Management (Legacy - moved to admin.php)
    Route::get('/settings', function () {
        return redirect()->route('admin.ayarlar.index');
    });

    // Valuation Dashboard Routes (under /admin)
    Route::prefix('admin/valuation')->name('valuation.')->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.valuation.dashboard');
        })->name('dashboard');

        Route::get('/parcel-search', function () {
            return view('admin.valuation.parcel-search');
        })->name('parcel-search');

        Route::get('/calculate', function () {
            return view('admin.valuation.calculate');
        })->name('calculate');

        Route::get('/reports', function () {
            return view('admin.valuation.reports');
        })->name('reports');

        Route::get('/analytics', function () {
            return view('admin.valuation.analytics');
        })->name('analytics');

        Route::get('/history', function () {
            return view('admin.valuation.history');
        })->name('history');

        Route::get('/market-trends', function () {
            return view('admin.valuation.market-trends');
        })->name('market-trends');
    });

    // Tip Yönetimi - REMOVED (controller deleted, feature unused)

    // Feature API Routes (Modal Selector) - MOVED TO api-admin.php (API routes için)

    // AI Monitoring Dashboard - Web UI (JSON APIs moved to routes/api/v1/admin.php)
    Route::middleware(['web', 'auth', 'throttle:60,1'])->group(function () {
        Route::get('/admin/ai-monitor', [SystemMonitorController::class, 'index'])->name('admin.ai-monitor.index');
        Route::get('/admin/monitor/ai-stats', [AiMonitorController::class, 'index'])->name('admin.monitor.ai-stats');
        // Note: JSON live endpoints are now at /api/v1/admin/ai-monitor/*
    });

    // 🎯 Phase 18 MVP: AI Product Surfaces
    Route::middleware(['web', 'auth', 'throttle:60,1'])->group(function () {
        // AI Advisor Command Center
        Route::get('/command-center', [AdvisorCommandCenterController::class, 'index'])->name('advisor.command-center');
        Route::get('/command-center/fetch', [AdvisorCommandCenterController::class, 'fetch'])->name('advisor.command-center.fetch');

        Route::get('/advisor/opportunities', [OpportunityInboxController::class, 'index'])->name('advisor.opportunities');
        Route::get('/opportunity-inbox/fetch', [OpportunityInboxController::class, 'fetch'])->name('opportunity-inbox.fetch');

        // AI Portfolio Doctor — Eski tanım kaldırıldı, Phase 21 UI route'u (satır 556) kanonik.
        // Route::get('/portfolio-doctor', ...) — DUPLICATE → routes/web.php:556'da tanımlı

        // AI Deal Radar
        Route::get('/advisor/listings/{listing}/buyer-matches', [BuyerMatchQueueController::class, 'index'])->name('advisor.buyer-match-queue');
        Route::get('/advisor/listings/{listing}/buyer-matches/fetch', [BuyerMatchQueueController::class, 'fetch'])->name('advisor.buyer-match-queue.fetch');

        Route::get('/advisor/listing/{ilan}/buyers', [BuyerMatchController::class, 'index'])->name('advisor.listing.buyers');

        // AI Conversational Advisor
        Route::get('/advisor/conversational', [ConversationalAdvisorController::class, 'index'])->name('advisor.conversational');
        Route::post('/advisor/conversational/query', [ConversationalAdvisorController::class, 'query'])
            ->middleware('throttle:60,1')
            ->name('advisor.conversational.query');

        // AI Advisor Analytics
        Route::get('/advisor/analytics', [AdvisorAnalyticsController::class, 'index'])->name('advisor.analytics');

        // AI Deal Radar Engine
        Route::get('/advisor/deal-radar', [DealRadarController::class, 'index'])->name('advisor.deal-radar');
        Route::get('/advisor/deal-radar/fetch', [DealRadarController::class, 'fetch'])->name('advisor.deal-radar.fetch');

        // AI Seller Strategy Engine
        Route::get('/advisor/listings/{listing}/seller-strategy', [SellerStrategyController::class, 'view'])->name('advisor.seller-strategy');
        Route::get('/advisor/listings/{listing}/seller-strategy/fetch', [SellerStrategyController::class, 'fetch'])->name('advisor.seller-strategy.fetch');

        // AI Broker Copilot
        Route::get('/advisor/copilot', [CopilotController::class, 'index'])->name('advisor.copilot');

        // AI Price Advisor
        Route::get('/advisor/listing/{ilan}/price', [PriceAdvisorController::class, 'index'])->name('advisor.listing.price');

        // AI Portfolio Doctor (Phase 21 UI)
        Route::get('/advisor/portfolio/doctor', [PortfolioDoctorController::class, 'index'])->name('advisor.portfolio-doctor');
        Route::get('/advisor/portfolio/doctor/fetch', [PortfolioDoctorController::class, 'fetch'])->name('advisor.portfolio-doctor.fetch');
        Route::get('/advisor/listing/{ilan}/diagnostics', [PortfolioDoctorController::class, 'diagnostics'])->name('advisor.listing.diagnostics');
    });

    // Owner Reporting Module routes moved to /owner portal group below ↓
});

/*
|--------------------------------------------------------------------------
| Admin Blog Routes
|--------------------------------------------------------------------------
*/

// AI Chat Routes - MOVED TO routes/api/v1/ai.php
// Centralized API: POST /api/v1/chat/* endpoints
// Note: Backward compatibility routes - deprecated, use v1 endpoints
Route::prefix('api/ai')->middleware('throttle:30,1')->group(function () {
    // DEPRECATED: Use /api/v1/chat/* instead
    // Route::post('/chat', [App\Http\Controllers\Api\AIChatController::class, 'chat']);
    // Route::post('/generate-description', [App\Http\Controllers\Api\AIChatController::class, 'generateDescription']);
    // Route::post('/suggest-tags', [App\Http\Controllers\Api\AIChatController::class, 'suggestTags']);
    // Route::post('/analyze-demand', [App\Http\Controllers\Api\AIChatController::class, 'analyzeDemand']);
    // Route::post('/find-matching-properties', [App\Http\Controllers\Api\AIChatController::class, 'findMatchingProperties']);
});

// Advanced AI Routes moved to api.php

// Test routes removed - use production APIs

// FRONTEND DYNAMIC FORM ROUTES - Use /api/v1/* endpoints for form handling

// ===== AI MONITORING API - MOVED TO routes/api/v1/ai.php =====
// Reference: API environment monitoring (internal documentation only)
// This endpoint has been centralized in the API system

// Dummy Contact Route for AgentCard Component
Route::post('/contact/submit', function () {
    return back()->with('success', 'Mesajınız başarıyla gönderildi.');
})->name('frontend.forms.contact.submit');

/*
|--------------------------------------------------------------------------
| 🏠 Owner Portal — Mülk Sahibi Paneli
|--------------------------------------------------------------------------
| Prefix  : /owner
| Guard   : check.owner (Spatie 'owner' rolü zorunlu)
| Auth    : Magic-link OTP (şifresiz, email ile)
| Task #14: Route & Auth yapısı — SAB v6.1.2
|--------------------------------------------------------------------------
*/

// Public: Giriş sayfası ve magic-link akışı (auth gerektirmez)
Route::prefix('owner')->name('owner.')->middleware(['web', 'throttle:20,1'])->group(function () {

    Route::get('/login', [OwnerAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/auth/send', [OwnerAuthController::class, 'sendLoginLink'])->name('auth.send');
    Route::get('/auth/verify', [OwnerAuthController::class, 'verifyToken'])->name('auth.verify');

});

// Protected: check.owner middleware (auth + owner role — auth zaten check.owner içinde)
Route::prefix('owner')->name('owner.')->middleware(['web', 'check.owner'])->group(function () {

    // Çıkış
    Route::post('/logout', [OwnerAuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [OwnerDashboardController::class, 'index'])->name('dashboard');

    // 📊 Raporlar (Task #19 — OwnerReportController mevcut altyapı)
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [OwnerReportController::class, 'index'])->name('index');
        Route::post('/export', [OwnerReportController::class, 'export'])->name('export');
        Route::get('/{export}/download', [OwnerReportController::class, 'download'])->name('download');
    });

    // 🏠 İlanlarım (Task #15)
    Route::get('/ilanlar', [OwnerIlanController::class, 'index'])->name('ilanlar.index');
    Route::get('/ilanlar/create', [OwnerIlanController::class, 'create'])->name('ilanlar.create');
    Route::post('/ilanlar', [OwnerIlanController::class, 'store'])->name('ilanlar.store');
    Route::get('/ilanlar/{id}', [OwnerIlanController::class, 'show'])->name('ilanlar.show');
    Route::get('/ilanlar/{id}/edit', [OwnerIlanController::class, 'edit'])->name('ilanlar.edit');
    Route::put('/ilanlar/{id}', [OwnerIlanController::class, 'update'])->name('ilanlar.update');
    Route::delete('/ilanlar/{id}', [OwnerIlanController::class, 'destroy'])->name('ilanlar.destroy');

    // 📷 Portföy Fotoğrafları (Sprint 3.4.2)
    Route::post('/ilanlar/{ilan}/photos', [OwnerPhotoController::class, 'upload'])->name('ilanlar.photos.upload');
    Route::delete('/ilanlar/{ilan}/photos/{photo}', [OwnerPhotoController::class, 'delete'])->name('ilanlar.photos.delete');

    // 🧠 Portföy Hazırlık Analizi (Sprint 3.4.3)
    Route::get('/ilanlar/{ilan}/readiness', [OwnerIntelligenceController::class, 'readiness'])->name('ilanlar.readiness');

    // 📩 Teklifler & Talepler (Task #16)
    Route::get('/teklifler', [OwnerTeklifController::class, 'index'])->name('teklifler.index');
    Route::get('/teklifler/{id}', [OwnerTeklifController::class, 'show'])->name('teklifler.show');

    // 💬 Danışmanla İletişim (Task #17)
    Route::get('/mesajlar', [OwnerMesajController::class, 'index'])->name('mesajlar.index');
    Route::post('/mesajlar', [OwnerMesajController::class, 'store'])->name('mesajlar.store');

    // 📁 Belgelerim (Task #18)
    Route::get('/belgeler', [OwnerBelgeController::class, 'index'])->name('belgeler.index');
    Route::get('/belgeler/{id}/download', [OwnerBelgeController::class, 'download'])->name('belgeler.download');

    // Ana sayfayı dashboard'a yönlendir
    Route::redirect('/', '/owner/dashboard')->name('home');
});
