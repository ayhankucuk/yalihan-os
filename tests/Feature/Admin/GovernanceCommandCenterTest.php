<?php

namespace Tests\Feature\Admin;

use App\Http\Livewire\Admin\GovernanceCommandCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GovernanceCommandCenterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_counts_recent_governance_decisions_by_decision_date(): void
    {
        $now = now();

        DB::table('governance_decisions')->insert([
            'finding_id' => 'GCC-RECENT',
            'source' => 'test',
            'domain' => 'governance',
            'severity' => 'high',
            'title' => 'Recent decision',
            'reason' => 'Test decision',
            'target' => 'test.target',
            'recommended_action' => 'review',
            'risk' => 'medium',
            'decision' => 'needs_review',
            'karar_durumu' => 'approved',
            'karar_tarihi' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('governance_decisions')->insert([
            'finding_id' => 'GCC-OLD',
            'source' => 'test',
            'domain' => 'governance',
            'severity' => 'high',
            'title' => 'Old decision',
            'reason' => 'Test decision',
            'target' => 'test.target',
            'recommended_action' => 'review',
            'risk' => 'medium',
            'decision' => 'needs_review',
            'karar_durumu' => 'approved',
            'karar_tarihi' => $now->copy()->subDays(2),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('governance_events')->insert([
            'metric' => 'governance.violation.test',
            'is_violation' => true,
            'occurred_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $component = new GovernanceCommandCenter();
        $component->loadStats();

        $this->assertSame(1, $component->stats['total_decisions']);
        $this->assertSame(1, $component->stats['drift_count']);
    }
}
