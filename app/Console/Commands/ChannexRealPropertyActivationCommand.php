<?php

namespace App\Console\Commands;

use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Models\PropertyReadiness;
use App\Models\PropertyReservation;
use App\Models\SaaS\Tenant;
use App\Modules\TakimYonetimi\Models\Gorev;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ChannexRealPropertyActivationCommand — Maps a verified real Channex property UUID.
 *
 * WAVE 7 Phase B1.1 — Real OTA Proof & Activation
 */
class ChannexRealPropertyActivationCommand extends Command
{
    protected $signature = 'channex:activate-property
                            {--property-uuid= : The verified real Channex Property UUID}
                            {--listing-id=1 : The Yalıhan listing ID to bind}
                            {--platform=airbnb : Connected OTA platform (airbnb|booking_com)}
                            {--clean-canary : Clean synthetic canary reservation #1 safely prior to real ingestion}';

    protected $description = 'Maps a verified Channex property UUID to a Yalıhan listing and manages canary disposition';

    public function handle(): int
    {
        $propertyUuid = $this->option('property-uuid');
        $listingId    = (int) $this->option('listing-id');
        $platform     = $this->option('platform');
        $cleanCanary  = (bool) $this->option('clean-canary');

        $this->info("🚀 Yalıhan OS — Real Channex Property Activation Gate");
        $this->line("-----------------------------------------------------------");

        // 1. Canary Disposition
        if ($cleanCanary) {
            $canaryRes = PropertyReservation::withoutGlobalScopes()
                ->where('external_reservation_id', 'RES-CHNX-PILOT-001')
                ->first();

            if ($canaryRes) {
                DB::transaction(function () use ($canaryRes) {
                    PropertyReadiness::withoutGlobalScopes()->where('reservation_id', $canaryRes->id)->delete();
                    Gorev::where('reservation_id', $canaryRes->id)->delete();
                    $canaryRes->delete();
                });
                $this->info("🧹 Synthetic Canary Reservation #1, Readiness #1, and Gorev #1 safely cleaned.");
            } else {
                $this->info("ℹ️ No synthetic canary reservation found.");
            }
        }

        // 2. Listing and Tenant Verification
        $listing = Ilan::withoutGlobalScopes()->find($listingId);
        if (!$listing) {
            $this->error("❌ Listing ID {$listingId} not found.");
            return 1;
        }

        $tenant = Tenant::find($listing->tenant_id);
        $this->info("✅ Target Listing: [ID: {$listing->id}] '{$listing->baslik}'");
        $this->info("✅ Tenant:         [ID: {$tenant?->id}] {$tenant?->name} ({$tenant?->domain})");

        // 3. Property UUID Mapping
        if (empty($propertyUuid)) {
            $this->warn("⚠️ No --property-uuid provided. Showing current mapping status:");
            $currentSync = IlanTakvimSync::withoutGlobalScopes()
                ->where('ilan_id', $listingId)
                ->first();

            if ($currentSync) {
                $this->line("   Current External Listing ID: {$currentSync->external_listing_id}");
                $this->line("   Platform:                    {$currentSync->platform}");
                $this->line("   Sync Active:                 " . ($currentSync->is_sync_active ? 'YES' : 'NO'));
            }
            return 0;
        }

        // Apply Real Property UUID Mapping
        $sync = IlanTakvimSync::withoutGlobalScopes()->updateOrCreate(
            ['ilan_id' => $listingId],
            [
                'platform'            => $platform,
                'external_listing_id' => $propertyUuid,
                'is_sync_active'      => true,
                'auto_sync'           => true,
            ]
        );

        $this->info("✅ Real Property UUID Mapping Applied:");
        $this->line("   - Listing ID:           {$listingId}");
        $this->line("   - Channex Property UUID: {$propertyUuid}");
        $this->line("   - Platform:             {$platform}");
        $this->line("   - Inbound Webhook URL:  https://api.yalihanemlak.com.tr/api/v1/webhook/channex");
        $this->line("-----------------------------------------------------------");
        $this->info("🎯 Ready for real OTA booking ingestion.");

        return 0;
    }
}
