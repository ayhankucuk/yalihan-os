<?php

namespace Tests\Feature\Wizard;

use App\Domain\Ilan\Events\WizardStepCompleted;
use App\Domain\Ilan\Events\WizardSubmitted;
use App\Domain\Ilan\Services\WizardSchemaResolver;
use App\Domain\Ilan\Services\WizardSessionManager;
use App\Domain\Ilan\Services\WizardStepExecutor;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\User;
use App\Models\YayinTipiSablonu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WizardDomainRefactorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private IlanKategori $kategori;
    private YayinTipiSablonu $sablon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'tenant_id' => 1,
        ]);

        $this->kategori = IlanKategori::factory()->create([
            'name' => 'Konut',
            'slug' => 'konut',
        ]);

        $this->sablon = YayinTipiSablonu::factory()->create([
            'ad' => 'Satılık',
            'slug' => 'satilik',
        ]);
    }

    public function test_wizard_session_manager_manages_state_and_locks(): void
    {
        $manager = app(WizardSessionManager::class);

        $state = $manager->getSessionState($this->user->id, 999);
        $this->assertEquals(1, $state['current_step']);
        $this->assertEquals(1, $state['lock_version']);

        $advanced = $manager->advanceStep($this->user->id, 999, 1, ['baslik' => 'Test Ilan'], 1);
        $this->assertEquals(2, $advanced['current_step']);
        $this->assertEquals(2, $advanced['lock_version']);
        $this->assertContains(1, $advanced['completed_steps']);

        // Test optimistic lock conflict
        $this->expectException(\App\Domain\Ilan\Exceptions\ConcurrentModificationException::class);
        $manager->advanceStep($this->user->id, 999, 2, ['fiyat' => 1000], 1); // expected 1 but current is 2
    }

    public function test_wizard_schema_resolver_returns_step_definitions(): void
    {
        $resolver = app(WizardSchemaResolver::class);

        $stepDef = $resolver->resolveStepDefinition(1, $this->kategori->id, $this->sablon->id);

        $this->assertEquals(1, $stepDef['step']);
        $this->assertEquals($this->kategori->id, $stepDef['kategori_id']);
        $this->assertEquals('Konut', $stepDef['kategori_adi']);
        $this->assertEquals('Satılık', $stepDef['sablon_adi']);
        $this->assertFalse($stepDef['is_dynamic']);
    }

    public function test_wizard_step_executor_persists_and_fires_events(): void
    {
        Event::fake([
            WizardStepCompleted::class,
            WizardSubmitted::class,
        ]);

        $executor = app(WizardStepExecutor::class);

        // Execute step 1: create new listing
        $result = $executor->executeStep($this->user->id, 1, [
            'tenant_id' => 1,
            'kategori_id' => $this->kategori->id,
            'baslik' => 'Domain Refactor Villa',
            'aciklama' => 'Lüks gayrimenkul açıklaması',
            'fiyat' => 15000000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'taslak',
            'user_id' => $this->user->id,
        ]);

        $this->assertTrue($result['success']);
        $ilanId = $result['ilan_id'];
        $this->assertNotNull($ilanId);

        Event::assertDispatched(WizardStepCompleted::class, function ($event) use ($ilanId) {
            return $event->ilan->id === $ilanId && $event->completedStep === 1;
        });

        // Submit wizard
        $submitResult = $executor->submitWizard($this->user->id, $ilanId, [
            'yayin_durumu' => 'taslak',
        ]);

        $this->assertTrue($submitResult['success']);
        $this->assertEquals(\App\Enums\IlanDurumu::TASLAK, $submitResult['ilan']->fresh()->yayin_durumu);

        Event::assertDispatched(WizardSubmitted::class, function ($event) use ($ilanId) {
            return $event->ilan->id === $ilanId;
        });
    }
}
