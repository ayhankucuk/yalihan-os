<?php

namespace Tests\Unit\Services\Communication;

use App\Models\Communication;
use App\Services\Email\IdempotencyGuard;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * IdempotencyGuardTest
 *
 * D1: Aynı Gmail Message-ID ikinci kez işlenmez — unit test
 */
class IdempotencyGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::table('communications')->truncate();
    }

    public function test_duplicate_message_returns_true(): void
    {
        $tenantId = $this->getDefaultTenantId();
        DB::table('communications')->insert([
            'tenant_id' => $tenantId,
            'external_message_id' => 'msg-123-unique',
            'channel' => 'email',
            'message' => 'Test',
            'reply_durumu' => 'bekliyor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $guard = new IdempotencyGuard(new Communication());

        $this->assertTrue($guard->isDuplicate($tenantId, 'msg-123-unique'));
    }

    public function test_new_message_returns_false(): void
    {
        $guard = new IdempotencyGuard(new Communication());

        $this->assertFalse($guard->isDuplicate($this->getDefaultTenantId(), 'msg-brand-new-456'));
    }

    public function test_find_existing_returns_communication(): void
    {
        $tenantId = $this->getDefaultTenantId();
        DB::table('communications')->insert([
            'tenant_id' => $tenantId,
            'external_message_id' => 'msg-find-789',
            'channel' => 'email',
            'message' => 'Test',
            'reply_durumu' => 'bekliyor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $guard = new IdempotencyGuard(new Communication());
        $found = $guard->findExisting($tenantId, 'msg-find-789');

        $this->assertNotNull($found);
        $this->assertSame('msg-find-789', $found->external_message_id);
    }

    public function test_find_existing_returns_null_for_new_message(): void
    {
        $guard = new IdempotencyGuard(new Communication());

        $found = $guard->findExisting($this->getDefaultTenantId(), 'msg-does-not-exist');

        $this->assertNull($found);
    }

    public function test_different_tenant_same_message_id_not_duplicate(): void
    {
        DB::table('communications')->insert([
            'tenant_id' => 1,
            'external_message_id' => 'msg-cross-tenant',
            'channel' => 'email',
            'message' => 'Test',
            'reply_durumu' => 'bekliyor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $guard = new IdempotencyGuard(new Communication());

        // tenant 2'de aynı message-id → duplicate DEĞİL
        $this->assertFalse($guard->isDuplicate(2, 'msg-cross-tenant'));
    }
}
