<?php

namespace Tests\Feature\Wizard;

use App\Domain\Ilan\Events\WizardStepCompleted;
use App\Domain\Ilan\Events\WizardSubmitted;
use App\Domain\Ilan\ValueObjects\GeoCoordinate;
use App\Domain\Ilan\ValueObjects\Money;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\User;
use App\Models\YayinTipiSablonu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HexagonalWizardMigrationTest extends TestCase
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
            'name' => 'Villa',
            'slug' => 'villa',
        ]);

        $this->sablon = YayinTipiSablonu::factory()->create([
            'ad' => 'Satılık',
            'slug' => 'satilik',
        ]);
    }

    public function test_money_value_object_enforces_invariants(): void
    {
        $money = new Money(5000000, 'TRY');
        $this->assertEquals(5000000, $money->amount);
        $this->assertEquals('TRY', $money->currency);
        $this->assertStringContainsString('5.000.000', $money->formatted());

        $this->expectException(\InvalidArgumentException::class);
        new Money(-100, 'TRY');
    }

    public function test_money_value_object_rejects_invalid_currency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Money(100, 'XYZ');
    }

    public function test_geo_coordinate_value_object_validates_boundaries(): void
    {
        $bodrum = new GeoCoordinate(37.0383, 27.4292);
        $this->assertTrue($bodrum->isWithinTurkey());

        $this->expectException(\InvalidArgumentException::class);
        new GeoCoordinate(100.0, 200.0);
    }

    public function test_http_api_wizard_step_and_submit_workflow(): void
    {
        Event::fake([
            WizardStepCompleted::class,
            WizardSubmitted::class,
        ]);

        Sanctum::actingAs($this->user);

        // 1. Save Step 1
        $step1Payload = [
            'step' => 1,
            'data' => [
                'tenant_id' => 1,
                'kategori_id' => $this->kategori->id,
                'baslik' => 'Hexagonal Architecture Villa',
                'aciklama' => 'Yalıhan OS Hexagonal Domain Test İlanı',
                'fiyat' => 25000000,
                'para_birimi' => 'TRY',
                'yayin_durumu' => 'taslak',
                'user_id' => $this->user->id,
            ],
        ];

        $response1 = $this->postJson('/api/v1/wizard/step', $step1Payload);
        $response1->assertOk();
        $response1->assertJsonPath('success', true);

        $ilanId = $response1->json('data.ilan_id');
        $lockVersion = $response1->json('data.session.lock_version');

        $this->assertNotNull($ilanId);
        $this->assertEquals(2, $lockVersion);

        Event::assertDispatched(WizardStepCompleted::class);

        // 2. Query Session State
        $sessionResponse = $this->getJson("/api/v1/wizard/session/{$ilanId}");
        $sessionResponse->assertOk();
        $sessionResponse->assertJsonPath('data.current_step', 2);

        // 3. Save Step 2 with valid lock_version
        $step2Payload = [
            'step' => 2,
            'ilan_id' => $ilanId,
            'lock_version' => $lockVersion,
            'data' => [
                'baslik' => 'Hexagonal Architecture Villa (Updated)',
            ],
        ];

        $response2 = $this->postJson('/api/v1/wizard/step', $step2Payload);
        $response2->assertOk();
        $this->assertEquals(3, $response2->json('data.session.lock_version'));

        // 4. Test Lock Conflict (409 Conflict)
        $conflictPayload = [
            'step' => 2,
            'ilan_id' => $ilanId,
            'lock_version' => 1, // stale lock version
            'data' => [
                'baslik' => 'Stale Update Attempt',
            ],
        ];

        $conflictResponse = $this->postJson('/api/v1/wizard/step', $conflictPayload);
        $conflictResponse->assertStatus(409);

        // 5. Submit Wizard
        $submitPayload = [
            'ilan_id' => $ilanId,
            'yayin_durumu' => 'taslak',
        ];

        $submitResponse = $this->postJson('/api/v1/wizard/domain-submit', $submitPayload);
        $submitResponse->assertOk();
        $submitResponse->assertJsonPath('success', true);

        Event::assertDispatched(WizardSubmitted::class);
    }
}
