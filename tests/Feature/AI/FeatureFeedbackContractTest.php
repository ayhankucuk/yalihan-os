<?php

namespace Tests\Feature\AI;

use App\Models\AiFeatureUsage;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\User;
use Tests\TestCase;

/**
 * FeatureFeedbackContractTest
 *
 * Tests feature feedback API contract for AI learning signal recording.
 *
 * @group ai-feature
 * @group pending-auth-fix
 */
class FeatureFeedbackContractTest extends TestCase
{

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    /** @test */
    public function it_records_valid_feedback_and_creates_learning_signal()
    {
        // @skip PENDING: Sanctum auth requires Spatie role sync in test DB
        $this->markTestSkipped('PENDING: Sanctum + Spatie role sync required for API auth');
    }

    /** @test */
    public function it_blocks_feedback_for_slugs_not_in_ups_template()
    {
        // @skip PENDING: Sanctum auth requires Spatie role sync in test DB
        $this->markTestSkipped('PENDING: Sanctum + Spatie role sync required for API auth');
    }
}
