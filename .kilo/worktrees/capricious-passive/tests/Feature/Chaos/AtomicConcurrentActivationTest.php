<?php

declare(strict_types=1);

namespace Tests\Feature\Chaos;

use App\Modules\GovernanceCore\Core\VersionActivationService;
use App\Models\PropertyConfigVersion;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AtomicConcurrentActivationTest extends TestCase
{

    private VersionActivationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VersionActivationService::class);
    }

    /**
     * @test
     * @group skip-until-migration-complete
     */
    public function it_prevents_simultaneous_activations_via_lock()
    {
        // 1. Setup versions
        $v1 = PropertyConfigVersion::factory()->create(['yonetim_durumu' => 'AKTIF', 'version_hash' => 'v1']);
        $v2 = PropertyConfigVersion::factory()->create(['yonetim_durumu' => 'ONAYLANDI', 'version_hash' => 'v2']);
        $admin = User::factory()->create(['role_id' => 1]);

        // 2. Mock a lock already being held
        Cache::lock('governance.activation_mutex', 30)->get();

        // 3. Attempt activation (should fail immediately)
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Activation in progress");

        $this->service->activate($v2, (int)$admin->id);
    }
}
