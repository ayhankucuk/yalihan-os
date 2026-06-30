<?php

namespace Tests\Unit\Services\Copilot;

use App\Enums\PipelineAdimDurumu;
use App\Enums\PipelineDurumu;
use App\Jobs\Copilot\StartPipelineJob;
use App\Models\PipelineRun;
use App\Models\PipelineStep;
use App\Services\AI\Copilot\Pipeline\PipelineDispatcher;
use App\Services\AI\Copilot\Pipeline\PipelineStateManager;
use App\Services\AI\Copilot\Support\OutputContractValidator;
use App\Services\AI\Copilot\Support\OutputNormalizer;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PipelineDispatcherTest extends TestCase
{

    private PipelineDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new PipelineDispatcher(
            new PipelineStateManager(),
            new OutputNormalizer(),
            new OutputContractValidator(),
        );
    }

    public function test_dispatch_creates_pipeline_run(): void
    {
        Queue::fake();

        $uuid = $this->dispatcher->dispatch('full', ['route' => 'dashboard'], 'wizard');

        $this->assertNotEmpty($uuid);

        $run = PipelineRun::where('run_uuid', $uuid)->first();
        $this->assertNotNull($run);
        $this->assertEquals('full', $run->pipeline_type);
        $this->assertEquals('wizard', $run->module);
        $this->assertEquals(PipelineDurumu::QUEUED, $run->pipeline_durumu);
        $this->assertEquals(['route' => 'dashboard'], $run->input_payload);
    }

    public function test_dispatch_creates_all_step_records(): void
    {
        Queue::fake();

        $uuid = $this->dispatcher->dispatch('audit', ['route' => 'dashboard']);

        $run = PipelineRun::where('run_uuid', $uuid)->first();
        $steps = $run->steps;

        $this->assertCount(6, $steps);

        $stepNames = $steps->pluck('adim_adi')->sort()->values()->toArray();
        $expected = ['audit', 'execution', 'fix', 'govern', 'normalize', 'verification'];
        $this->assertEquals($expected, $stepNames);

        // All steps start as pending
        $steps->each(function (PipelineStep $step) {
            $this->assertEquals(PipelineAdimDurumu::PENDING, $step->adim_durumu);
        });
    }

    public function test_dispatch_queues_start_pipeline_job(): void
    {
        Queue::fake();

        $this->dispatcher->dispatch('full', ['route' => 'dashboard']);

        Queue::assertPushed(StartPipelineJob::class);
    }

    public function test_dispatch_uses_correct_queue(): void
    {
        Queue::fake();

        $this->dispatcher->dispatch('full', ['route' => 'dashboard']);

        Queue::assertPushedOn('copilot-high', StartPipelineJob::class);
    }

    public function test_dispatch_returns_unique_uuid(): void
    {
        Queue::fake();

        $uuid1 = $this->dispatcher->dispatch('full', ['test' => 1]);
        $uuid2 = $this->dispatcher->dispatch('full', ['test' => 2]);

        $this->assertNotEquals($uuid1, $uuid2);
    }

    public function test_dispatch_sets_total_steps(): void
    {
        Queue::fake();

        $uuid = $this->dispatcher->dispatch('full', []);

        $run = PipelineRun::where('run_uuid', $uuid)->first();
        $this->assertEquals(6, $run->total_steps);
        $this->assertEquals(0, $run->completed_steps);
    }

    public function test_queue_routing_maps_correctly(): void
    {
        $this->assertEquals('copilot-high', $this->dispatcher->queueForStep('normalize'));
        $this->assertEquals('copilot-default', $this->dispatcher->queueForStep('audit'));
        $this->assertEquals('copilot-default', $this->dispatcher->queueForStep('fix'));
        $this->assertEquals('copilot-default', $this->dispatcher->queueForStep('execution'));
        $this->assertEquals('copilot-verification', $this->dispatcher->queueForStep('verification'));
        $this->assertEquals('copilot-governance', $this->dispatcher->queueForStep('govern'));
    }

    public function test_dispatch_with_triggered_by_user(): void
    {
        Queue::fake();

        // Create a user
        $user = \App\Models\User::factory()->create();

        $uuid = $this->dispatcher->dispatch('full', [], null, $user->id);

        $run = PipelineRun::where('run_uuid', $uuid)->first();
        $this->assertEquals($user->id, $run->triggered_by);
    }

    public function test_step_queue_names_stored(): void
    {
        Queue::fake();

        $uuid = $this->dispatcher->dispatch('full', []);

        $run = PipelineRun::where('run_uuid', $uuid)->first();

        $normalizeStep = $run->steps()->where('adim_adi', 'normalize')->first();
        $this->assertEquals('copilot-high', $normalizeStep->queue_name);

        $governStep = $run->steps()->where('adim_adi', 'govern')->first();
        $this->assertEquals('copilot-governance', $governStep->queue_name);
    }
}
