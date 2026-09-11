<?php

namespace Tests\Unit\AI;

use App\Jobs\AI\GenerateStorytellingJob;
use App\Jobs\AI\MasterAIOrchestrator;
use App\Jobs\AI\VisionAnalysisJob;
use App\Services\AI\VisionService;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class VisionAnalysisJobTest extends TestCase
{
    public function test_vision_analysis_job_initialization_and_queue_properties(): void
    {
        $job = new VisionAnalysisJob(42, 'photos/villa_1.jpg');

        $this->assertEquals(42, $job->ilanId);
        $this->assertEquals('photos/villa_1.jpg', $job->dosyaYolu);
        $this->assertEquals(2, $job->tries);
        $this->assertEquals(60, $job->timeout);
    }

    public function test_vision_analysis_job_calls_vision_service_with_correct_signature(): void
    {
        $mockService = Mockery::mock(VisionService::class);
        $mockService->shouldReceive('analizEt')
            ->once()
            ->with('photos/villa_living_room.jpg', ['ilan_id' => 99])
            ->andReturn([
                'quality_score' => 9,
                'detected_features' => ['deniz_manzarasi', 'somine'],
                'is_verified' => true,
                'room_type' => 'Salon',
                'aesthetic_rating' => 9,
            ]);

        $job = new VisionAnalysisJob(99, 'photos/villa_living_room.jpg');
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    public function test_master_ai_orchestrator_dispatches_parallel_vision_jobs(): void
    {
        Queue::fake();

        $orchestrator = new MasterAIOrchestrator(
            ilanId: 101,
            fotografYollari: ['photos/1.jpg', 'photos/2.jpg', 'photos/3.jpg'],
            ton: 'luks'
        );

        $orchestrator->handle();

        Queue::assertPushed(VisionAnalysisJob::class, 3);
        Queue::assertPushed(GenerateStorytellingJob::class, 1);
    }
}
