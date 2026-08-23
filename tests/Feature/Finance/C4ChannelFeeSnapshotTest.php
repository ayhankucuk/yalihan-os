<?php

namespace Tests\Feature\Finance;

use App\Enums\ChannelFeeBearer;
use App\Enums\ChannelFeeSource;
use App\Enums\ManagementModel;
use App\Models\Ilan;
use App\Models\LedgerAccount;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Services\Finance\PayoutReadinessService;
use App\Services\FinancialLedgerService;
use App\Services\ReservationService;
use App\ValueObjects\TransactionStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C4.1: Channel Fee Snapshot & Policy Foundation Certification Tests
 *
 * Scope:
 *   - Channel fee snapshot fields added to reservation
 *   - OWNER_BORNE model: fee deducted before owner payable
 *   - DO NOT GUESS: UNKNOWN source → payout blocked
 *   - C4.1 Invariant 1: owner_payable = gross - channel_fee - yalihan_commission
 *   - C4.1 Invariant 2: UNKNOWN channel_fee → payout readiness BLOCKED
 *
 * Required coverage:
 *  1.  OWNER_BORNE + PROVIDER_REPORTED amount → payout ready, correct net
 *  2.  OWNER_BORNE + PROPERTY_CONFIG → payout blocked (needs C5 reconciliation)
 *  3.  OWNER_BORNE + EXPLICIT_RULE → payout blocked (needs C5 reconciliation)
 *  4.  OWNER_BORNE + UNKNOWN source → payout BLOCKED (Invariant 2)
 *  5.  OWNER_BORNE + NULL amount (no bearer) → payout BLOCKED
 *  6.  YALIHAN_BORNE → payout ready regardless of channel fee (fee is Yalihan's cost)
 *  7.  COMMISSION_SHARE + amount known → payout ready with net computation
 *  8.  Full formula: Gross 100K, channel 15.5K, Yalihan 15% → net 69.5K
 *  9.  YALIHAN_BORNE: owner gets same as C3.2 (no channel deduction)
 * 10.  UNKNOWN bearer + channel fee amount → payout BLOCKED (source UNKNOWN)
 * 11.  PayoutReadyEvent contains correct channel fee snapshot
 * 12.  getAwaitingChannelFeeReconciliation returns correctly blocked reservations
 *
 * SAAB Decision: C4_POLICY_LOCKED_OWNER_BORNE
 * Baseline: e9b3111
 */
class C4ChannelFeeSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $reservationService;

    protected FinancialLedgerService $ledgerService;

    protected User $user;

    protected Ilan $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reservationService = app(ReservationService::class);
        $this->ledgerService = app(FinancialLedgerService::class);
        $this->user = User::factory()->create();

        $this->ilan = Ilan::factory()->create([
            'rental_enabled' => true,
            'min_stay_nights' => 1,
            'fiyat' => 5000.00,
            'para_birimi' => 'TRY',
            'management_model' => ManagementModel::FULL_MANAGEMENT,
        ]);
    }

    /**
     * Create a confirmed reservation with channel fee for testing.
     * Uses ReservationService to create reservation with correct tenant_id, then sets
     * finansal_durum = CONFIRMED and channel fee fields directly.
     *
     * Note: processCompletion() is NOT called because it requires ledger accounts
     * to exist. C4.1 tests focus on the service-level channel fee gating logic,
     * not ledger entry creation (which is covered by C3.2 tests).
     *
     * C4.2 UPDATE: When channel fee is sufficient (PROVIDER_REPORTED + amount not null),
     * recordChannelFeeAccrual is called to create the required ledger entries.
     * PayoutReadinessService now requires ledger evidence per C4.2 gate lock.
     */
    private function createReservationWithChannelFee(
        ?float $channelFeeAmount,
        string $channelFeeSource,
        string $channelFeeBearer,
        bool $channelFeeVerified = false,
        float $gross = 100000.00,
    ): PropertyReservation {
        // Create ilan with rental_enabled=true so ReservationService accepts it
        $ilan = Ilan::factory()->create([
            'rental_enabled' => true,
            'min_stay_nights' => 1,
            'fiyat' => 5000.00,
            'para_birimi' => 'TRY',
            'management_model' => ManagementModel::FULL_MANAGEMENT,
        ]);
        $ilan->tenant_id = 1;
        $ilan->saveQuietly();

        // Create reservation via ReservationService (handles reservation_state = 'confirmed')
        $startDate = Carbon::tomorrow()->format('Y-m-d');
        $endDate = Carbon::tomorrow()->addDays(2)->format('Y-m-d');
        $reservation = $this->reservationService->createReservation(
            $ilan->id, $startDate, $endDate,
            ['guest_name' => 'Test Guest', 'total_amount' => $gross, 'currency' => 'TRY'],
            $this->user->id
        );

        // Commission rate snapshot: FULL_MANAGEMENT = 0.1500
        $snapshotRate = match (true) {
            $ilan->custom_commission_rate !== null => (float) $ilan->custom_commission_rate,
            $ilan->management_model === ManagementModel::FULL_MANAGEMENT => 0.1500,
            $ilan->management_model === ManagementModel::CHECKIN_CHECKOUT => 0.1000,
            default => 0.0000,
        };

        // Mark as CONFIRMED (finansal_durum) for payout readiness
        // external_channel: set to 'booking_com' for OTA tests to correctly classify as CASE B.
        // C4.2 uses external_channel to determine Direct vs OTA (CASE A vs CASE B/C).
        $reservation->update([
            'finansal_durum' => TransactionStatus::CONFIRMED,
            'completed_at' => now(),
            'checked_out_at' => now(),
            'commission_rate_snapshot' => $snapshotRate,
            'channel_fee_amount' => $channelFeeAmount,
            'channel_fee_currency' => 'TRY',
            'channel_fee_rate' => $channelFeeAmount !== null ? $channelFeeAmount / $gross : null,
            'channel_fee_source' => $channelFeeSource,
            'channel_fee_bearer' => $channelFeeBearer,
            'channel_fee_is_verified' => $channelFeeVerified,
            'channel_fee_captured_at' => $channelFeeVerified ? now() : null,
            'external_channel' => 'booking_com',
        ]);

        $reservation->refresh();

        // C4.2: When channel fee is sufficient, record ledger accrual entries.
        // PayoutReadinessService now requires ledger evidence as the payout readiness gate.
        $bearerRequiresChannelFee = ($channelFeeBearer === ChannelFeeBearer::OWNER_BORNE->value
            || $channelFeeBearer === ChannelFeeBearer::COMMISSION_SHARE->value);
        $sourceIsSufficient = in_array($channelFeeSource, [
            ChannelFeeSource::PROVIDER_REPORTED->value,
        ], true);
        $amountIsKnown = $channelFeeAmount !== null && $bearerRequiresChannelFee;

        if ($bearerRequiresChannelFee && $sourceIsSufficient && $amountIsKnown) {
            // recordChannelFeeAccrual requires Konaklama / Kira Gelirleri account.
            // Create it if not exists (it is created by recordReservationInitialBooking).
            LedgerAccount::firstOrCreate(
                ['name' => 'Konaklama / Kira Gelirleri'],
                ['tip' => 'gelir', 'aktiflik_durumu' => true, 'display_order' => 20, 'currency' => 'TRY']
            );

            try {
                $this->ledgerService->recordChannelFeeAccrual($reservation);
            } catch (\Throwable $e) {
                $this->fail("recordChannelFeeAccrual failed: {$e->getMessage()}");
            }
        }

        return $reservation;
    }

    // ─────────────────────────────────────────────────────────────────
    // C4.1 Invariant 1: owner_payable = gross - channel_fee - yalihan_commission
    // ─────────────────────────────────────────────────────────────────

    public function test_owner_borne_provider_reported_payout_ready_with_correct_net(): void
    {
        $reservation = $this->createReservationWithChannelFee(
            15500.00,
            ChannelFeeSource::PROVIDER_REPORTED->value,
            ChannelFeeBearer::OWNER_BORNE->value,
            true
        );

        $service = new PayoutReadinessService;
        $result = $service->getPayoutReadyReservations(1);

        $this->assertNotEmpty($result, 'Provider reported OWNER_BORNE should be payout ready');
        $item = $result[0];

        // Invariant 1: 100000 - 15500 - 15000 = 69500
        $this->assertEquals(69500.00, $item['owner_entitlement_after_channel']);
        $this->assertEquals(100000.00, $item['gross_amount']);
        $this->assertEquals(15500.00, $item['channel_fee_amount']);
        $this->assertEquals(ChannelFeeBearer::OWNER_BORNE->value, $item['channel_fee_bearer']);
        $this->assertEquals('ready_for_payout', $item['status']);
    }

    public function test_full_formula_100k_gross_15500_channel_15pct_yalihan_net_69500(): void
    {
        $reservation = $this->createReservationWithChannelFee(
            15500.00,
            ChannelFeeSource::PROVIDER_REPORTED->value,
            ChannelFeeBearer::OWNER_BORNE->value,
            true
        );

        $service = new PayoutReadinessService;
        $result = $service->getPayoutReadyReservations(1);

        $this->assertNotEmpty($result);
        $item = $result[0];

        // SAAB example:
        // Booking Gross: 100,000 TL | OTA / Channel Fee: -15,500 TL | Yalihan %15: -15,000 TL | Owner: 69,500 TL
        $this->assertEquals(100000.00, $item['gross_amount']);
        $this->assertEquals(15500.00, $item['channel_fee_amount']);
        $this->assertEquals(15000.00, $item['commission_amount']);
        $this->assertEquals(69500.00, $item['owner_entitlement_after_channel']);
        $this->assertEquals(85000.00, $item['owner_entitlement']); // C3.2: gross - commission
    }

    // ─────────────────────────────────────────────────────────────────
    // C4.1 Invariant 2: UNKNOWN → payout BLOCKED
    // ─────────────────────────────────────────────────────────────────

    public function test_owner_borne_unknown_source_blocked(): void
    {
        $this->createReservationWithChannelFee(
            null,
            ChannelFeeSource::UNKNOWN->value,
            ChannelFeeBearer::OWNER_BORNE->value
        );

        $service = new PayoutReadinessService;
        $result = $service->getPayoutReadyReservations(1);

        $this->assertEmpty($result, 'UNKNOWN source should BLOCK payout readiness');
    }

    public function test_owner_borne_null_amount_blocked(): void
    {
        $this->createReservationWithChannelFee(
            null,
            ChannelFeeSource::PROPERTY_CONFIG->value,
            ChannelFeeBearer::OWNER_BORNE->value
        );

        $service = new PayoutReadinessService;
        $result = $service->getPayoutReadyReservations(1);

        $this->assertEmpty($result, 'OWNER_BORNE without amount should BLOCK payout');
    }

    public function test_owner_borne_property_config_blocked(): void
    {
        $this->createReservationWithChannelFee(
            15000.00,
            ChannelFeeSource::PROPERTY_CONFIG->value,
            ChannelFeeBearer::OWNER_BORNE->value,
            false
        );

        $service = new PayoutReadinessService;
        $result = $service->getPayoutReadyReservations(1);

        $this->assertEmpty($result, 'PROPERTY_CONFIG should block payout until C5 reconciliation');
    }

    public function test_owner_borne_explicit_rule_blocked(): void
    {
        $this->createReservationWithChannelFee(
            15000.00,
            ChannelFeeSource::EXPLICIT_RULE->value,
            ChannelFeeBearer::OWNER_BORNE->value,
            false
        );

        $service = new PayoutReadinessService;
        $result = $service->getPayoutReadyReservations(1);

        $this->assertEmpty($result, 'EXPLICIT_RULE should block payout until reconciled');
    }

    public function test_owner_borne_provider_reported_verified_payout_ready(): void
    {
        $reservation = $this->createReservationWithChannelFee(
            15000.00,
            ChannelFeeSource::PROVIDER_REPORTED->value,
            ChannelFeeBearer::OWNER_BORNE->value,
            true
        );

        $service = new PayoutReadinessService;
        $result = $service->getPayoutReadyReservations(1);

        $this->assertNotEmpty($result);
        $item = $result[0];
        $this->assertEquals(70000.00, $item['owner_entitlement_after_channel']); // 100K - 15K - 15K
    }

    // ─────────────────────────────────────────────────────────────────
    // YALIHAN_BORNE: channel fee is Yalihan's problem
    // ─────────────────────────────────────────────────────────────────

    public function test_yalihan_borne_payout_ready_regardless_of_channel_fee(): void
    {
        $this->createReservationWithChannelFee(
            null,
            ChannelFeeSource::UNKNOWN->value,
            ChannelFeeBearer::YALIHAN_BORNE->value
        );

        $service = new PayoutReadinessService;
        $result = $service->getPayoutReadyReservations(1);

        $this->assertNotEmpty($result, 'YALIHAN_BORNE should be payout ready even without channel fee');
        $item = $result[0];

        // Owner: gross - yalihan commission (same as C3.2)
        $this->assertEquals(85000.00, $item['owner_entitlement']);
        $this->assertEquals(85000.00, $item['owner_entitlement_after_channel']);
        $this->assertEquals(ChannelFeeBearer::YALIHAN_BORNE->value, $item['channel_fee_bearer']);
    }

    public function test_yalihan_borne_with_known_channel_fee_owner_unaffected(): void
    {
        $this->createReservationWithChannelFee(
            20000.00,
            ChannelFeeSource::PROVIDER_REPORTED->value,
            ChannelFeeBearer::YALIHAN_BORNE->value,
            true
        );

        $service = new PayoutReadinessService;
        $result = $service->getPayoutReadyReservations(1);

        $this->assertNotEmpty($result);
        $item = $result[0];

        // Owner: gross - yalihan commission (channel fee is Yalihan's problem)
        $this->assertEquals(85000.00, $item['owner_entitlement_after_channel']);
        $this->assertEquals(20000.00, $item['channel_fee_amount']);
    }

    // ─────────────────────────────────────────────────────────────────
    // COMMISSION_SHARE
    // ─────────────────────────────────────────────────────────────────

    public function test_commission_share_with_known_channel_fee_payout_ready(): void
    {
        $this->createReservationWithChannelFee(
            10000.00,
            ChannelFeeSource::PROVIDER_REPORTED->value,
            ChannelFeeBearer::COMMISSION_SHARE->value,
            true
        );

        $service = new PayoutReadinessService;
        $result = $service->getPayoutReadyReservations(1);

        $this->assertNotEmpty($result);
        $item = $result[0];
        $this->assertEquals(75000.00, $item['owner_entitlement_after_channel']); // 100K - 10K - 15K
    }

    public function test_commission_share_without_channel_fee_blocked(): void
    {
        $this->createReservationWithChannelFee(
            null,
            ChannelFeeSource::UNKNOWN->value,
            ChannelFeeBearer::COMMISSION_SHARE->value
        );

        $service = new PayoutReadinessService;
        $result = $service->getPayoutReadyReservations(1);

        $this->assertEmpty($result, 'COMMISSION_SHARE without channel fee should be blocked');
    }

    // ─────────────────────────────────────────────────────────────────
    // getAwaitingChannelFeeReconciliation
    // ─────────────────────────────────────────────────────────────────

    public function test_get_awaiting_channel_fee_reconciliation_returns_blocked(): void
    {
        $reservation = $this->createReservationWithChannelFee(
            null,
            ChannelFeeSource::PROPERTY_CONFIG->value,
            ChannelFeeBearer::OWNER_BORNE->value
        );

        // Directly call buildReadinessState via reflection to verify it returns null
        $service = new PayoutReadinessService;
        $reflMethod = new \ReflectionMethod($service, 'buildReadinessState');
        $reflMethod->setAccessible(true);
        $state = $reflMethod->invoke($service, $reservation);
        $this->assertNull($state, 'buildReadinessState should return null for OWNER_BORNE without channel fee amount');

        // getAwaitingChannelFeeReconciliation should include this reservation
        $allAwaiting = $service->getAwaitingChannelFeeReconciliation(1);
        $matching = collect($allAwaiting)->firstWhere('reservation_id', $reservation->id);
        $this->assertNotNull($matching, 'Reservation should appear in awaiting list');
        $this->assertEquals('awaiting_channel_fee_reconciliation', $matching['status']);
    }

    public function test_awaiting_channel_fee_excludes_yalihan_borne(): void
    {
        $this->createReservationWithChannelFee(
            null,
            ChannelFeeSource::UNKNOWN->value,
            ChannelFeeBearer::YALIHAN_BORNE->value
        );

        $service = new PayoutReadinessService;
        $awaiting = $service->getAwaitingChannelFeeReconciliation(1);

        $this->assertEmpty($awaiting, 'YALIHAN_BORNE should not appear in awaiting list');
    }

    public function test_awaiting_channel_fee_excludes_ready_reservations(): void
    {
        $reservation = $this->createReservationWithChannelFee(
            15000.00,
            ChannelFeeSource::PROVIDER_REPORTED->value,
            ChannelFeeBearer::OWNER_BORNE->value,
            true
        );

        // Directly verify buildReadinessState returns ready status
        $service = new PayoutReadinessService;
        $reflMethod = new \ReflectionMethod($service, 'buildReadinessState');
        $reflMethod->setAccessible(true);
        $directState = $reflMethod->invoke($service, $reservation);
        $this->assertNotNull($directState, 'buildReadinessState must return non-null for ready reservation');
        $this->assertEquals('ready_for_payout', $directState['status']);

        // getAwaitingChannelFeeReconciliation should exclude PROVIDER_REPORTED verified
        $awaiting = $service->getAwaitingChannelFeeReconciliation(1);
        $matchingAwaiting = collect($awaiting)->firstWhere('reservation_id', $reservation->id);
        $this->assertNull($matchingAwaiting, 'PROVIDER_REPORTED verified should not appear in awaiting');
    }

    // ─────────────────────────────────────────────────────────────────
    // No bearer set: default to requiring channel fee
    // ─────────────────────────────────────────────────────────────────

    public function test_null_bearer_default_requires_channel_fee(): void
    {
        $ilan = Ilan::factory()->create([
            'rental_enabled' => true, 'min_stay_nights' => 1, 'fiyat' => 5000,
        ]);
        $ilan->tenant_id = 1;
        $ilan->saveQuietly();

        $res = $this->reservationService->createReservation(
            $ilan->id, Carbon::tomorrow()->format('Y-m-d'), Carbon::tomorrow()->addDays(2)->format('Y-m-d'),
            ['guest_name' => 'T', 'total_amount' => 100000, 'currency' => 'TRY'],
            $this->user->id
        );
        $res->update([
            'finansal_durum' => TransactionStatus::CONFIRMED,
            'completed_at' => now(),
            'checked_out_at' => now(),
            'channel_fee_amount' => null,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED->value,
            'channel_fee_bearer' => null,
        ]);

        $service = new PayoutReadinessService;
        $result = $service->getPayoutReadyReservations(1);

        $this->assertEmpty($result, 'Null bearer should default to requiring channel fee');
    }

    // ─────────────────────────────────────────────────────────────────
    // Financial state immutability
    // ─────────────────────────────────────────────────────────────────

    public function test_channel_fee_does_not_affect_finansal_durum(): void
    {
        $reservation = $this->createReservationWithChannelFee(
            null,
            ChannelFeeSource::UNKNOWN->value,
            ChannelFeeBearer::OWNER_BORNE->value
        );

        $this->assertEquals(TransactionStatus::CONFIRMED, $reservation->finansal_durum);
    }
}
