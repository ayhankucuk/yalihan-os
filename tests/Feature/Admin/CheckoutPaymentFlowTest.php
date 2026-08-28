<?php

namespace Tests\Feature\Admin;

use App\Models\Ilan;
use App\Models\Payment;
use App\Models\PropertyReservation;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\ValueObjects\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * CheckoutPaymentFlowTest — Checkout / Ödeme Akışı Testleri
 *
 * CHECKOUT/ÖDEME AKIŞI — IMPLEMENTATION
 *
 * Kapsam:
 * - Checkout sayfası rezervasyon özeti + ödeme geçmişi yüklenir.
 * - Ödeme kaydı oluşturma (pending).
 * - Manuel onay (paid) → rezervasyon finansal_durum güncellenir.
 * - Başarısız işaretleme (failed).
 * - Tenant izolasyonu.
 * - Idempotency (aynı ödemenin iki kez kaydedilmemesi).
 * - Rezervasyon–ödeme eşleşme guard'ı.
 */
class CheckoutPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $adminA;
    private User $adminB;
    private Ilan $ilanA;
    private Ilan $ilanB;

    protected function setUp(): void
    {
        parent::setUp();

        // 🛡️ Cache flush: SetTenantContext middleware caches tenant models via
        // Cache::remember("tenant:{id}"). RefreshDatabase does NOT clear the cache,
        // so a stale tenant from a previous test could leak into this test's
        // tenant context resolution. Flush to guarantee correct tenant isolation.
        \Illuminate\Support\Facades\Cache::flush();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->tenantA = Tenant::firstOrCreate(['domain' => 'tenant-checkout-a.local'], ['name' => 'Tenant Checkout A']);
        $this->tenantB = Tenant::firstOrCreate(['domain' => 'tenant-checkout-b.local'], ['name' => 'Tenant Checkout B']);

        // 🛡️ Tenant context: TestCase::injectDefaultTenantContext() sets the singleton
        // to the FIRST tenant in the DB. We must override it to tenantA so that the
        // TenantScope (applied during route model binding) resolves the correct tenant.
        app(\App\Services\SaaS\TenantContextService::class)->setTenant($this->tenantA);

        $this->adminA = User::factory()->create(['tenant_id' => $this->tenantA->id]);
        $this->adminA->assignRole($adminRole);

        $this->adminB = User::factory()->create(['tenant_id' => $this->tenantB->id]);
        $this->adminB->assignRole($adminRole);

        $this->ilanA = Ilan::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Bodrum Luxury Villa Checkout A',
        ]);
        $this->ilanB = Ilan::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'baslik' => 'Bodrum Luxury Villa Checkout B',
        ]);
    }

    private function makeReservation(Ilan $ilan, array $overrides = []): PropertyReservation
    {
        return PropertyReservation::factory()->forIlan($ilan)->create(array_merge([
            'reservation_state' => 'confirmed',
            'finansal_durum' => TransactionStatus::PENDING,
            'total_amount' => 5000.00,
            'currency' => 'TRY',
        ], $overrides));
    }

    /**
     * Checkout sayfası rezervasyon özeti + ödeme geçmişi ile yüklenir.
     */
    public function test_checkout_page_loads_with_reservation_summary(): void
    {
        $reservation = $this->makeReservation($this->ilanA);

        $response = $this->actingAs($this->adminA)
            ->get(route('admin.ilanlar.checkout.show', [$this->ilanA, $reservation]));

        $response->assertOk();
        $response->assertSee('Checkout / Ödeme');
        $response->assertSee('Rezervasyon Özeti');
        $response->assertSee('Ödeme Geçmişi');
        $response->assertSee('5.000,00');
    }

    /**
     * Ödeme kaydı oluşturulur (pending).
     */
    public function test_payment_record_is_created_as_pending(): void
    {
        $reservation = $this->makeReservation($this->ilanA);

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.ilanlar.checkout.store', [$this->ilanA, $reservation]), [
                'amount' => 2000.00,
                'currency' => 'TRY',
                'payment_method' => 'mock',
                'reference' => 'REF-001',
                'notes' => 'İlk ödeme',
            ]);

        $response->assertRedirect(route('admin.ilanlar.checkout.show', [$this->ilanA, $reservation]));

        $this->assertDatabaseHas('payments', [
            'tenant_id' => $this->tenantA->id,
            'reservation_id' => $reservation->id,
            'amount' => 2000.00,
            'status' => TransactionStatus::PENDING,
            'payment_method' => 'mock',
        ]);
    }

    /**
     * Manuel onay: ödeme paid olur, rezervasyon finansal_durum güncellenir.
     */
    public function test_approve_payment_marks_paid_and_updates_reservation(): void
    {
        $reservation = $this->makeReservation($this->ilanA);
        $payment = Payment::create([
            'tenant_id' => $this->tenantA->id,
            'ulke_id' => 1,
            'reservation_id' => $reservation->id,
            'amount' => 2000.00,
            'currency' => 'TRY',
            'payment_method' => 'mock',
            'status' => TransactionStatus::PENDING,
            'recorded_by' => $this->adminA->id,
        ]);

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.ilanlar.checkout.approve', [$this->ilanA, $reservation, $payment]));

        $response->assertRedirect(route('admin.ilanlar.checkout.show', [$this->ilanA, $reservation]));

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => TransactionStatus::PAID,
            'verified_by' => $this->adminA->id,
        ]);

        $this->assertDatabaseHas('property_reservations', [
            'id' => $reservation->id,
            'finansal_durum' => TransactionStatus::PAID,
        ]);
    }

    /**
     * Ödeme başarısız olarak işaretlenir.
     */
    public function test_fail_payment_marks_failed(): void
    {
        $reservation = $this->makeReservation($this->ilanA);
        $payment = Payment::create([
            'tenant_id' => $this->tenantA->id,
            'ulke_id' => 1,
            'reservation_id' => $reservation->id,
            'amount' => 2000.00,
            'currency' => 'TRY',
            'payment_method' => 'mock',
            'status' => TransactionStatus::PENDING,
            'recorded_by' => $this->adminA->id,
        ]);

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.ilanlar.checkout.fail', [$this->ilanA, $reservation, $payment]), [
                'reason' => 'Banka onayı alınamadı',
            ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => TransactionStatus::FAILED,
        ]);
    }

    /**
     * Tenant izolasyonu: farklı tenant ödemesine erişilemez.
     */
    public function test_tenant_isolation_blocks_cross_tenant_payment(): void
    {
        $reservationA = $this->makeReservation($this->ilanA);
        $paymentA = Payment::create([
            'tenant_id' => $this->tenantA->id,
            'ulke_id' => 1,
            'reservation_id' => $reservationA->id,
            'amount' => 2000.00,
            'currency' => 'TRY',
            'payment_method' => 'mock',
            'status' => TransactionStatus::PENDING,
            'recorded_by' => $this->adminA->id,
        ]);

        // Tenant B, Tenant A'nın ödemesini onaylamaya çalışır.
        $response = $this->actingAs($this->adminB)
            ->post(route('admin.ilanlar.checkout.approve', [$this->ilanA, $reservationA, $paymentA]));

        // 🛡️ Tenant izolasyonu: Tenant B, Tenant A'nın ilanına erişemez.
        // guardTenantAccess() deterministik olarak 403 döndürür (ilan tenant eşleşmez).
        // NOT: 500 kabul edilmez — 500 güvenli tenant izolasyonu kanıtı değildir.
        $response->assertForbidden();

        $this->assertDatabaseHas('payments', [
            'id' => $paymentA->id,
            'status' => TransactionStatus::PENDING,
        ]);
    }

    /**
     * Idempotency: aynı idempotency_key ile ikinci kayıt oluşturulmaz.
     */
    public function test_idempotency_prevents_duplicate_payment(): void
    {
        $reservation = $this->makeReservation($this->ilanA);

        $this->actingAs($this->adminA)
            ->post(route('admin.ilanlar.checkout.store', [$this->ilanA, $reservation]), [
                'amount' => 2000.00,
                'currency' => 'TRY',
                'payment_method' => 'mock',
                'idempotency_key' => 'dup-key-1',
            ]);

        $this->actingAs($this->adminA)
            ->post(route('admin.ilanlar.checkout.store', [$this->ilanA, $reservation]), [
                'amount' => 2000.00,
                'currency' => 'TRY',
                'payment_method' => 'mock',
                'idempotency_key' => 'dup-key-1',
            ]);

        $this->assertSame(1, Payment::query()
            ->where('tenant_id', $this->tenantA->id)
            ->where('reservation_id', $reservation->id)
            ->where('idempotency_key', 'dup-key-1')
            ->count());
    }

    /**
     * Rezervasyon–ödeme eşleşme guard: ödeme başka rezervasyona aitse 404.
     */
    public function test_payment_not_belonging_to_reservation_is_rejected(): void
    {
        $reservationA = $this->makeReservation($this->ilanA);
        $reservationB = $this->makeReservation($this->ilanA);

        $paymentForB = Payment::create([
            'tenant_id' => $this->tenantA->id,
            'ulke_id' => 1,
            'reservation_id' => $reservationB->id,
            'amount' => 2000.00,
            'currency' => 'TRY',
            'payment_method' => 'mock',
            'status' => TransactionStatus::PENDING,
            'recorded_by' => $this->adminA->id,
        ]);

        // reservationA üzerinden paymentB onaylanmaya çalışılır → 404.
        $response = $this->actingAs($this->adminA)
            ->post(route('admin.ilanlar.checkout.approve', [$this->ilanA, $reservationA, $paymentForB]));

        $response->assertNotFound();

        $this->assertDatabaseHas('payments', [
            'id' => $paymentForB->id,
            'status' => TransactionStatus::PENDING,
        ]);
    }
}