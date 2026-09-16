<?php

namespace Tests\Feature\ActionCenter;

use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Models\ActionEvidence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActionEvidenceLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    // ── Factory helpers ─────────────────────────────────────────────────────────

    private function makeGorev(array $attrs = []): Gorev
    {
        return Gorev::factory()->create(array_merge([
            'tenant_id' => $this->tenantId,
            'source_event' => 'IlanCreated',
        ], $attrs));
    }

    // ── Creation ───────────────────────────────────────────────────────────────

    public function test_note_creates_evidence_record(): void
    {
        $gorev = $this->makeGorev();
        $author = User::factory()->create(['tenant_id' => $this->tenantId]);

        $evidence = ActionEvidence::note($gorev, 'Müşteri arandı, geri dönüş bekleniyor.', $author->id);

        $this->assertDatabaseHas('action_evidence', [
            'id' => $evidence->id,
            'gorev_id' => $gorev->id,
            'evidence_type' => 'note',
            'recorded_by' => $author->id,
        ]);
        $data = is_string($evidence->evidence_data)
            ? json_decode($evidence->evidence_data, true)
            : $evidence->evidence_data;
        $this->assertEquals('Müşteri arandı, geri dönüş bekleniyor.', $data['text']);
    }

    public function test_log_system_creates_system_log_entry(): void
    {
        $gorev = $this->makeGorev();

        $evidence = ActionEvidence::logSystem($gorev, 'system', 'Gorev otomatik atandı.');

        $this->assertDatabaseHas('action_evidence', [
            'gorev_id' => $gorev->id,
            'evidence_type' => 'system_log',
            'recorded_by' => null,
        ]);
        $data = $evidence->evidence_data; // already cast to array by model
        $this->assertEquals('Gorev otomatik atandı.', $data['message']);
        $this->assertEquals('system', $data['actor']);
    }

    public function test_log_system_with_user_id_sets_recorded_by(): void
    {
        $gorev = $this->makeGorev();
        $user = User::factory()->create(['tenant_id' => $this->tenantId]);

        $evidence = ActionEvidence::logSystem($gorev, $user->id, 'Kullanıcı görevi tamamladı.');

        $this->assertEquals($user->id, $evidence->recorded_by);
    }

    public function test_photo_creates_photo_entry_with_hash(): void
    {
        $gorev = $this->makeGorev();
        $uploader = User::factory()->create(['tenant_id' => $this->tenantId]);

        $evidence = ActionEvidence::photo(
            $gorev,
            '/storage/photos/temizlik-123.jpg',
            'a1b2c3d4e5f6',
            'image/jpeg',
            $uploader->id
        );

        $this->assertDatabaseHas('action_evidence', [
            'gorev_id' => $gorev->id,
            'evidence_type' => 'photo',
            'recorded_by' => $uploader->id,
        ]);
        $data = $evidence->evidence_data;
        $this->assertEquals('/storage/photos/temizlik-123.jpg', $data['path']);
        $this->assertEquals('a1b2c3d4e5f6', $data['hash']);
        $this->assertEquals('image/jpeg', $data['mime']);
    }

    // ── isCompletionProof ───────────────────────────────────────────────────────

    public function test_is_completion_proof_returns_true_for_note(): void
    {
        $evidence = ActionEvidence::note(Gorev::factory()->create(['tenant_id' => $this->tenantId]), 'Tamamlandı.');
        $this->assertTrue($evidence->isCompletionProof());
    }

    public function test_is_completion_proof_returns_true_for_photo(): void
    {
        $evidence = ActionEvidence::photo(Gorev::factory()->create(['tenant_id' => $this->tenantId]), '/path.jpg', 'hash');
        $this->assertTrue($evidence->isCompletionProof());
    }

    public function test_is_completion_proof_returns_true_for_screenshot(): void
    {
        $gorev = Gorev::factory()->create(['tenant_id' => $this->tenantId]);
        $evidence = ActionEvidence::create([
            'gorev_id' => $gorev->id,
            'evidence_type' => 'screenshot',
            'evidence_data' => json_encode(['path' => '/screenshot.png']),
        ]);
        $this->assertTrue($evidence->isCompletionProof());
    }

    public function test_is_completion_proof_returns_false_for_system_log(): void
    {
        $evidence = ActionEvidence::logSystem(Gorev::factory()->create(['tenant_id' => $this->tenantId]), 'system', 'Auto-assigned.');
        $this->assertFalse($evidence->isCompletionProof());
    }

    // ── Cascade delete ─────────────────────────────────────────────────────────

    public function test_deleting_gorev_cascades_to_action_evidence(): void
    {
        $gorev = $this->makeGorev();
        ActionEvidence::note($gorev, 'Bir not.');
        ActionEvidence::logSystem($gorev, 'system', 'Sistem notu.');

        $this->assertEquals(2, ActionEvidence::where('gorev_id', $gorev->id)->count());

        $gorev->forceDelete();

        $this->assertEquals(0, ActionEvidence::where('gorev_id', $gorev->id)->count());
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function test_gorev_relationship_returns_model(): void
    {
        $gorev = $this->makeGorev();
        $evidence = ActionEvidence::note($gorev, 'Test notu.');

        $this->assertInstanceOf(Gorev::class, $evidence->gorev);
        $this->assertEquals($gorev->id, $evidence->gorev->id);
    }

    public function test_recorded_by_relationship_returns_model(): void
    {
        $gorev = $this->makeGorev();
        $author = User::factory()->create(['tenant_id' => $this->tenantId]);
        $evidence = ActionEvidence::note($gorev, 'Test.', $author->id);

        $this->assertInstanceOf(User::class, $evidence->recordedBy);
        $this->assertEquals($author->id, $evidence->recordedBy->id);
    }

    public function test_recorded_by_returns_null_for_system_log(): void
    {
        $gorev = $this->makeGorev();
        $evidence = ActionEvidence::logSystem($gorev, 'system', 'System event.');

        $this->assertNull($evidence->recordedBy);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function test_scope_of_type_filters_correctly(): void
    {
        $gorev = $this->makeGorev();
        ActionEvidence::note($gorev, 'Not 1.');
        ActionEvidence::note($gorev, 'Not 2.');
        ActionEvidence::logSystem($gorev, 'system', 'System log.');

        $notes = ActionEvidence::ofType('note')->get();
        $logs = ActionEvidence::ofType('system_log')->get();

        $this->assertCount(2, $notes);
        $this->assertCount(1, $logs);
    }

    public function test_scope_completion_evidence_returns_only_completion_types(): void
    {
        $gorev = $this->makeGorev();
        ActionEvidence::note($gorev, 'Not.');
        ActionEvidence::photo($gorev, '/img.jpg', 'hash');
        ActionEvidence::logSystem($gorev, 'system', 'Log.');

        $completion = ActionEvidence::completionEvidence()->get();

        $this->assertCount(2, $completion);
        $types = $completion->pluck('evidence_type')->toArray();
        $this->assertContains('note', $types);
        $this->assertContains('photo', $types);
        $this->assertNotContains('system_log', $types);
    }
}
