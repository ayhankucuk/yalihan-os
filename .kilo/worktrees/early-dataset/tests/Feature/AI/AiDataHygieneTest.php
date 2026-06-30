<?php

namespace Tests\Feature\AI;

use App\Services\AI\AiArchiveService;
use App\Services\AI\AiRetentionPolicyService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiDataHygieneTest extends TestCase
{

    /** @test */
    public function retention_policy_calculates_correct_cutoff_date()
    {
        $retentionPolicy = app(AiRetentionPolicyService::class);
        
        $cutoffDate = $retentionPolicy->getCutoffDate();
        $expected = now()->subDays(90);

        $this->assertEquals($expected->toDateString(), $cutoffDate->toDateString());
    }

    /** @test */
    public function should_retain_returns_true_for_recent_records()
    {
        $retentionPolicy = app(AiRetentionPolicyService::class);
        
        $recentDate = now()->subDays(30);
        
        $this->assertTrue($retentionPolicy->shouldRetain($recentDate));
    }

    /** @test */
    public function should_retain_returns_false_for_old_records()
    {
        $retentionPolicy = app(AiRetentionPolicyService::class);
        
        $oldDate = now()->subDays(100);
        
        $this->assertFalse($retentionPolicy->shouldRetain($oldDate));
    }

    /** @test */
    public function retention_policy_identifies_tables_correctly()
    {
        $retentionPolicy = app(AiRetentionPolicyService::class);
        
        $tables = $retentionPolicy->getRetentionTables();
        
        $this->assertContains('ai_feature_usages', $tables);
        $this->assertContains('ai_logs', $tables);
        $this->assertContains('ai_provider_decisions', $tables);
    }

    /** @test */
    public function retention_policy_checks_archive_enabled()
    {
        $retentionPolicy = app(AiRetentionPolicyService::class);
        
        $this->assertTrue($retentionPolicy->isArchiveEnabled('ai_feature_usages'));
        $this->assertTrue($retentionPolicy->isArchiveEnabled('ai_logs'));
    }

    /** @test */
    public function retention_policy_gets_correct_date_column()
    {
        $retentionPolicy = app(AiRetentionPolicyService::class);
        
        $dateColumn = $retentionPolicy->getDateColumn('ai_feature_usages');
        
        $this->assertEquals('created_at', $dateColumn);
    }

    /** @test */
    public function archive_service_initializes_correctly()
    {
        $archiveService = app(AiArchiveService::class);
        
        $this->assertInstanceOf(AiArchiveService::class, $archiveService);
    }
}
