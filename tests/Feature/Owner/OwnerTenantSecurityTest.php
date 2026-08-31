<?php

namespace Tests\Feature\Owner;

use App\Models\OwnerLoginToken;
use App\Models\OwnerReportMetric;
use App\Models\OwnerReportRow;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * OwnerTenantSecurityTest
 *
 * SAB Kural 1 (Tenant Isolation) & Security Verification for Owner Portal Auth & Reports.
 *
 * @group owner
 * @group sab
 * @group security
 */
class OwnerTenantSecurityTest extends TestCase
{
    private User $ownerTenant1;
    private User $ownerTenant2;
    private User $ownerNoTenant;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Mail::fake();

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        \Spatie\Permission\Models\Role::findOrCreate('owner', 'web');

        \App\Models\SaaS\Tenant::firstOrCreate(['id' => 1], ['name' => 'Tenant 1', 'domain' => 'tenant-1.local']);
        \App\Models\SaaS\Tenant::firstOrCreate(['id' => 2], ['name' => 'Tenant 2', 'domain' => 'tenant-2.local']);

        $this->ownerTenant1 = User::factory()->create([
            'tenant_id' => 1,
            'email'     => 'owner1@example.com',
        ]);
        $this->ownerTenant1->assignRole('owner');

        $this->ownerTenant2 = User::factory()->create([
            'tenant_id' => 2,
            'email'     => 'owner2@example.com',
        ]);
        $this->ownerTenant2->assignRole('owner');

        $this->ownerNoTenant = User::factory()->create([
            'tenant_id' => null,
            'email'     => 'notenant@example.com',
        ]);
        $this->ownerNoTenant->assignRole('owner');
    }

    // ─── OwnerAuthController Security ────────────────────────────────────────

    /** @test */
    public function send_login_link_rejects_user_without_tenant_id_and_does_not_generate_token(): void
    {
        $response = $this->post(route('owner.auth.send'), [
            'email' => 'notenant@example.com',
        ]);

        $response->assertSessionHas('bilgi');

        // Token asla üretilmemeli (özellikle varsayılan tenant_id=1 verilmemeli)
        $this->assertDatabaseMissing('owner_login_tokens', [
            'user_id' => $this->ownerNoTenant->id,
        ]);
    }

    /** @test */
    public function send_login_link_assigns_exact_user_tenant_id_to_token(): void
    {
        $response = $this->from(route('owner.login'))
            ->post(route('owner.auth.send'), [
                'email' => $this->ownerTenant2->email,
            ]);

        $response->assertRedirect(route('owner.login'));
        $response->assertSessionHas('basarili');

        // Token doğrudan kullanıcının kendi tenant_id'si (2) ile kaydedilmeli
        $this->assertDatabaseHas('owner_login_tokens', [
            'user_id'   => $this->ownerTenant2->id,
            'tenant_id' => 2,
        ]);
    }

    // ─── OwnerReportController Tenant Isolation ──────────────────────────────

    /** @test */
    public function owner_cannot_view_report_rows_from_another_tenant(): void
    {
        // Tenant 1'e ait satır
        OwnerReportRow::create([
            'tenant_id'    => 1,
            'owner_id'     => $this->ownerTenant1->id,
            'ilan_id'      => null,
            'islem_tipi'   => 'kira_odemesi',
            'kayit_tarihi' => now()->toDateString(),
            'tutar'        => 50000,
            'para_birimi'  => 'TRY',
            'aciklama'     => 'Tenant 1 Kira',
        ]);

        // Tenant 2'ye ait satır
        OwnerReportRow::create([
            'tenant_id'    => 2,
            'owner_id'     => $this->ownerTenant2->id,
            'ilan_id'      => null,
            'islem_tipi'   => 'kira_odemesi',
            'kayit_tarihi' => now()->toDateString(),
            'tutar'        => 75000,
            'para_birimi'  => 'TRY',
            'aciklama'     => 'Tenant 2 Kira',
        ]);

        // Owner 1 giriş yaptığında sadece Tenant 1 satırını görmeli
        $response = $this->actingAs($this->ownerTenant1)
            ->get(route('owner.reports.index'));

        $response->assertOk();
        $response->assertSee('Tenant 1 Kira');
        $response->assertDontSee('Tenant 2 Kira');
    }

    /** @test */
    public function report_index_aborts_403_if_authenticated_owner_has_no_tenant_id(): void
    {
        $response = $this->actingAs($this->ownerNoTenant)
            ->get(route('owner.reports.index'));

        $response->assertForbidden();
    }
}
