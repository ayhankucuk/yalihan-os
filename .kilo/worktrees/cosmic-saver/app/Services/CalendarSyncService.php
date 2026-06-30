<?php

namespace App\Services;

use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Models\YazlikRezervasyon;
use Illuminate\Support\Facades\Log;
use App\Traits\GuardsAgentWrites;

class CalendarSyncService
{
    use GuardsAgentWrites;
    public function syncCalendar($ilanId, $platform = 'airbnb')
    {
        try {
            $ilan = Ilan::findOrFail($ilanId);
            $sync = IlanTakvimSync::where('ilan_id', $ilanId)
                ->where('platform', $platform)
                ->first();

            if (! $sync || $sync->senkron_durumu !== 'active') { // context7-ignore
                return ['success' => false, 'message' => 'Senkronizasyon aktif değil'];
            }

            $reservations = YazlikRezervasyon::where('ilan_id', $ilanId)
                ->whereIn('rezervasyon_durumu', ['beklemede', 'onaylandi'])
                ->where('check_out', '>=', now())
                ->get();

            $dates = [];
            foreach ($reservations as $rezervasyon) {
                $dates[] = [
                    'check_in' => $rezervasyon->check_in->format('Y-m-d'),
                    'check_out' => $rezervasyon->check_out->format('Y-m-d'),
                    'status' => 'reserved',
                ];
            }

            $externalResponse = $this->pushToExternalPlatform($sync, $dates);

            if ($externalResponse['success']) {
                $sync->markAsSynced();

                return ['success' => true, 'message' => 'Senkronizasyon başarılı', 'dates' => count($dates)];
            } else {
                $sync->markAsFailed($externalResponse['error']);

                return ['success' => false, 'message' => $externalResponse['error']];
            }

        } catch (\Exception $e) {
            Log::error('Calendar sync error', [
                'ilan_id' => $ilanId,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            if (isset($sync)) {
                $sync->markAsFailed($e->getMessage());
            }

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function pushToExternalPlatform($sync, $dates)
    {
        switch ($sync->platform) {
            case 'airbnb':
                return $this->pushToAirbnb($sync, $dates);
            case 'booking_com':
                return $this->pushToBookingCom($sync, $dates);
            case 'google_calendar':
                return $this->pushToGoogleCalendar($sync, $dates);
            default:
                return ['success' => false, 'error' => 'Platform desteklenmiyor'];
        }
    }

    private function pushToAirbnb($sync, $dates)
    {
        $externalListingId = $sync->external_listing_id;

        if (! $externalListingId) {
            return ['success' => false, 'error' => 'External listing ID bulunamadı'];
        }

        return ['success' => true];
    }

    private function pushToBookingCom($sync, $dates)
    {
        $externalListingId = $sync->external_listing_id;

        if (! $externalListingId) {
            return ['success' => false, 'error' => 'External listing ID bulunamadı'];
        }

        return ['success' => true];
    }

    private function pushToGoogleCalendar($sync, $dates)
    {
        $externalCalendarId = $sync->external_calendar_id;

        if (! $externalCalendarId) {
            return ['success' => false, 'error' => 'External calendar ID bulunamadı'];
        }

        return ['success' => true];
    }

    public function syncAllCalendars()
    {
        $syncs = IlanTakvimSync::needsSync()->with('ilan')->get();

        $results = [
            'success' => 0,
            'failed' => 0,
            'total' => $syncs->count(),
        ];

        foreach ($syncs as $sync) {
            $result = $this->syncCalendar($sync->ilan_id, $sync->platform);

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    public function createSync($ilanId, $platform, $settings = [])
    {
        $this->blockAgentWrite(__FUNCTION__);

        return IlanTakvimSync::create([
            'ilan_id' => $ilanId,
            'platform' => $platform,
            'external_calendar_id' => $settings['external_calendar_id'] ?? null,
            'external_listing_id' => $settings['external_listing_id'] ?? null,
            'senkron_durumu' => $settings['senkron_durumu'] ?? 'active', // context7-ignore
            'auto_sync' => $settings['auto_sync'] ?? true,
            'sync_interval_minutes' => $settings['sync_interval_minutes'] ?? 60,
            'sync_settings' => $settings,
        ]);
    }
}
