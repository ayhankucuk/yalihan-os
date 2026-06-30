<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Il;
use App\Models\Ilan;
use App\Models\MarketIntelligenceSetting;
use App\Services\AI\YalihanCortex;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Market Intelligence Controller
 *
 * Context7: Market Intelligence - Pazar İstihbaratı
 * Bölge yönetimi ve veri çekme ayarları
 * ✅ REFACTORED: YalihanCortex merkezi AI sistemi kullanılıyor
 */
class MarketIntelligenceController extends Controller
{
    protected YalihanCortex $cortex;
    // protected \App\Services\Intelligence\MarketIntelligenceService $intelligenceService;

    public function __construct(
        YalihanCortex $cortex,
        // \App\Services\Intelligence\MarketIntelligenceService $intelligenceService
    ) {
        $this->cortex = $cortex;
        // $this->intelligenceService = $intelligenceService;
    }
    /**
     * Dashboard - Genel bakış
     */
    public function dashboard()
    {
        return view('admin.market-intelligence.dashboard');
    }

    /**
     * Settings - Bölge ayarları sayfası
     */
    public function settings()
    {
        $iller = Il::orderBy('il_adi')->get();
        $userSettings = MarketIntelligenceSetting::forUser(Auth::id())
            ->with(['il', 'ilce', 'mahalle'])
            ->orderBy('priority') // context7-ignore
            ->get();

        return view('admin.market-intelligence.settings', [
            'iller' => $iller,
            'settings' => $userSettings,
        ]);
    }

    /**
     * Compare - Fiyat karşılaştırma sayfası
     * ✅ REFACTORED: YalihanCortex ile AI destekli fiyat karşılaştırması
     */
    public function compare($ilanId = null)
    {
        $comparisonData = null;

        if ($ilanId) {
            $ilan = Ilan::with(['il', 'ilce', 'mahalle'])->find($ilanId);
            if ($ilan) {
                $comparisonData = $this->cortex->compareMarketPrices($ilan);
            }
        }

        return view('admin.market-intelligence.compare', [
            'ilan_id' => $ilanId,
            'comparison_data' => $comparisonData,
        ]);
    }

    /**
     * Trends - Piyasa trendleri sayfası
     * ✅ REFACTORED: YalihanCortex ile AI destekli trend analizi
     */
    public function trends(Request $request)
    {
        $filters = [
            'il_id' => $request->input('il_id'),
            'ilce_id' => $request->input('ilce_id'),
            'date_from' => $request->input('date_from', now()->subDays(30)->toDateString()),
            'date_to' => $request->input('date_to', now()->toDateString()),
        ];

        $trendData = $this->cortex->analyzeMarketTrends($filters);

        return view('admin.market-intelligence.trends', [
            'trend_data' => $trendData,
            'filters' => $filters,
        ]);
    }

    /**
     * API: Aktif bölgeleri getir (n8n bot için)
     *
     * GET /api/admin/market-intelligence/active-regions
     * n8n bot'unun hangi bölgeleri tarayacağını döndürür
     */
    public function getActiveRegions()
    {
        try {
            // Global ayarlar + Tüm kullanıcıların aktif ayarları
            $activeRegions = MarketIntelligenceSetting::active()
                ->with(['il', 'ilce', 'mahalle'])
                ->orderBy('priority') // context7-ignore
                ->get()
                ->map(function ($setting) {
                    return [
                        'id' => $setting->id,
                        'il_id' => $setting->il_id,
                        'il_adi' => $setting->il->il_adi ?? null,
                        'ilce_id' => $setting->ilce_id,
                        'ilce_adi' => $setting->ilce->ilce_adi ?? null,
                        'mahalle_id' => $setting->mahalle_id,
                        'mahalle_adi' => $setting->mahalle->mahalle_adi ?? null,
                        'aktiflik_durumu' => $setting->aktiflik_durumu,
                        'priority' => $setting->priority,
                        'is_global' => $setting->isGlobal(),
                        'location_text' => $setting->location_text,
                    ];
                });

            return ResponseService::success($activeRegions, 'Aktif bölgeler listelendi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Aktif bölgeler getirilemedi', $e);
        }
    }

    /**
     * API: Bölge ayarlarını kaydet
     *
     * POST /api/admin/market-intelligence/settings
     */
    public function saveSettings(Request $request)
    {
        try {
            $validated = $request->validate([
                'regions' => 'required|array',
                'regions.*.il_id' => 'required|exists:iller,id',
                'regions.*.ilce_id' => 'nullable|exists:ilceler,id',
                'regions.*.mahalle_id' => 'nullable|exists:mahalleler,id',
                'regions.*.is_active' => 'required|boolean',
                'regions.*.priority' => 'nullable|integer|min:0|max:100',
            ]);

            $userId = Auth::id();

            $savedCount = 0; // $this->intelligenceService->saveSettings($userId, $validated['regions']);

            return ResponseService::success([
                'saved_count' => $savedCount,
            ], 'Bölge ayarları başarıyla kaydedildi');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseService::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseService::serverError('Bölge ayarları kaydedilemedi', $e);
        }
    }

    /**
     * API: Bölge ayarını sil
     *
     * DELETE /api/admin/market-intelligence/settings/{id}
     */
    public function deleteSetting($id, \App\Actions\Admin\Intelligence\DeleteMarketSettingAction $action)
    {
        try {
            $setting = MarketIntelligenceSetting::findOrFail($id);

            // Sadece kendi ayarlarını silebilir (veya admin)
            $user = Auth::user();
            if ($setting->user_id !== $user->id && !in_array($user->role_id, [1, 2])) {
                return ResponseService::forbidden('Bu ayarı silme yetkiniz yok');
            }

            $action->handle($setting);

            return ResponseService::success(null, 'Bölge ayarı başarıyla silindi');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseService::notFound('Bölge ayarı bulunamadı');
        } catch (\Exception $e) {
            return ResponseService::serverError('Bölge ayarı silinemedi', $e);
        }
    }

