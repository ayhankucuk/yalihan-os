<?php

namespace Tests\Feature\Rental;

use App\Models\CountryFinancialRule;
use App\Models\FinancialTransaction;
use App\Models\FxRate;
use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Services\CountryFinancialService;
use App\Services\FinancialLedgerService;
use App\Services\FxService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Enterprise Money Core — Full Test Matrix
 *
 * @group skip-until-migration-complete
 * Tests:
 * 1. GBP rezervasyon → TRY ledger doğru
 * 2. EUR rezervasyon → fx_rate_locked kaydedildi
 * 3. TR/GR/UK komisyon hesabı doğru
 * 4. Deposit refund doğru ledger oluşturdu
 * 5. Cancel sonrası finans state doğru
 * 6. Kur değişse bile geçmiş işlem değişmedi
 */
class EnterpriseMoneyTest extends TestCase
{

    protected FxService $fxService;
    protected CountryFinancialService $countryService;
    protected FinancialLedgerService $ledgerService;
    protected Ilan $ilan;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fxService      = app(FxService::class);
        $this->countryService = app(CountryFinancialService::class);
        $this->ledgerService  = app(FinancialLedgerService::class);

        // Seed FX rates
        FxRate::create([
            'from_currency'   => 'TRY',
            'to_currency'     => 'EUR',
            'rate'            => 0.029,
            'aktiflik_durumu' => true,
            'effective_at'    => now(),
        ]);
        FxRate::create([
            'from_currency'   => 'TRY',
            'to_currency'     => 'GBP',
            'rate'            => 0.025,
            'aktiflik_durumu' => true,
            'effective_at'    => now(),
        ]);

        // Seed country rules
        CountryFinancialRule::create([
            'country_code'           => 'TR',
            'country_name'           => 'Türkiye',
            'rental_commission_rate' => 0.08,
            'sales_commission_rate'  => 0.03,
            'advisory_fee_rate'      => 0.00,
            'tax_rate'               => 0.00,
            'aktiflik_durumu'        => true,
        ]);
        CountryFinancialRule::create([
            'country_code'           => 'GR',
            'country_name'           => 'Yunanistan',
            'rental_commission_rate' => 0.05,
            'sales_commission_rate'  => 0.03,
            'advisory_fee_rate'      => 0.02,
            'tax_rate'               => 0.00,
            'aktiflik_durumu'        => true,
        ]);
        CountryFinancialRule::create([
            'country_code'           => 'UK',
            'country_name'           => 'İngiltere',
            'rental_commission_rate' => 0.06,
            'sales_commission_rate'  => 0.025,
            'advisory_fee_rate'      => 0.00,
            'tax_rate'               => 0.00,
            'aktiflik_durumu'        => true,
        ]);

