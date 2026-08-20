<?php

namespace App\Console\Commands;

use App\DTOs\ChannelManager\ChannexReservationPayload;
use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Models\PropertyReadiness;
use App\Models\PropertyReservation;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\ChannelManager\ChannexReservationIngestService;
use App\Services\Reservation\OperationalExceptionEvaluatorService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * BootstrapProductionPilotCommand — Wave 7 Phase B1 Pilot Onboarding.
 *
 * Idempotently bootstraps:
 * 1. Yalıhan Tenant & Admin User
 * 2. Pilot Luxury Bodrum Villa (ilan)
 * 3. Channex Channel Mapping (ilan_takvim_sync)
 * 4. Controlled Inbound Reservation Ingest & Wave 7 Exception Verification (--test-ingest)
 */
class BootstrapProductionPilotCommand extends Command
{
    protected $signature = 'ops:bootstrap-pilot-villa {--test-ingest : Ingest a controlled pilot reservation and evaluate Wave 7 exceptions}';
    protected $description = 'Idempotently onboard Yalıhan tenant, pilot Bodrum luxury villa, and Channex channel mapping.';

    public function handle(
        ChannexReservationIngestService $ingestService,
        OperationalExceptionEvaluatorService $exceptionEvaluator
    ): int {
        $this->info('🚀 Yalıhan OS — Wave 7 Phase B1: Production Pilot Onboarding');
        $this->line('-----------------------------------------------------------');

        // 1. Tenant Bootstrap
        $tenant = Tenant::firstOrCreate(
            ['domain' => 'yalihan.com.tr'],
            [
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'name' => 'Yalıhan Emlak & Luxury Real Estate',
                'status' => 'active',
            ]
        );
        $this->info("✅ Tenant: [ID: {$tenant->id}] {$tenant->name} ({$tenant->domain})");

        // Ensure Admin User for Tenant
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::firstOrCreate(
            ['email' => 'admin@yalihan.com.tr'],
            [
                'name' => 'Ayhan Küçük (Yalıhan Admin)',
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'tenant_id' => $tenant->id,
            ]
        );
        if (!$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }
        if ($admin->tenant_id !== $tenant->id) {
            $admin->tenant_id = $tenant->id;
            $admin->save();
        }
        $this->info("✅ Admin User: [ID: {$admin->id}] {$admin->name} ({$admin->email})");

        // 2. Pilot Luxury Villa Bootstrap
        $villa = Ilan::withoutGlobalScopes()->where('tenant_id', $tenant->id)
            ->where('baslik', 'Yalıhan Mandarin Oriental Luxury Villa #101')
            ->first();

        if (!$villa) {
            $villa = new Ilan();
            $villa->tenant_id = $tenant->id;
            $villa->baslik = 'Yalıhan Mandarin Oriental Luxury Villa #101';
            $villa->aciklama = 'Göltürkbükü Cennet Koyu mevkii, özel havuzlu ve panoramik deniz manzaralı ultra lüks kiralık yalı villa.';
            $villa->fiyat = 75000;
            $villa->para_birimi = 'EUR';
            $villa->yayin_durumu = 'Yayında';
            $villa->aktiflik_durumu = 1;
            $villa->rental_enabled = true;
            $villa->min_stay_nights = 1;
            $villa->slug = 'yalihan-mandarin-oriental-luxury-villa-101';
            $villa->save();
        } else {
            $villa->rental_enabled = true;
            $villa->min_stay_nights = 1;
            $villa->save();
        }
        $this->info("✅ Pilot Villa: [ID: {$villa->id}] {$villa->baslik} ({$villa->fiyat} {$villa->para_birimi})");

        // 3. Channex Channel Mapping Bootstrap
        $channelSync = IlanTakvimSync::where('ilan_id', $villa->id)
            ->where('platform', 'airbnb')
            ->first();

        if (!$channelSync) {
            $channelSync = new IlanTakvimSync();
            $channelSync->ilan_id = $villa->id;
            $channelSync->platform = 'airbnb';
            $channelSync->external_listing_id = 'CHNX-BODRUM-VILLA-101';
            $channelSync->is_sync_active = true;
            $channelSync->senkron_durumu = 'active';
            $channelSync->save();
        } else {
            $channelSync->is_sync_active = true;
            $channelSync->senkron_durumu = 'active';
            $channelSync->save();
        }
        $this->info("✅ Channel Mapping: [ID: {$channelSync->id}] Platform: {$channelSync->platform} ↔ External Listing: {$channelSync->external_listing_id}");

        // 4. Inbound Reservation Test & Wave 7 Exception Verification (if requested)
        if ($this->option('test-ingest')) {
            $this->line('');
            $this->info('⚡ Executing Controlled Inbound Reservation Ingest Test...');

            $today = Carbon::today()->toDateString();
            $departure = Carbon::today()->addDays(3)->toDateString();
            $externalReservationId = 'RES-CHNX-PILOT-001';

            $payload = new ChannexReservationPayload(
                externalReservationId: $externalReservationId,
                externalListingId: 'CHNX-BODRUM-VILLA-101',
                channel: 'airbnb',
                arrivalDate: $today,
                departureDate: $departure,
                nights: 3,
                guestName: 'Ali & Selin Demir (Pilot Misafir)',
                guestPhone: '+90 532 555 0101',
                guestEmail: 'pilot.guest@example.com',
                adultCount: 4,
                totalPrice: 225000.0,
                currency: 'EUR'
            );

            // Execute canonical ingest chain
            $reservation = $ingestService->ingest($payload, $tenant->id);
            $this->info("✅ Ingest Success: PropertyReservation #[{$reservation->id}] created for {$reservation->guest_name}");
            $this->line("   - Dates: {$reservation->start_date} to {$reservation->end_date} (Nights: {$reservation->nights})");
            $this->line("   - Channel: {$reservation->external_channel} ({$reservation->external_reservation_id})");

            // Verify Readiness Creation
            $readiness = PropertyReadiness::where('reservation_id', $reservation->id)->first();
            if ($readiness) {
                $this->info("✅ Readiness Initialized: [ID: {$readiness->id}] Score: {$readiness->completed_count}/5 (is_ready: " . ($readiness->is_ready ? 'true' : 'false') . ")");
            } else {
                $this->warn('⚠️ Readiness not found for reservation');
            }

            // Verify / Ensure Prep Task Creation
            $prepTask = Gorev::where('reservation_id', $reservation->id)->first();
            if (!$prepTask) {
                $prepTask = app(\App\Services\Reservation\OperationalGorevService::class)
                    ->createPreArrivalTask($reservation, $villa);
            }

            if ($prepTask) {
                $this->info("✅ Operational Task Created: [ID: {$prepTask->id}] '{$prepTask->baslik}' (Status: {$prepTask->gorev_durumu})");
            } else {
                $this->warn('⚠️ Operational task not found for reservation');
            }

            // Wave 7 Operational Exception Intelligence Evaluation
            $reservation->load(['readiness', 'prepTask', 'turnoverTask']);
            $exceptions = $exceptionEvaluator->evaluate($reservation);

            $this->line('');
            $this->info('🧠 Wave 7 Operational Exception Intelligence Evaluation:');
            if (empty($exceptions)) {
                $this->line('   - No operational exceptions found (Villa is 100% ready).');
            } else {
                $this->info('   - Active Exceptions Detected: ' . count($exceptions));
                foreach ($exceptions as $exc) {
                    $badge = $exc->isP0() ? '🔴 [P0 CRITICAL]' : '🟡 [P1 WARNING]';
                    $this->line("     {$badge} {$exc->code}: {$exc->title} — {$exc->reason}");
                }
            }
        }

        $this->line('-----------------------------------------------------------');
        $this->info('🎯 Bootstrap Completed Successfully.');
        return 0;
    }
}
