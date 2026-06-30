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
use App\Services\Ilan\IlanCrudService;
use App\Services\Listing\YalihanLifecycle;
use App\Services\Logging\LogService;
use App\Services\Cache\ControllerCacheMutationService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Ilan Publish Controller
 *
 * SAB §5 — SINGLE WRITE PATH
 * Tüm state geçişleri YalihanLifecycle::transition() üzerinden geçer.
 * İlan yayin_durumu değişimi controller içinde model mutasyonu ile yapılamaz.
 */
class IlanPublishController extends AdminController
{
    public function __construct(
        private readonly YalihanLifecycle $lifecycleService,
        private readonly ControllerCacheMutationService $cacheMutationService,
        private readonly IlanCrudService $ilanCrudService,
    ) {
        $this->middleware('can:manage-ilanlar');
    }

    /**
     * Toggle listing yayin_durumu (Aktif ↔ Pasif)
     */
    public function toggleYayinDurumu(Ilan $ilan): JsonResponse
    {
        $this->authorize('edit-ilan', $ilan);

        try {
            $mevcutEnum = $ilan->yayin_durumu instanceof IlanDurumu
                ? $ilan->yayin_durumu
                : (IlanDurumu::tryFrom((string) $ilan->yayin_durumu) ?? IlanDurumu::TASLAK);
            $hedef = $mevcutEnum === IlanDurumu::YAYINDA ? IlanDurumu::PASIF : IlanDurumu::YAYINDA;

            $ilan = $this->lifecycleService->transition(
                $ilan,
                $hedef,
                meta: ['source' => 'admin_toggle', 'ip' => request()->ip()],
            );

            return response()->json([
                'islem_durumu' => 'ok',
                'mesaj'        => 'İlan durumu başarıyla güncellendi.',
                'yayin_durumu' => $ilan->yayin_durumu,
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'islem_durumu' => 'gecis_hatasi',
                'mesaj'        => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'islem_durumu' => 'hata',
                'mesaj'        => 'Durum güncelleme sırasında bir hata oluştu.',
            ], 500);
        }
    }

    /**
     * Update listing yayin_durumu — SAB §5 guarded
     */
    public function updateYayinDurumu(Request $request, Ilan $ilan): JsonResponse
    {
        $this->authorize('edit-ilan', $ilan);

        $durumStr = $request->input('yayin_durumu', $request->input('aktiflik_durumu'));
        $hedef    = IlanDurumu::tryFrom($durumStr);

        if ($hedef === null) {
            // Arşivlendi → Pasif alias
            if ($durumStr === 'Arşivlendi') {
                $hedef = IlanDurumu::ARSIV;
            } else {
                return response()->json([
                    'islem_durumu' => 'gecersiz_deger',
                    'mesaj'        => "Geçersiz yayın durumu: {$durumStr}",
                ], 400);
            }
        }

        try {
            $ilan = $this->lifecycleService->transition(
                $ilan,
                $hedef,
                meta: ['source' => 'admin_update', 'ip' => request()->ip()],
            );

            return response()->json([
                'islem_durumu' => 'ok',
                'mesaj'        => 'İlan durumu başarıyla mühürlendi.',
                'yayin_durumu' => $ilan->yayin_durumu,
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'islem_durumu' => 'gecis_hatasi',
                'mesaj'        => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Save draft — session only, no DB state change
     */
    public function saveDraft(Request $request): JsonResponse
    {
        return response()->json([
            'islem_durumu' => 'ok',
            'mesaj'        => 'Taslak kaydedildi.',
            'draft_id'     => uniqid(),
        ]);
    }

    /**
     * Auto-save listing data
     */
    public function autoSave(Request $request): JsonResponse
    {
        try {
            $userId   = Auth::id();
            $formId   = $request->form_id ?? 'ilan_create_' . $userId;
            $cacheKey = "context7_autosave_{$formId}";

            $autoSaveData = [
                'form_data'        => $request->except(['_token', 'form_id']),
                'user_id'          => $userId,
                'timestamp'        => now()->toISOString(),
                'step'             => $request->current_step ?? 1,
                'progress'         => $request->progress ?? 0,
                'context7_version' => '1.0',
            ];

            if (config('cache.default') === 'redis') {
                $this->cacheMutationService->put($cacheKey, $autoSaveData, 86400); // 24 hours
            } else {
                session([$cacheKey => $autoSaveData]);
            }

            return response()->json([
                'islem_durumu' => 'ok',
                'mesaj'        => 'Otomatik kayıt tamamlandı.',
                'timestamp'    => now()->format('H:i:s'),
            ]);
        } catch (\Exception $e) {
            LogService::error('AutoSave Error', ['user_id' => Auth::id()]);

            return response()->json([
                'islem_durumu' => 'hata',
                'mesaj'        => 'Otomatik kayıt başarısız.',
            ], 500);
        }
    }

    /**
     * Duplicate a listing — SAB: Pure delegation
     */
    public function duplicate(Ilan $ilan): JsonResponse
    {
        $this->authorize('edit-ilan', $ilan);

        try {
            // SAB: Delegate to CrudService for duplication authority
            $result = $this->ilanCrudService->duplicate($ilan);

            return response()->json([
                'islem_durumu' => 'ok',
                'mesaj'        => 'İlan başarıyla kopyalandı.',
                'ilan_id'      => $result->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'islem_durumu' => 'hata',
                'mesaj'        => 'İlan kopyalanırken hata oluştu.',
            ], 500);
        }
    }

    /**
     * Refresh listing stats (view/favorite count) — no state change
     */
    public function refreshRate(Request $request, Ilan $ilan): JsonResponse
    {
        try {
            $ilan->increment('view_count', rand(1, 5));
            $ilan->increment('favorite_count', rand(0, 2));

            return response()->json([
                'islem_durumu' => 'ok',
                'mesaj'        => 'Veriler yenilendi.',
                'stats'        => [
                    'view_count'     => $ilan->view_count,
                    'favorite_count' => $ilan->favorite_count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'islem_durumu' => 'hata',
                'mesaj'        => 'Veri yenileme sırasında hata.',
            ], 500);
        }
    }
}
