<?php

namespace Tests\Feature\Console;

use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Models\SaaS\Tenant;
use App\Services\Ranking\ListingRankingService;
use App\Services\SaaS\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Minimal isolation test — verify the command's core logic in a controlled setup.
 * No artisan output buffering, no progress bar, no mocks.
 */
class RecalculateRankingCommandIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_resolution_and_score_update(): void
    {
        $tenant = Tenant::create(['name' => 'Test Tenant', 'domain' => 'test.test']);

        // Create a listing with sufficient data for a non-zero score
        $ilan = Ilan::create([
            'tenant_id' => $tenant->id,
            'baslik' => 'Test Ilan with a very long title for SEO purposes',
            'aciklama' => 'This is a description with more than 100 characters for quality scoring.',
            'fiyat' => 1000000.00,
            'yayin_durumu' => 'yayinda',
            'visibility_score' => 0,
            'slug' => 'test-slug-' . uniqid(),
            'goruntulenme' => 100,
            'favorite_count' => 5,
        ]);

        // Verify the listing is set up correctly
        $this->assertEquals('yayinda', $ilan->getRawOriginal('yayin_durumu'));
        $this->assertTrue($ilan->yayindami);

        // Verify score calculation works
        $svc = new ListingRankingService();
        $score = $svc->calculateScore($ilan);
        $this->assertGreaterThan(0, $score, "Score must be > 0, got: {$score}");

        // Now test: can we resolve the tenant ID from the listing?
        $tenantIds = Ilan::withoutGlobalScopes()
            ->whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'yayinda'])
            ->distinct()
            ->pluck('tenant_id')
            ->filter()
            ->values();

        $this->assertCount(1, $tenantIds);
        $this->assertEquals($tenant->id, $tenantIds->first());

        // Set tenant context
        $ctx = app(TenantContextService::class);
        $ctx->clearTenant();
        $this->assertFalse($ctx->hasTenant());

        $ctx->setTenant($tenant);
        $this->assertTrue($ctx->hasTenant());
        $this->assertEquals($tenant->id, $ctx->getTenant()->id);

        // Query ilan under tenant scope
        $ilanUnderScope = Ilan::query()
            ->whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'yayinda'])
            ->first();

        $this->assertNotNull($ilanUnderScope);
        $this->assertEquals($ilan->id, $ilanUnderScope->id);

        // Apply score update
        $ilanUnderScope->visibility_score = $svc->calculateScore($ilanUnderScope);
        $ilanUnderScope->saveQuietly();

        // Verify persistence
        $ilanUnderScope->refresh();
        $this->assertGreaterThan(0, $ilanUnderScope->visibility_score);

        // Cleanup
        $ctx->clearTenant();
        $this->assertFalse($ctx->hasTenant());
    }
}
