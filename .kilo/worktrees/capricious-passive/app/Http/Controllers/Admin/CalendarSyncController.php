<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Models\PropertyAvailability;
use App\Services\CalendarSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalendarSyncController extends AdminController
{
    protected $calendarSyncService;

    public function __construct(CalendarSyncService $calendarSyncService)
    {
        $this->calendarSyncService = $calendarSyncService;
    }

    public function getSyncs($ilanId)
    {
        try {
            $ilan = Ilan::findOrFail($ilanId);

            $syncs = IlanTakvimSync::where('ilan_id', $ilanId)
                ->orderBy('platform') // context7-ignore
                ->get();

            return response()->json([
                'success' => true,
                'data' => $syncs,
            ]);

        } catch (\Exception $e) {
            Log::error('Get syncs error', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Senkronizasyonlar alınamadı: '.$e->getMessage(),
            ], 500);
        }
    }

    public function createSync(Request $request, $ilanId, \App\Actions\Admin\Calendar\CreateCalendarSyncAction $action)
    {
        try {
            $validated = $request->validate([
                'platform' => 'required|in:airbnb,booking_com,google_calendar',
                'external_listing_id' => 'required|string',
                'senkronizasyon_durumu' => 'boolean',
            ]);

            $sync = $action->handle((int) $ilanId, [
                'platform' => $validated['platform'],
                'external_listing_id' => $validated['external_listing_id'],
                'senkronizasyon_durumu' => $request->boolean('senkronizasyon_durumu', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Senkronizasyon oluşturuldu',
                'data' => $sync,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Create sync error', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Senkronizasyon oluşturulamadı: '.$e->getMessage(),
            ], 500);
        }
    }

    public function manualSync(Request $request, $ilanId)
    {
        try {
            $request->validate([
                'platform' => 'required|in:airbnb,booking_com,google_calendar',
            ]);

            $result = $this->calendarSyncService->syncCalendar(
                $ilanId,
                $request->platform
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Senkronizasyon başarılı',
                    'data' => $result,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Manual sync error', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Senkronizasyon hatası: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getCalendar($ilanId)
    {
        try {
            $ilan = Ilan::findOrFail($ilanId);

            $availability = PropertyAvailability::where('property_id', $ilanId)
                ->where('date', '>=', now()->format('Y-m-d'))
                ->where('date', '<=', now()->addDays(90)->format('Y-m-d'))
                ->orderBy('date') // context7-ignore
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'is_available' => $item->is_available,
                        'reason' => $item->block_reason,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'ilan_id' => $ilanId,
                    'availability' => $availability,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Get calendar error', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Takvim alınamadı: '.$e->getMessage(),
            ], 500);
        }
    }

    public function blockDates(Request $request, $ilanId, \App\Actions\Admin\Calendar\BlockCalendarDatesAction $action)
    {
        try {
            $validated = $request->validate([
                'dates' => 'required|array',
                'dates.*' => 'required|date',
                'reason' => 'nullable|string',
            ]);

            $blocked = $action->handle((int) $ilanId, $validated['dates'], $validated['reason'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Tarihler engellendi',
                'data' => $blocked,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Block dates error', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Tarihler engellenemedi: '.$e->getMessage(),
            ], 500);
        }
    }


    public function updateSync(Request $request, $ilanId, $syncId, \App\Actions\Admin\Calendar\UpdateCalendarSyncAction $action)
    {
        try {
            $request->validate([
                'external_listing_id' => 'required|string',
                'senkronizasyon_durumu' => 'boolean',
            ]);

            $sync = IlanTakvimSync::where('ilan_id', $ilanId)
                ->findOrFail($syncId);

            $action->handle($sync, $request->only(['external_listing_id', 'senkronizasyon_durumu']));

            return response()->json([
                'success' => true,
                'message' => 'Senkronizasyon güncellendi',
                'data' => $sync->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Update sync error', [
                'ilan_id' => $ilanId,
                'sync_id' => $syncId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Senkronizasyon güncellenemedi: '.$e->getMessage(),
            ], 500);
        }
    }

    public function deleteSync($ilanId, $syncId, \App\Actions\Admin\Calendar\DeleteCalendarSyncAction $action)
    {
        try {
            $sync = IlanTakvimSync::where('ilan_id', $ilanId)
                ->findOrFail($syncId);

            $action->handle($sync);

            return response()->json([
                'success' => true,
                'message' => 'Senkronizasyon silindi',
            ]);

        } catch (\Exception $e) {
            Log::error('Delete sync error', [
                'ilan_id' => $ilanId,
                'sync_id' => $syncId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Senkronizasyon silinemedi: '.$e->getMessage(),
            ], 500);
        }
    }
}