        $this->user = User::factory()->create();
        $this->ilan = Ilan::factory()->create([
            'user_id'        => $this->user->id,
            'rental_enabled' => true,
            'min_stay_nights'=> 1,
            'fiyat'          => 1000,
        ]);
    }

    protected function createReservation(float $totalAmount = 5000): PropertyReservation
    {
        return PropertyReservation::create([
            'property_id'       => $this->ilan->id,
            'start_date'        => '2026-08-01',
            'end_date'          => '2026-08-06',
            'nights'            => 5,
            'guest_name'        => 'Money Test Guest',
            'reservation_state' => 'confirmed',
            'finansal_durum'    => 'draft',
            'total_amount'      => $totalAmount,
            'confirmed_at'      => now(),
        ]);
    }

    /**
     * Test 1: GBP reservation → TRY ledger recorded correctly
     */
    public function test_1_gbp_reservation_try_ledger_correct(): void
    {
        $reservation    = $this->createReservation(5000); // 5000 TRY
        $gbpRate        = 0.025; // 1 TRY = 0.025 GBP
        $expectedGBP    = round(5000 * $gbpRate, 2); // 125.00

        $tx = $this->ledgerService->recordRentalTransaction(
            $this->ilan->id,
            $reservation->id,
            5000,
            'TR',
            'GBP',
            $this->user->id
        );

        $this->assertEquals(5000.0, $tx->base_amount, "Base amount must always be TRY.");
        $this->assertEquals('TRY', $tx->base_currency);
        $this->assertEquals('GBP', $tx->display_currency);
        $this->assertEquals($expectedGBP, $tx->display_amount);
        $this->assertEquals($gbpRate, $tx->fx_rate_locked);
        $this->assertEquals('kira', $tx->islem_tipi);
        $this->assertEquals('settled', $tx->islem_durumu);
    }

    /**
     * Test 2: EUR reservation → fx_rate_locked must be persisted
     */
    public function test_2_eur_reservation_fx_rate_locked_persisted(): void
    {
        $reservation = $this->createReservation(10000); // 10000 TRY
        $eurRate     = 0.029;

        $tx = $this->ledgerService->recordRentalTransaction(
            $this->ilan->id,
            $reservation->id,
            10000,
            'GR',
            'EUR',
            $this->user->id
        );

        // Must be in DB and fx_rate_locked must be set
        $fresh = FinancialTransaction::find($tx->id);
        $this->assertNotNull($fresh->fx_rate_locked, "fx_rate_locked must be persisted in DB.");
        $this->assertEquals($eurRate, $fresh->fx_rate_locked);
        $this->assertEquals(round(10000 * $eurRate, 2), $fresh->display_amount);
    }

    /**
     * Test 3: TR/GR/UK commission rates all correct from DB
     */
    public function test_3_country_commission_correct_from_db(): void
    {
        $subtotal = 10000.0;

        // TR: 8%
        $tr = $this->countryService->calculateRentalCommission($subtotal, 'TR');
        $this->assertEquals(800.0, $tr['amount_try']);

        // GR: 5% + 2% advisory = 7% total
        $gr          = $this->countryService->calculateRentalCommission($subtotal, 'GR');
        $grAdvisory  = $this->countryService->calculateAdvisory($subtotal, 'GR');
        $this->assertEquals(500.0, $gr['amount_try']);
        $this->assertEquals(200.0, $grAdvisory['amount_try']);

        // UK: 6%
        $uk = $this->countryService->calculateRentalCommission($subtotal, 'UK');
        $this->assertEquals(600.0, $uk['amount_try']);

        // Full breakdown for GR
        $breakdown = $this->countryService->rentalBreakdown($subtotal, 'GR');
        $this->assertEquals(10700.0, $breakdown['total_try']); // 10000 + 500 + 200
    }

    /**
     * Test 4: Deposit payment + refund creates correct ledger entries
     */
    public function test_4_deposit_refund_creates_correct_ledger(): void
    {
        $reservation = $this->createReservation();

        // Update deposit amount on reservation
        $reservation->update(['depozito_tutari' => 2000, 'depozito_durumu' => 'required']);

        // Pay deposit
        $depositTx = $this->ledgerService->recordDepositTransaction(
            $this->ilan->id,
            $reservation->id,
            2000,
            $this->user->id
        );

        $this->assertEquals('depozito', $depositTx->islem_tipi);
        $this->assertEquals(2000.0, $depositTx->base_amount);
        $reservation->refresh();
        $this->assertEquals('paid', $reservation->depozito_durumu);

        // Refund deposit
        $refundTx = $this->ledgerService->recordDepositRefund(
            $this->ilan->id,
            $reservation->id,
            2000,
            'Misafir ayrıldı, depozito iadesi',
            $this->user->id
        );

        $this->assertEquals('iade', $refundTx->islem_tipi);
        $this->assertEquals('refunded', $refundTx->islem_durumu);
        $reservation->refresh();
        $this->assertEquals('refunded', $reservation->depozito_durumu);

        // Ledger must have 2 records: deposit + refund
        $ledger = $this->ledgerService->getReservationLedger($reservation->id);
        $this->assertCount(2, $ledger);
    }

    /**
     * Test 5: Cancel → finansal_durum transitions to 'cancelled'
     */
    public function test_5_cancel_transitions_financial_state(): void
    {
        $reservation = $this->createReservation();
        $this->assertEquals('draft', $reservation->finansal_durum);

        // Confirm financial state
        $this->ledgerService->transitionToConfirmed($reservation->id);
        $reservation->refresh();
        $this->assertEquals('confirmed', $reservation->finansal_durum);

        // Cancel
        $this->ledgerService->transitionToCancelled($reservation->id);
        $reservation->refresh();
        $this->assertEquals('cancelled', $reservation->finansal_durum);
    }

    /**
     * Test 6: Changing FX rate does NOT change past transactions (historical integrity)
     */
    public function test_6_historical_fx_rate_immutable(): void
    {
        $reservation = $this->createReservation(8000);

        // Record transaction at current GBP rate (0.025)
        $tx = $this->ledgerService->recordRentalTransaction(
            $this->ilan->id,
            $reservation->id,
            8000,
            'TR',
            'GBP',
            $this->user->id
        );

        $originalLockedRate    = $tx->fx_rate_locked;
        $originalDisplayAmount = $tx->display_amount;

        // Now "update" the FX rate in the DB (simulates rate change)
        FxRate::where('to_currency', 'GBP')->update(['rate' => 0.040]); // Rate jumped significantly
        Cache::forget('fx_rate.TRY.GBP'); // Clear cache

        // Reload the transaction — it must NOT change
        $fresh = FinancialTransaction::find($tx->id);
        $this->assertEquals($originalLockedRate, $fresh->fx_rate_locked,
            "Historical FX rate must be immutable — past transactions must not change when rate changes."
        );
        $this->assertEquals($originalDisplayAmount, $fresh->display_amount,
            "Historical display_amount must be immutable."
        );
    }
}
