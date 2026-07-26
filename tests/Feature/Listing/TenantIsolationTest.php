<?php

namespace Tests\Feature\Listing;

use App\Domain\Listing\ListingCrudService;
use App\Models\Ilan;
use App\Models\Property;
use App\Models\PropertyWorkspace;
use App\Models\SaaS\Tenant;
use App\Services\Listing\YalihanLifecycle;
use App\Services\Property\PropertyCrudService;
use App\Services\Property\PropertyStateMachine;
use App\Services\SaaS\TenantContextService;
use App\Repositories\EloquentPropertyRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

/**
 * Sprint 12B: Workspace Tenant Isolation — Cross-Tenant Tests
 *
 * Validates that cross-tenant access is blocked.
 * All CRUD operations must validate tenant_id matches current context.
 */
class TenantIsolationTest extends TestCase
{
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected PropertyCrudService $propertyService;
    protected ListingCrudService $listingService;
    protected YalihanLifecycle $lifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupTestTables();

        $this->tenantA = Tenant::create(['name' => 'Tenant A', 'domain' => 'tenant-a.test']);
        $this->tenantB = Tenant::create(['name' => 'Tenant B', 'domain' => 'tenant-b.test']);

        $propertyRepo = new EloquentPropertyRepository(new Property());
        $this->propertyService = new PropertyCrudService($propertyRepo, new PropertyStateMachine());
        $this->listingService = new ListingCrudService();
        $this->lifecycle = app(YalihanLifecycle::class);

