<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\CRM\FollowUpAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAuthorityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_modular_gorev_records_when_scheduling_follow_up()
    {
        $agent = User::factory()->create();
        $lead = Lead::factory()->create([
            'assigned_agent_id' => $agent->id,
            'crm_durumu' => 0, // NEW
        ]);

        $service = new FollowUpAutomationService();
        $service->scheduleFollowUp($lead);

        // Verify that a Gorev was created, not a FollowUpTask
        $this->assertDatabaseHas('gorevler', [
            'lead_id' => $lead->id,
            'atanan_user_id' => $agent->id,
            'gorev_durumu' => 'beklemede',
        ]);

        $task = Gorev::where('lead_id', $lead->id)->first();
        $this->assertStringContainsString('Takip:', $task->baslik);
        $this->assertEquals('contact_new_lead', $task->gorev_tipi);
    }

    /** @test */
    public function it_can_complete_a_task_and_update_status_to_tamamlandi()
    {
        $agent = User::factory()->create();
        $task = Gorev::create([
            'baslik' => 'Test Task',
            'atanan_user_id' => $agent->id,
            'gorev_durumu' => 'beklemede',
            'bitis_tarihi' => now()->addDay(),
        ]);

        $service = new FollowUpAutomationService();
        $service->completeTask($task, 'Task finished successfully');

        $this->assertEquals('tamamlandi', $task->fresh()->gorev_durumu);
        $this->assertStringContainsString('Task finished successfully', $task->fresh()->notlar);
    }
}
