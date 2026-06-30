<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\CallIntelligenceService;
use Tests\TestCase;

class CallIntelligenceServiceTest extends TestCase
{
    protected CallIntelligenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CallIntelligenceService();
    }

    /** @test */
    public function it_can_analyze_call_with_existing_transcript()
    {
        $transcript = "Müşteri İstanbul'da 3+1 daire arıyor. Bütçesi 15 milyon TL.";
        $activityId = 123; // Mock ID

        $result = $this->service->analyzeCall($activityId, null, $transcript);

        $this->assertArrayHasKey('summary_short', $result);
        $this->assertArrayHasKey('sentiment_score', $result);
        $this->assertEquals($activityId, $result['activity_id']);
        $this->assertEquals($transcript, $result['transcript']);
    }

    /** @test */
    public function it_fails_gracefully_when_no_transcript_or_audio()
    {
        $result = $this->service->analyzeCall(999);

        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertEquals('No transcript available', $result['message']);
    }

    /** @test */
    public function it_can_mock_transcription_from_audio_path()
    {
        $result = $this->service->analyzeCall(456, '/path/to/mock/audio.mp3');

        $this->assertArrayHasKey('transcript', $result);
        $this->assertStringContainsString('örnek çağrı metnidir', $result['transcript']);
        $this->assertArrayHasKey('summary_long', $result);
    }
}
