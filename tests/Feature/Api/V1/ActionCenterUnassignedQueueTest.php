<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActionCenterUnassignedQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private int $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['tenant_id' => $this->tenantId]);
    }

    /**
     * Test that dashboard unassigned queue excludes tasks that have an assigned user,
     * even if their status is 'bekliyor'.
     */
    public function test_unassigned_queue_excludes_assigned_tasks_with_status_bekliyor(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create(['tenant_id' => $this->tenantId]);

        // 1. Truly unassigned task
        $unassignedTask = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'bekliyor',
            'atanan_user_id' => null,
            'bitis_tarihi' => now()->addDays(5),
            'source_event' => 'IlanCreated',
        ]);

        // 2. Assigned to another user but status is 'bekliyor'
        $assignedToOther = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'bekliyor',
            'atanan_user_id' => $otherUser->id,
            'bitis_tarihi' => now()->addDays(5),
            'source_event' => 'IlanCreated',
        ]);

        // 3. Assigned to current user but status is 'bekliyor'
        $assignedToMe = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'bekliyor',
            'atanan_user_id' => $this->user->id,
            'bitis_tarihi' => now()->addDays(5),
            'source_event' => 'IlanCreated',
        ]);

        $response = $this->getJson('/api/v1/action-center/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unassigned.count', 1);

        $unassignedItems = $response->json('data.unassigned.items');
        $this->assertCount(1, $unassignedItems);
        $this->assertEquals($unassignedTask->id, $unassignedItems[0]['id']);
    }

    /**
     * Test that unassigned queue enforces tenant isolation.
     */
    public function test_unassigned_queue_enforces_tenant_isolation(): void
    {
        Sanctum::actingAs($this->user);

        $otherTenantId = 999;

        // Task for current tenant (unassigned)
        $myTenantTask = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'bekliyor',
            'atanan_user_id' => null,
            'source_event' => 'IlanCreated',
        ]);

        // Task for other tenant (unassigned)
        $otherTenantTask = Gorev::factory()->create([
            'tenant_id' => $otherTenantId,
            'gorev_durumu' => 'bekliyor',
            'atanan_user_id' => null,
            'source_event' => 'IlanCreated',
        ]);

        $response = $this->getJson('/api/v1/action-center/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unassigned.count', 1);

        $unassignedItems = $response->json('data.unassigned.items');
        $this->assertCount(1, $unassignedItems);
        $this->assertEquals($myTenantTask->id, $unassignedItems[0]['id']);
    }
}
