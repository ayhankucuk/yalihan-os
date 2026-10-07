<?php

namespace Tests\Feature;

use App\Modules\Finans\Models\FinansalIslem;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinanceIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear tenant context
        app(TenantContextService::class)->clearTenant();

        // Manually truncate to bypass SAVEPOINT issues and data pollution
        DB::table('finansal_islemler')->truncate();
        DB::table('komisyonlar')->truncate();
    }

    /**
     * Override global DatabaseTransactions from TestCase to bypass SAVEPOINT errors
     */
    protected function beginDatabaseTransaction()
    {
        // Do nothing - bypass infrastructure transaction wrapping
    }

    /** @test */
    public function it_can_create_a_financial_transaction_using_modular_model()
    {
        $user = User::factory()->create();
        $ilan = Ilan::factory()->create();
        $kisi = Kisi::factory()->create();

        $islem = FinansalIslem::create([
            'ilan_id' => $ilan->id,
            'kisi_id' => $kisi->id,
            'islem_tipi' => 'gelir',
            'miktar' => 1500.50,
            'para_birimi' => 'TRY',
            'islem_statusu' => 'bekliyor',
            'tarih' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('finansal_islemler', [
            'id' => $islem->id,
            'miktar' => 1500.50,
            'islem_statusu' => 'bekliyor',
        ]);
    }

    /** @test */
    public function it_can_approve_a_financial_transaction()
    {
        $approver = User::factory()->create();
        $islem = FinansalIslem::create([
            'islem_tipi' => 'masraf',
            'miktar' => 250,
            'para_birimi' => 'USD',
            'islem_statusu' => 'bekliyor',
            'tarih' => now()->toDateString(),
        ]);

        $islem->onayla($approver->id);

        $this->assertEquals('onaylandi', $islem->fresh()->islem_statusu);
        $this->assertEquals($approver->id, $islem->fresh()->onaylayan_id);
        $this->assertNotNull($islem->fresh()->onay_tarihi);
    }

    /** @test */
    public function it_filters_transactions_by_status_scopes()
    {
        // Create ilan with tenant for proper tenant isolation
        $ilan = Ilan::factory()->create();

        FinansalIslem::withoutGlobalScopes()->create([
            'ilan_id' => $ilan->id,
            'islem_statusu' => 'bekliyor',
            'islem_tipi' => 'gelir',
            'miktar' => 100,
            'tarih' => now(),
        ]);
        FinansalIslem::withoutGlobalScopes()->create([
            'ilan_id' => $ilan->id,
            'islem_statusu' => 'onaylandi',
            'islem_tipi' => 'gelir',
            'miktar' => 200,
            'tarih' => now(),
        ]);
        FinansalIslem::withoutGlobalScopes()->create([
            'ilan_id' => $ilan->id,
            'islem_statusu' => 'tamamlandi',
            'islem_tipi' => 'gelir',
            'miktar' => 300,
            'tarih' => now(),
        ]);

        $this->assertCount(1, FinansalIslem::bekleyen()->get());
        $this->assertCount(1, FinansalIslem::onaylanan()->get());
        $this->assertCount(1, FinansalIslem::tamamlanan()->get());
    }
}
