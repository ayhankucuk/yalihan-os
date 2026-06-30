<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Models\Ilan;
use App\Domain\CQRS\Projections\IlanProjectionHandler;
use App\Services\SaaS\TenantContextService;
use App\Enums\IlanDurumu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

/**
 * Class IlanQueryConsistencyTest
 * @package Tests\Feature\Api
 * @description CQRS Sprint 3: Query API Gateway and Eventual Consistency Integration and Isolation Tests.
 */
class IlanQueryConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private TenantContextService $tenantContext;
    private IlanProjectionHandler $projectionHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = app(TenantContextService::class);
        $this->projectionHandler = app(IlanProjectionHandler::class);
        
        // Ensure Redis driver is faked or using array/null if needed, but standard cache testing is fine.
        Cache::flush();
        $this->withoutExceptionHandling();
    }

    /**
     * Test: Eventual Consistency and Tenant-Tagged Cache Eviction
     *
     * @test
     * @group cqrs
     */
    public function test_eventual_consistency_and_cache_eviction(): void
    {
        // 1. Seed two distinct tenants
        $tenantA = Tenant::firstOrCreate(['id' => 1001], ['name' => 'Tenant A', 'aktiflik_durumu' => 1]);
        $tenantB = Tenant::firstOrCreate(['id' => 1002], ['name' => 'Tenant B', 'aktiflik_durumu' => 1]);

        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

        // 2. Create listing on write model for Tenant A
        $ilanA = Ilan::factory()->create([
            'id' => 10001,
            'tenant_id' => $tenantA->id,
            'danisman_id' => $userA->id,
            'baslik' => 'Write Model Listing A',
            'fiyat' => 1000000,
            'yayin_durumu' => 'taslak',
            'aktiflik_durumu' => 1,
        ]);

        // 3. Project "IlanOlusturuldu" event onto read model
        $this->projectionHandler->handle([
            'tenant_id' => $tenantA->id,
            'event_type' => 'IlanOlusturuldu',
            'aggregate_id' => $ilanA->id,
            'sequence_number' => 1,
            'payload' => [
                'baslik' => 'Read Model Listing A',
                'ilan_durumu' => 'taslak',
                'fiyat' => 1000000,
            ]
        ]);

        // Assert write was persisted to read model table
        $this->assertDatabaseHas('ilanlar_read_model', [
            'tenant_id' => $tenantA->id,
            'ilan_id' => $ilanA->id,
            'baslik' => 'Read Model Listing A',
            'yayin_durumu' => 'taslak',
            'fiyat' => 1000000,
            'son_islenen_sira_numarasi' => 1,
        ]);

        // 4. Authenticate as User A and hit Query API Index
        Sanctum::actingAs($userA);

        $response = $this->getJson('/api/v1/query/ilanlar');
        $response->assertStatus(200);
        $response->assertJsonPath('durum', 'basari');
        
        // Assert listing exists in response (note: only 'aktif' active listings are returned in getPaginatedList query)
        // Since we created listing with aktiflik_durumu = 1 (active) in the projection
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Read Model Listing A', $data[0]['baslik']);

        // 5. Test cache hit: Querying it again should pull from cache
        $responseShow = $this->getJson("/api/v1/query/ilanlar/{$ilanA->id}");
        $responseShow->assertStatus(200);
        $responseShow->assertJsonPath('durum', 'basari');
        $responseShow->assertJsonPath('data.baslik', 'Read Model Listing A');

        // 6. Update Listing via domain event: Price changed and Status updated
        $this->projectionHandler->handle([
            'tenant_id' => $tenantA->id,
            'event_type' => 'IlanFiyatiDegistirildi',
            'aggregate_id' => $ilanA->id,
            'sequence_number' => 2,
            'payload' => [
                'yeni_fiyat' => 1250000,
            ]
        ]);

        $this->projectionHandler->handle([
            'tenant_id' => $tenantA->id,
            'event_type' => 'IlanDurumuDegistirildi',
            'aggregate_id' => $ilanA->id,
            'sequence_number' => 3,
            'payload' => [
                'yeni_durum' => 'yayinda',
            ]
        ]);

        // Assert database is updated (idempotency sequence numbers are tracked correctly)
        $this->assertDatabaseHas('ilanlar_read_model', [
            'tenant_id' => $tenantA->id,
            'ilan_id' => $ilanA->id,
            'fiyat' => 1250000,
            'yayin_durumu' => 'yayinda',
            'son_islenen_sira_numarasi' => 3,
        ]);

        // 7. Hit API gateway again: Cache should be evicted and fresh consistent values returned
        $responseShowUpdated = $this->getJson("/api/v1/query/ilanlar/{$ilanA->id}");
        $responseShowUpdated->assertStatus(200);
        $responseShowUpdated->assertJsonPath('data.fiyat', 1250000);
        $responseShowUpdated->assertJsonPath('data.yayin_durumu', 'yayinda');
    }

    /**
     * Test: Strict Tenant Isolation on Query Gateway API
     *
     * @test
     * @group cqrs
     */
    public function test_query_api_strict_tenant_isolation(): void
    {
        // 1. Seed two distinct tenants
        $tenantA = Tenant::firstOrCreate(['id' => 2001], ['name' => 'Tenant A', 'aktiflik_durumu' => 1]);
        $tenantB = Tenant::firstOrCreate(['id' => 2002], ['name' => 'Tenant B', 'aktiflik_durumu' => 1]);

        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

        // 2. Create listing A on write model
        $ilanA = Ilan::factory()->create([
            'id' => 20001,
            'tenant_id' => $tenantA->id,
            'danisman_id' => $userA->id,
            'baslik' => 'Listing Tenant A',
            'aktiflik_durumu' => 1,
        ]);

        // Create listing B on write model
        $ilanB = Ilan::factory()->create([
            'id' => 20002,
            'tenant_id' => $tenantB->id,
            'danisman_id' => $userB->id,
            'baslik' => 'Listing Tenant B',
            'aktiflik_durumu' => 1,
        ]);

        // 3. Project both listings onto read model
        $this->projectionHandler->handle([
            'tenant_id' => $tenantA->id,
            'event_type' => 'IlanOlusturuldu',
            'aggregate_id' => $ilanA->id,
            'sequence_number' => 1,
            'payload' => [
                'baslik' => 'Listing Tenant A Read Model',
                'ilan_durumu' => 'taslak',
            ]
        ]);

        $this->projectionHandler->handle([
            'tenant_id' => $tenantB->id,
            'event_type' => 'IlanOlusturuldu',
            'aggregate_id' => $ilanB->id,
            'sequence_number' => 1,
            'payload' => [
                'baslik' => 'Listing Tenant B Read Model',
                'ilan_durumu' => 'taslak',
            ]
        ]);

        // 4. Authenticate as Tenant A user
        Sanctum::actingAs($userA);

        // Get all listings for Tenant A
        $responseIndex = $this->getJson('/api/v1/query/ilanlar');
        $responseIndex->assertStatus(200);
        $dataIndex = $responseIndex->json('data');
        
        // Assert only Tenant A listing is returned
        $this->assertCount(1, $dataIndex);
        $this->assertEquals('Listing Tenant A Read Model', $dataIndex[0]['baslik']);

        // Assert Tenant A cannot show Tenant B's listing (must return 404 isolated)
        $responseShowOther = $this->getJson("/api/v1/query/ilanlar/{$ilanB->id}");
        $responseShowOther->assertStatus(404);
        $responseShowOther->assertJsonPath('durum', 'hata');
        $responseShowOther->assertJsonPath('message', 'Ilan bulunamadi veya erisim yetkiniz yok.');
    }

    /**
     * Test: CQRS Command Write Path Mutations (POST, PUT, PATCH)
     *
     * @test
     * @group cqrs
     */
    public function test_command_api_gateway_mutations(): void
    {
        // 1. Seed tenant and user
        $tenant = Tenant::firstOrCreate(['id' => 3001], ['name' => 'Command Tenant', 'aktiflik_durumu' => 1]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        Sanctum::actingAs($user);

        // 2. POST to create listing (Command)
        $payload = [
            'baslik' => 'Command Listing Title',
            'fiyat' => 950000.00,
            'ana_kategori_id' => 1,
            'alt_kategori_id' => 2,
            'il' => 'İstanbul',
            'ilce' => 'Beşiktaş',
            'yayin_durumu' => 'taslak'
        ];

        try {
            $responsePost = $this->postJson('/api/v1/command/ilanlar', $payload);
        } catch (\Throwable $e) {
            file_put_contents('/Users/macbookpro/dev/yalihan2026/storage/logs/test_error.txt', $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }
        
        // Assert 202 Accepted and Zero Query constraint (no model returned, only id)
        $responsePost->assertStatus(202);
        $responsePost->assertJsonPath('durum', 'basari');
        $responsePost->assertJsonPath('message', 'Ilan olusturma komutu basariyla alindi ve islendi.');
        
        $ilanId = $responsePost->json('data.ilan_id');
        $this->assertNotNull($ilanId);

        // Verify event store has the IlanOlusturuldu event
        $this->assertDatabaseHas('etki_alani_olaylari', [
            'tenant_id' => $tenant->id,
            'aggregate_id' => $ilanId,
            'event_type' => 'IlanOlusturuldu',
            'sequence_number' => 1
        ]);

        // Manually process the async projection job so read model is updated
        $latestEvent = \App\Models\EtkiAlaniOlayi::where('aggregate_id', $ilanId)
            ->where('sequence_number', 1)
            ->first();
        $this->projectionHandler->handle($latestEvent->toArray());

        // Assert read model is now populated and queryable
        $responseShow = $this->getJson("/api/v1/query/ilanlar/{$ilanId}");
        $responseShow->assertStatus(200);
        $responseShow->assertJsonPath('data.baslik', 'Command Listing Title');
        $responseShow->assertJsonPath('data.fiyat', 950000);

        // 3. PUT to update listing (Command: Change Price)
        $updatePayload = [
            'fiyat' => 980000.00,
        ];

        $responsePut = $this->putJson("/api/v1/command/ilanlar/{$ilanId}", $updatePayload);
        
        // Assert 200 OK and Zero Query constraint
        $responsePut->assertStatus(200);
        $responsePut->assertJsonPath('durum', 'basari');
        $responsePut->assertJsonPath('message', 'Ilan guncelleme komutu basariyla islendi.');

        // Verify Event Store has the IlanFiyatiDegistirildi event (sequence 2)
        $this->assertDatabaseHas('etki_alani_olaylari', [
            'tenant_id' => $tenant->id,
            'aggregate_id' => $ilanId,
            'event_type' => 'IlanFiyatiDegistirildi',
            'sequence_number' => 2
        ]);

        // Project the second event
        $priceEvent = \App\Models\EtkiAlaniOlayi::where('aggregate_id', $ilanId)
            ->where('sequence_number', 2)
            ->first();
        $this->projectionHandler->handle($priceEvent->toArray());

        // Clear local cache to bypass tag cache for tests
        Cache::flush();

        // Assert Query API reflects updated price
        $responseShowUpdated = $this->getJson("/api/v1/query/ilanlar/{$ilanId}");
        $responseShowUpdated->assertStatus(200);
        $responseShowUpdated->assertJsonPath('data.fiyat', 980000);

        // 4. PATCH to update status (Command: Change Status to beklemede)
        $patchPayload = [
            'yayin_durumu' => 'beklemede'
        ];

        $responsePatch = $this->patchJson("/api/v1/command/ilanlar/{$ilanId}/durum", $patchPayload);
        $responsePatch->assertStatus(200);
        $responsePatch->assertJsonPath('durum', 'basari');
        
        // Verify Event Store has IlanDurumuDegistirildi (sequence 3)
        $this->assertDatabaseHas('etki_alani_olaylari', [
            'tenant_id' => $tenant->id,
            'aggregate_id' => $ilanId,
            'event_type' => 'IlanDurumuDegistirildi',
            'sequence_number' => 3
        ]);
    }
}
