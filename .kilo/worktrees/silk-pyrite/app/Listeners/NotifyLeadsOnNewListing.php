<?php

namespace App\Listeners;

use App\Events\IlanYayinlandiEvent;
use App\Models\Lead;
use App\Services\Notification\WhatsAppNotificationManager;
use Illuminate\Support\Facades\Log;

/**
 * Listen for IlanYayınlandıEvent and notify matching leads
 * 
 * Ultra-Think: Hafıza Senkronizasyonu - Event-driven notification flow
 */
class NotifyLeadsOnNewListing
{
    protected WhatsAppNotificationManager $notificationManager;

    public function __construct(WhatsAppNotificationManager $notificationManager)
    {
        $this->notificationManager = $notificationManager;
    }

    /**
     * Handle the event
     */
    public function handle(IlanYayinlandiEvent $event): void
    {
        try {
            $ilan = $event->ilan;

            Log::info('Processing new listing notifications', [
                'ilan_id' => $ilan->id,
                'baslik' => $ilan->baslik,
            ]);

            // 1. Find matching leads (same location + property type + budget match)
            $matchingLeads = $this->findMatchingLeads($ilan);

            Log::info('Found matching leads', [
                'ilan_id' => $ilan->id,
                'count' => $matchingLeads->count(),
            ]);

            // 2. Notify each lead
            $notified = 0;
            foreach ($matchingLeads as $lead) {
                if ($this->notificationManager->notifyLeadOfMatch($lead, $ilan)) {
                    $notified++;
                }
            }

            Log::info('Notification sending complete', [
                'ilan_id' => $ilan->id,
                'notified' => $notified,
                'total_matches' => $matchingLeads->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing listing notifications', [
                'ilan_id' => $event->ilan->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Find leads matching the property listing
     * 
     * Context7: Performance - use eager loading (with)
     */
    protected function findMatchingLeads($ilan)
    {
        return Lead::query()
            ->where('platform', 'whatsapp')
            ->whereNotNull('platform_phone')
            ->where('crm_status', '!=', 'won') // Exclude closed deals
            ->where('aktif', true) // Context7: boolean field
            ->when($ilan->interested_location_id, function ($query) use ($ilan) {
                // Match location
                $query->where('interested_location_id', $ilan->il_id);
            })
            ->when($ilan->interested_property_type, function ($query) use ($ilan) {
                // Match property type
                $query->where('interested_property_type', $ilan->kategori_id);
            })
            ->when($ilan->fiyat, function ($query) use ($ilan) {
                // Match budget
                $query->where('budget_min', '<=', $ilan->fiyat)
                      ->where(function ($q) use ($ilan) {
                          $q->whereNull('budget_max')
                            ->orWhere('budget_max', '>=', $ilan->fiyat);
                      });
            })
            ->highConfidence() // Lead confidence >= 0.70
            ->with(['messages', 'activities', 'agent']) // Eager load relationships
            ->get();
    }
}
