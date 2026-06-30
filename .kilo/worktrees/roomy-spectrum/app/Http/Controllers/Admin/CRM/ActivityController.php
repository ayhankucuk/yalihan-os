<?php

namespace App\Http\Controllers\Admin\CRM;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Kisi;
use App\Models\KisiEtkilesim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * CRM Activity Timeline Controller
 *
 * Müşteri etkileşim geçmişini (Activity Timeline) yönetir.
 * 6 aktivite tipi: arama, whatsapp, email, randevu, gorusme, not
 *
 * Context7 Compliance:
 * - Uses `etkilesim_tipi` (not forbidden keyword)
 * - Uses `crm_surec_asamasi` for customer stage
 * - Activity logging for audit trail
 */
class ActivityController extends Controller
{
    /**
     * Get all activities for a person
     *
     * Müşterinin tüm etkileşim geçmişini kronolojik sırada getirir.
     * En yeni aktiviteler en üstte.
     *
     * @param Kisi $kisi
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivities(Kisi $kisi)
    {
        try {
            // Load activities with user relationship
            $activities = KisiEtkilesim::where('kisi_id', $kisi->id)
                ->with('kullanici:id,ad_soyad')
                ->orderBy('created_at', 'desc') // context7-ignore
                ->limit(50) // Last 50 activities
                ->get()
                ->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'etkilesim_tipi' => $activity->etkilesim_tipi,
                        'aciklama' => $activity->aciklama,
                        'kullanici' => $activity->kullanici?->ad_soyad ?? 'Sistem',
                        'created_at' => $activity->created_at->toISOString(),
                        'formatted_date' => $activity->created_at->diffForHumans(),
                    ];
                });

            return response()->json([
                'success' => true,
                'activities' => $activities,
                'count' => $activities->count()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Activities load error', [
                'kisi_id' => $kisi->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Aktiviteler yüklenirken bir hata oluştu.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new activity
     *
     * Yeni bir müşteri etkileşimi kaydeder.
     * Aktivite tipleri: arama, whatsapp, email, randevu, gorusme, not
     *
     * @param Request $request
     * @param Kisi $kisi
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeActivity(Request $request, Kisi $kisi, \App\Actions\CRM\Activity\StoreActivityAction $action)
    {
        $this->authorize('update', $kisi);

        try {
            // Validation
            $validated = $request->validate([
                'etkilesim_tipi' => 'required|in:arama,whatsapp,email,randevu,gorusme,not,surec_degisikligi',
                'aciklama' => 'required|string|max:500',
            ]);

            $activity = $action->handle($kisi->id, $validated);

            // Format response
            $formattedActivity = [
                'id' => $activity->id,
                'etkilesim_tipi' => $activity->etkilesim_tipi,
                'aciklama' => $activity->aciklama,
                'kullanici' => $activity->kullanici?->ad_soyad ?? 'Sistem',
                'created_at' => $activity->created_at->toISOString(),
                'formatted_date' => $activity->created_at->diffForHumans(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Aktivite başarıyla eklendi.',
                'activity' => $formattedActivity
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasyon hatası.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ Activity creation error', [
                'kisi_id' => $kisi->id,
                'kullanici_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Aktivite eklenirken bir hata oluştu.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an activity
     *
     * Bir aktiviteyi siler (opsiyonel).
     * Not: Soft delete kullanılabilir.
     *
     * @param Kisi $kisi
     * @param KisiEtkilesim $activity
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteActivity(Kisi $kisi, KisiEtkilesim $activity, \App\Actions\CRM\Activity\DeleteActivityAction $action)
    {
        $this->authorize('delete', $activity);

        try {
            // Check if activity belongs to this person
            if ($activity->kisi_id !== $kisi->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu aktivite bu müşteriye ait değil.'
                ], 403);
            }

            // Delete
            $action->handle($activity);

            return response()->json([
                'success' => true,
                'message' => 'Aktivite başarıyla silindi.'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Activity deletion error', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Aktivite silinirken bir hata oluştu.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get activity statistics for a person
     *
     * Müşterinin aktivite istatistiklerini getirir.
     * Aktivite tipine göre sayım ve trend analizi.
     *
     * @param Kisi $kisi
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivityStats(Kisi $kisi)
    {
        try {
            $stats = KisiEtkilesim::where('kisi_id', $kisi->id)
                ->selectRaw('etkilesim_tipi, COUNT(*) as count')
                ->groupBy('etkilesim_tipi')
                ->get()
                ->pluck('count', 'etkilesim_tipi');

            $lastActivity = KisiEtkilesim::where('kisi_id', $kisi->id)
                ->latest()
                ->first();

            $activityCount7Days = KisiEtkilesim::where('kisi_id', $kisi->id)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            return response()->json([
                'success' => true,
                'stats' => [
                    'by_type' => $stats,
                    'total' => $stats->sum(),
                    'last_7_days' => $activityCount7Days,
                    'last_activity' => $lastActivity ? [
                        'etkilesim_tipi' => $lastActivity->etkilesim_tipi,
                        'date' => $lastActivity->created_at->diffForHumans()
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Activity stats error', [
                'kisi_id' => $kisi->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'İstatistikler yüklenirken bir hata oluştu.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