        YalihanLifecycle::$skipGuards = true;
    }

    protected function tearDown(): void
    {
        YalihanLifecycle::$skipGuards = false;
        app(TenantContextService::class)->clear();
        parent::tearDown();
    }

    protected function setupTestTables(): void
    {
        if (!Schema::hasTable('properties')) {
            Schema::create('properties', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('workspace_id')->nullable();
                $table->string('uuid')->unique();
                $table->string('idempotency_key', 64)->nullable()->unique();
                $table->string('tkgm_id')->nullable();
                $table->string('ada')->nullable();
                $table->string('parsel')->nullable();
                $table->unsignedInteger('il_id')->nullable();
                $table->unsignedInteger('ilce_id')->nullable();
                $table->unsignedInteger('mahalle_id')->nullable();
                $table->decimal('lat', 10, 8)->nullable();
                $table->decimal('lng', 11, 8)->nullable();
                $table->decimal('alan_m2', 10, 2)->nullable();
                $table->string('bina_yasi')->nullable();
                $table->unsignedInteger('kat_sayisi')->nullable();
                $table->unsignedInteger('bulundugu_kat')->nullable();
                $table->string('oda_sayisi')->nullable();
                $table->unsignedInteger('banyo_sayisi')->nullable();
                $table->string('aktiflik_durumu');
                $table->string('kapak_resmi')->nullable();
                $table->string('nitelik')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::dropIfExists('ilanlar');
        Schema::dropIfExists('listing_state_transitions');
        Schema::dropIfExists('property_workspaces');

        // property_workspaces table for workspace isolation tests
        Schema::create('property_workspaces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('ilan_id')->nullable();
            $table->string('workspace_uuid', 36)->unique();
            $table->string('state', 32)->default('draft');
            $table->string('intent', 64)->nullable();
            $table->string('template_id', 64)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('listing_state_transitions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ilan_id');
            $t->string('from_state', 32);
            $t->string('to_state', 32);
            $t->unsignedBigInteger('aktan_id')->nullable();
            $t->json('meta')->nullable();
            $t->timestamp('created_at')->nullable();
            $t->index('ilan_id');
            $t->index('created_at');
        });
        Schema::create('ilanlar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->string('uuid')->unique();
            $table->string('baslik')->nullable();
            $table->text('aciklama')->nullable();
            $table->decimal('fiyat', 15, 2)->nullable();
            $table->string('para_birimi', 10)->default('TRY');
            $table->string('yayin_durumu')->default('taslak');
            $table->string('kanal', 32)->nullable();
            $table->unsignedInteger('il_id')->nullable();
            $table->unsignedInteger('ilce_id')->nullable();
            $table->unsignedInteger('mahalle_id')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->string('ada_no')->nullable();
            $table->string('parsel_no')->nullable();
            $table->string('slug')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->integer('completion_score')->nullable();
            $table->float('quality_score')->nullable();
            $table->unsignedBigInteger('yayin_tipi_id')->nullable();
            $table->string('ilan_tarihi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CROSS-TENANT ACCESS TESTS
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function submit_for_review_blocks_cross_tenant_access(): void
    {
        // Create listing under Tenant A
        app(TenantContextService::class)->setTenant($this->tenantA);
        $listingA = $this->createListingForTenant($this->tenantA->id);

        // Set Tenant B context
        app(TenantContextService::class)->setTenant($this->tenantB);

        // Attempt to submit listing from Tenant A (should fail)
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/Cross-tenant|cross-tenant/i');

        $this->listingService->submitForReview($listingA);
    }

    /** @test */
    public function publish_blocks_cross_tenant_access(): void
    {
        // Create listing under Tenant A
        app(TenantContextService::class)->setTenant($this->tenantA);
        $listingA = $this->createListingForTenant($this->tenantA->id, 'beklemede');

        // Set Tenant B context
        app(TenantContextService::class)->setTenant($this->tenantB);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/Cross-tenant|cross-tenant/i');

        $this->listingService->publish($listingA);
    }

    /** @test */
    public function unpublish_blocks_cross_tenant_access(): void
    {
        // Create listing under Tenant A
        app(TenantContextService::class)->setTenant($this->tenantA);
        $listingA = $this->createListingForTenant($this->tenantA->id, 'yayinda');

        // Set Tenant B context
        app(TenantContextService::class)->setTenant($this->tenantB);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/Cross-tenant|cross-tenant/i');

        $this->listingService->unpublish($listingA);
    }

    /** @test */
    public function archive_blocks_cross_tenant_access(): void
    {
        // Create listing under Tenant A
        app(TenantContextService::class)->setTenant($this->tenantA);
        $listingA = $this->createListingForTenant($this->tenantA->id);

        // Set Tenant B context
        app(TenantContextService::class)->setTenant($this->tenantB);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/Cross-tenant|cross-tenant/i');

        $this->listingService->archive($listingA);
    }

    /** @test */
    public function update_blocks_cross_tenant_access(): void
    {
        // Create listing under Tenant A
        app(TenantContextService::class)->setTenant($this->tenantA);
        $listingA = $this->createListingForTenant($this->tenantA->id);

        // Set Tenant B context
        app(TenantContextService::class)->setTenant($this->tenantB);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/Cross-tenant|cross-tenant/i');

        $this->listingService->update($listingA, ['baslik' => 'Hacked Title']);
    }

    /** @test */
    public function delete_blocks_cross_tenant_access(): void
    {
        // Create listing under Tenant A
        app(TenantContextService::class)->setTenant($this->tenantA);
        $listingA = $this->createListingForTenant($this->tenantA->id);

        // Set Tenant B context
        app(TenantContextService::class)->setTenant($this->tenantB);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/Cross-tenant|cross-tenant/i');

        $this->listingService->delete($listingA);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SAME-TENANT ACCESS TESTS (Positive cases)
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function submit_for_review_allows_same_tenant_access(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);
        $listing = $this->createListingForTenant($this->tenantA->id);

        // Same tenant should succeed
        $result = $this->listingService->submitForReview($listing);

        $this->assertEquals('beklemede', $this->getStateValue($result));
    }

    /** @test */
    public function publish_allows_same_tenant_access(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);
        $listing = $this->createListingForTenant($this->tenantA->id, 'beklemede');

        $result = $this->listingService->publish($listing);

        $this->assertEquals('yayinda', $this->getStateValue($result));
    }

    /** @test */
    public function unpublish_allows_same_tenant_access(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);
        $listing = $this->createListingForTenant($this->tenantA->id, 'yayinda');

        $result = $this->listingService->unpublish($listing);

        $this->assertEquals('pasif', $this->getStateValue($result));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // WORKSPACE ISOLATION TESTS (Sprint 12B)
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function submit_for_review_blocks_cross_workspace_access(): void
    {
        // Setup: Two workspaces
        $workspaceA = $this->createWorkspace($this->tenantA->id);
        $workspaceB = $this->createWorkspace($this->tenantA->id);

        // Create listing in Workspace A
        app(TenantContextService::class)->setTenant($this->tenantA);
        $listing = $this->createListingForWorkspace($workspaceA->id);

        // Set Workspace B context
        app(TenantContextService::class)->setTenant($this->tenantA);
        app(TenantContextService::class)->setWorkspace($workspaceB);

        // Attempt to submit listing from Workspace A (should fail)
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/workspace|Workspace/i');

        $this->listingService->submitForReview($listing);
    }

    /** @test */
    public function publish_blocks_cross_workspace_access(): void
    {
        // Setup: Two workspaces
        $workspaceA = $this->createWorkspace($this->tenantA->id);
        $workspaceB = $this->createWorkspace($this->tenantA->id);

        // Create listing in Workspace A (in beklemede state)
        app(TenantContextService::class)->setTenant($this->tenantA);
        $listing = $this->createListingForWorkspace($workspaceA->id, 'beklemede');

        // Set Workspace B context
        app(TenantContextService::class)->setTenant($this->tenantA);
        app(TenantContextService::class)->setWorkspace($workspaceB);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/workspace|Workspace/i');

        $this->listingService->publish($listing);
    }

    /** @test */
    public function unpublish_blocks_cross_workspace_access(): void
    {
        // Setup: Two workspaces
        $workspaceA = $this->createWorkspace($this->tenantA->id);
        $workspaceB = $this->createWorkspace($this->tenantA->id);

        // Create listing in Workspace A (in yayinda state)
        app(TenantContextService::class)->setTenant($this->tenantA);
        $listing = $this->createListingForWorkspace($workspaceA->id, 'yayinda');

        // Set Workspace B context
        app(TenantContextService::class)->setTenant($this->tenantA);
        app(TenantContextService::class)->setWorkspace($workspaceB);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/workspace|Workspace/i');

        $this->listingService->unpublish($listing);
    }

    /** @test */
    public function archive_blocks_cross_workspace_access(): void
    {
        // Setup: Two workspaces
        $workspaceA = $this->createWorkspace($this->tenantA->id);
        $workspaceB = $this->createWorkspace($this->tenantA->id);

        // Create listing in Workspace A
        app(TenantContextService::class)->setTenant($this->tenantA);
        $listing = $this->createListingForWorkspace($workspaceA->id);

        // Set Workspace B context
        app(TenantContextService::class)->setTenant($this->tenantA);
        app(TenantContextService::class)->setWorkspace($workspaceB);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/workspace|Workspace/i');

        $this->listingService->archive($listing);
    }

    /** @test */
    public function submit_for_review_allows_same_workspace_access(): void
    {
        $workspace = $this->createWorkspace($this->tenantA->id);

        app(TenantContextService::class)->setTenant($this->tenantA);
        app(TenantContextService::class)->setWorkspace($workspace);

        $listing = $this->createListingForWorkspace($workspace->id);

        // Same workspace should succeed
        $result = $this->listingService->submitForReview($listing);

        $this->assertEquals('beklemede', $this->getStateValue($result));
    }

    /** @test */
    public function publish_allows_same_workspace_access(): void
    {
        $workspace = $this->createWorkspace($this->tenantA->id);

        app(TenantContextService::class)->setTenant($this->tenantA);
        app(TenantContextService::class)->setWorkspace($workspace);

        $listing = $this->createListingForWorkspace($workspace->id, 'beklemede');

        $result = $this->listingService->publish($listing);

        $this->assertEquals('yayinda', $this->getStateValue($result));
    }

    /** @test */
    public function unpublish_allows_same_workspace_access(): void
    {
        $workspace = $this->createWorkspace($this->tenantA->id);

        app(TenantContextService::class)->setTenant($this->tenantA);
        app(TenantContextService::class)->setWorkspace($workspace);

        $listing = $this->createListingForWorkspace($workspace->id, 'yayinda');

        $result = $this->listingService->unpublish($listing);

        $this->assertEquals('pasif', $this->getStateValue($result));
    }

    /** @test */
    public function operations_work_without_workspace_context_set(): void
    {
        // Workspace context not set, but tenant is set
        $workspace = $this->createWorkspace($this->tenantA->id);

        app(TenantContextService::class)->setTenant($this->tenantA);
        // Do NOT set workspace context

        $listing = $this->createListingForWorkspace($workspace->id);

        // Should work (with warning log) when no workspace context is set
        // This allows backward compatibility
        $result = $this->listingService->submitForReview($listing);

        $this->assertEquals('beklemede', $this->getStateValue($result));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════════════

    protected function createListingForTenant(int $tenantId, string $initialState = 'taslak'): Ilan
    {
        // Set tenant context for property creation (if tenant exists)
        $tenant = Tenant::find($tenantId);
        if ($tenant) {
            app(TenantContextService::class)->setTenant($tenant);
        }

        $property = $this->propertyService->create([
            'workspace_id' => 1,
            'il_id' => 48,
            'ilce_id' => 1,
            'mahalle_id' => 2,
            'lat' => '37.1042',
            'lng' => '27.2900',
            'ada' => '102',
            'parsel' => '4',
        ]);
        $this->propertyService->verify($property);
        $this->propertyService->activate($property);

        $listing = $this->listingService->createFromProperty($property, [
            'baslik' => "Tenant {$tenantId} Listing",
            'kanal' => 'yalihan',
        ]);

        // Force tenant_id to simulate cross-tenant scenario
        DB::table('ilanlar')->where('id', $listing->id)->update(['tenant_id' => $tenantId]);

        // Set initial state if needed
        if ($initialState !== 'taslak') {
            $this->lifecycle->transition($listing, \App\Enums\IlanDurumu::from($initialState));
        }

        return $listing->fresh();
    }

    protected function createListingForWorkspace(int $workspaceId, string $initialState = 'taslak'): Ilan
    {
        // Set tenant context for property creation
        app(TenantContextService::class)->setTenant($this->tenantA);

        $property = $this->propertyService->create([
            'workspace_id' => $workspaceId,
            'il_id' => 48,
            'ilce_id' => 1,
            'mahalle_id' => 2,
            'lat' => '37.1042',
            'lng' => '27.2900',
            'ada' => '102',
            'parsel' => '4',
        ]);
        $this->propertyService->verify($property);
        $this->propertyService->activate($property);

        $listing = $this->listingService->createFromProperty($property, [
            'baslik' => "Workspace {$workspaceId} Listing",
            'kanal' => 'yalihan',
        ]);

        // Update workspace_id on listing
        DB::table('ilanlar')->where('id', $listing->id)->update([
            'tenant_id' => $this->tenantA->id,
            'workspace_id' => $workspaceId,
        ]);

        // Set initial state if needed
        if ($initialState !== 'taslak') {
            $this->lifecycle->transition($listing, \App\Enums\IlanDurumu::from($initialState));
        }

        return $listing->fresh();
    }

    protected function createWorkspace(int $tenantId): PropertyWorkspace
    {
        return PropertyWorkspace::create([
            'tenant_id' => $tenantId,
            'workspace_uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'state' => 'draft',
        ]);
    }

    protected function getStateValue(Ilan $ilan): string
    {
        return $ilan->yayin_durumu instanceof \App\Enums\IlanDurumu
            ? $ilan->yayin_durumu->value
            : (string) $ilan->yayin_durumu;
    }
}