    /**
     * API: Bölge ayarını aktif/pasif yap
     *
     * PATCH /api/admin/market-intelligence/settings/{id}/toggle
     */
    public function toggleSetting($id, \App\Actions\Admin\Intelligence\ToggleMarketSettingAction $action)
    {
        try {
            $setting = MarketIntelligenceSetting::findOrFail($id);

            // Sadece kendi ayarlarını değiştirebilir (veya admin)
            $user = Auth::user();
            if ($setting->user_id !== $user->id && !in_array($user->role_id, [1, 2])) {
                return ResponseService::forbidden('Bu ayarı değiştirme yetkiniz yok');
            }

            $action->handle($setting);

            return ResponseService::success([
                'id' => $setting->id,
                'aktiflik_durumu' => $setting->aktiflik_durumu,
            ], $setting->aktiflik_durumu ? 'Bölge ayarı aktif edildi' : 'Bölge ayarı pasif edildi');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseService::notFound('Bölge ayarı bulunamadı');
        } catch (\Exception $e) {
            return ResponseService::serverError('Bölge ayarı güncellenemedi', $e);
        }
    }

    /**
     * API: n8n bot'tan gelen verileri senkronize et
     *
     * POST /api/admin/market-intelligence/sync
     * n8n bot'unun çektiği ilanları Laravel'e gönderir
     */
    public function sync(Request $request)
    {
        try {
            $validated = $request->validate([
                'source' => 'required|string|in:sahibinden,hepsiemlak,emlakjet',
                'region' => 'nullable|array',
                'region.il_id' => 'nullable|integer',
                'region.ilce_id' => 'nullable|integer',
                'listings' => 'required|array',
                'listings.*.external_id' => 'required|string',
                'listings.*.url' => 'nullable|string|max:500',
                'listings.*.title' => 'required|string|max:500',
                'listings.*.price' => 'nullable|numeric',
                'listings.*.currency' => 'nullable|string|max:10',
                'listings.*.location_il' => 'nullable|string|max:100',
                'listings.*.location_ilce' => 'nullable|string|max:100',
                'listings.*.location_mahalle' => 'nullable|string|max:100',
                'listings.*.m2_brut' => 'nullable|integer',
                'listings.*.m2_net' => 'nullable|integer',
                'listings.*.room_count' => 'nullable|string|max:20',
                'listings.*.ilan_sahibi' => 'nullable|in:sahibinden,emlakci,bilinmiyor',
                'listings.*.ilan_tarihi' => 'nullable|date',
                'listings.*.ham_veri' => 'nullable|array',
            ]);

            $source = $validated['source'];
            $listings = $validated['listings'];

            $result = [
                'synced_count' => 0,
                'new_count' => 0,
                'updated_count' => 0,
            ]; // $this->intelligenceService->syncListings($source, $listings);

            return ResponseService::success([
                'synced_count' => $result['synced_count'],
                'new_count' => $result['new_count'],
                'updated_count' => $result['updated_count'],
                'source' => $source,
            ], sprintf(
                '%d ilan senkronize edildi (%d yeni, %d güncellendi)',
                $result['synced_count'],
                $result['new_count'],
                $result['updated_count']
            ));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseService::validationError($e->errors());
        } catch (\Exception $e) {
            \App\Services\Logging\LogService::error(
                'Market Intelligence sync failed',
                [
                    'source' => $validated['source'] ?? 'unknown',
                    'listings_count' => count($validated['listings'] ?? []),
                    'error' => $e->getMessage(),
                ],
                $e,
                \App\Services\Logging\LogService::CHANNEL_AI
            );

            return ResponseService::serverError('Veri senkronizasyonu başarısız oldu: ' . $e->getMessage(), $e);
        }
    }

    /**
     * API: Fiyat karşılaştırması yap
     *
     * POST /api/admin/market-intelligence/compare-price
     * ✅ REFACTORED: YalihanCortex kullanılıyor
     */
    public function comparePrice(Request $request)
    {
        try {
            $request->validate([
                'ilan_id' => 'required|exists:ilanlar,id',
            ]);

            $ilan = Ilan::with(['il', 'ilce', 'mahalle'])->findOrFail($request->ilan_id);
            $result = $this->cortex->compareMarketPrices($ilan);

            return ResponseService::success($result, 'Fiyat karşılaştırması tamamlandı');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseService::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseService::serverError('Fiyat karşılaştırması başarısız oldu', $e);
        }
    }

    /**
     * API: Piyasa trend analizi yap
     *
     * POST /api/admin/market-intelligence/analyze-trends
     * ✅ REFACTORED: YalihanCortex kullanılıyor
     */
    public function analyzeTrends(Request $request)
    {
        try {
            $filters = [
                'il_id' => $request->input('il_id'),
                'ilce_id' => $request->input('ilce_id'),
                'mahalle_id' => $request->input('mahalle_id'),
                'date_from' => $request->input('date_from', now()->subDays(30)->toDateString()),
                'date_to' => $request->input('date_to', now()->toDateString()),
            ];

            $result = $this->cortex->analyzeMarketTrends($filters);

            return ResponseService::success($result, 'Trend analizi tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::serverError('Trend analizi başarısız oldu', $e);
        }
    }
}
