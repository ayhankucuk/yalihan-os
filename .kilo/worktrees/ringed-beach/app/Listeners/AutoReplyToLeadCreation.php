<?php

namespace App\Listeners;

use App\Events\LeadOlusturuldu;
use App\Models\Lead;
use App\Services\Notification\InstagramAutoReplyService;
use App\Services\Notification\FacebookAutoReplyService;
use Illuminate\Support\Facades\Log;

/**
 * AutoReplyToLeadCreation
 *
 * Automatically replies to leads when they first message on Instagram or Facebook
 * Sends welcome message and quick action menu based on platform
 */
class AutoReplyToLeadCreation
{
    protected InstagramAutoReplyService $instagramService;
    protected FacebookAutoReplyService $facebookService;

    /**
     * Create the event listener.
     */
    public function __construct(
        InstagramAutoReplyService $instagramService,
        FacebookAutoReplyService $facebookService
    ) {
        $this->instagramService = $instagramService;
        $this->facebookService = $facebookService;
    }

    /**
     * Handle the event.
     */
    public function handle(LeadOlusturuldu $event): void
    {
        $lead = $event->lead;

        try {
            switch ($lead->platform) {
                case 'instagram':
                    $this->handleInstagram($lead);
                    break;
                case 'facebook':
                    $this->handleFacebook($lead);
                    break;
                case 'whatsapp':
                    // WhatsApp handled by different listener
                    break;
                default:
                    Log::warning('Unknown platform for auto-reply', [
                        'lead_id' => $lead->id,
                        'platform' => $lead->platform,
                    ]);
            }

            Log::info('Auto-reply sent to lead', [
                'lead_id' => $lead->id,
                'platform' => $lead->platform,
            ]);
        } catch (\Exception $e) {
            Log::error('Auto-reply failed', [
                'lead_id' => $lead->id,
                'platform' => $lead->platform,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle Instagram auto-reply
     */
    private function handleInstagram(Lead $lead): void
    {
        // Send welcome message
        $this->instagramService->sendAutoReply(
            $lead,
            "👋 Merhaba " . ($lead->first_name ?? 'arkadaş') . "!\n\n"
            . "Yalıhan Emlak'a hoş geldin. 🏠\n"
            . "Senin için uygun mülkleri bulmaya başladık. Yakında iletişime geçilecektir! 💼",
            ['type' => 'welcome']
        );

        // Send quick action menu after 2 seconds
        \App\Jobs\SendInstagramQuickMenuJob::dispatch($lead)
            ->delay(now()->addSeconds(2));
    }

    /**
     * Handle Facebook auto-reply
     */
    private function handleFacebook(Lead $lead): void
    {
        // Send welcome message with quick reply buttons
        $this->facebookService->sendWelcomeMessage($lead);
    }
}
