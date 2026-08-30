<?php

namespace Tests\Feature\Property;

use Tests\TestCase;
use App\Models\Property;
use App\Models\SaaS\Tenant;
use App\Services\SaaS\TenantContextService;
use App\Domain\Property\ValueObjects\Location;
use App\Domain\Property\ValueObjects\TapuInfo;
use App\Domain\Property\ValueObjects\PhysicalSpecs;
use App\Domain\Property\Events\PropertyCreated;
use App\Domain\Property\Events\PropertyVerified;
use App\Domain\Property\Events\PropertyActivated;
use App\Services\Property\PropertyCrudService;
use App\Services\Property\PropertyStateMachine;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class PropertyAggregateTest extends TestCase
{
    protected Tenant $tenant;
    protected PropertyCrudService $crudService;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create properties table dynamically for Sprint 11 testing isolation
        // Drop if exists to ensure clean schema
        if (Schema::hasTable('properties')) {
            Schema::drop('properties');
        }

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->string('canonical_reference', 64)->nullable()->unique();
            $table->string('lifecycle_state')->default('DRAFT');
            $table->string('tkgm_id')->nullable(); // context7-ignore
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

        // 2. Setup tenant context
        $this->tenant = Tenant::create(['name' => 'Bodrum Luxury Real Estate', 'domain' => 'bodrum.yalihan.com']);
        app(TenantContextService::class)->setTenant($this->tenant);

        // 3. Resolve services
        $this->crudService = app(PropertyCrudService::class);
    }

    public function test_property_creation_defaults_to_draft()
    {
        $property = $this->crudService->create([
            'workspace_id' => 1,
            'nitelik' => 'Villa',
        ]);

        $this->assertNotNull($property->uuid);
        $this->assertEquals(PropertyStateMachine::STATE_DRAFT, $property->aktiflik_durumu);
    }

    public function test_value_objects_mapping()
    {
        $location = new Location(48, 12, 345, '37.1042', '27.2900');
        $tapuInfo = new TapuInfo('101', '5', 'tkgm-id-99'); // context7-ignore
        $specs = new PhysicalSpecs(350.5, '5+2', 4, '0');

        $property = $this->crudService->create([
            'workspace_id' => 1,
            'location' => $location,
            'tapu_info' => $tapuInfo,
            'physical_specs' => $specs,
            'nitelik' => 'Villa'
        ]);

        // Assert DB columns mapped
        $this->assertEquals(48, $property->il_id);
        $this->assertEquals(37.1042, (float) $property->lat);
        $this->assertEquals('101', $property->ada);
        $this->assertEquals(350.5, (float) $property->alan_m2);
        $this->assertEquals('5+2', $property->oda_sayisi);

        // Assert retrieving VOs back
        $retrievedLocation = $property->getLocation();
        $this->assertEquals(37.1042, (float) $retrievedLocation->getLat());
        $this->assertEquals(48, $retrievedLocation->getIlId());

        $retrievedTapu = $property->getTapuInfo();
        $this->assertEquals('101', $retrievedTapu->getAda());
        $this->assertEquals('tkgm-id-99', $retrievedTapu->getTkgmId()); // context7-ignore
    }

    public function test_verify_transition_fails_if_invariants_missing()
    {
        $property = $this->crudService->create([
            'workspace_id' => 1,
            'nitelik' => 'Villa',
        ]);

        // 1. Missing Location coordinates
        $this->expectException(\DomainException::class);
        $this->crudService->verify($property);
    }

    public function test_verify_transition_succeeds_when_all_invariants_met()
    {
        $property = $this->crudService->create([
            'workspace_id' => 1,
            'il_id' => 48,
            'ilce_id' => 1,
            'mahalle_id' => 2,
            'lat' => '37.1042',
            'lng' => '27.2900',
            'ada' => '102',
            'parsel' => '4',
            'nitelik' => 'Villa'
        ]);

        $this->crudService->verify($property);
        $this->assertEquals(PropertyStateMachine::STATE_VERIFIED, $property->aktiflik_durumu);
    }

    public function test_activate_transition_fails_if_not_verified()
    {
        $property = $this->crudService->create([
            'workspace_id' => 1,
            'nitelik' => 'Villa',
        ]);

        // Cannot activate directly from DRAFT
        $this->expectException(\DomainException::class);
        $this->crudService->activate($property);
    }

    public function test_activate_transition_succeeds_from_verified()
    {
        $property = $this->crudService->create([
            'workspace_id' => 1,
            'il_id' => 48,
            'ilce_id' => 1,
            'mahalle_id' => 2,
            'lat' => '37.1042',
            'lng' => '27.2900',
            'ada' => '102',
            'parsel' => '4',
            'nitelik' => 'Villa'
        ]);

        $this->crudService->verify($property);
        $this->crudService->activate($property);
        $this->assertEquals(PropertyStateMachine::STATE_ACTIVE, $property->aktiflik_durumu);
    }

    public function test_archive_transition_succeeds_from_any_state()
    {
        $property = $this->crudService->create([
            'workspace_id' => 1,
            'nitelik' => 'Villa',
        ]);

        $this->crudService->archive($property);
        $this->assertEquals(PropertyStateMachine::STATE_ARCHIVED, $property->aktiflik_durumu);
    }

    public function test_tenant_isolation_enforced()
    {
        // Property under Tenant A
        $propertyA = $this->crudService->create([
            'workspace_id' => 1,
            'nitelik' => 'Villa Tenant A',
        ]);

        // Switch context to Tenant B
        $tenantB = Tenant::create(['name' => 'Mugla Real Estate', 'domain' => 'mugla.yalihan.com']);
        app(TenantContextService::class)->setTenant($tenantB);

        // Property under Tenant B
        $propertyB = $this->crudService->create([
            'workspace_id' => 1,
            'nitelik' => 'Villa Tenant B',
        ]);

        // Assert Tenant B only sees Property B
        $allProperties = Property::all();
        $this->assertCount(1, $allProperties);
        $this->assertEquals($propertyB->id, $allProperties->first()->id);

        // Switch context back to Tenant A
        app(TenantContextService::class)->setTenant($this->tenant);

        // Assert Tenant A only sees Property A
        $allPropertiesA = Property::all();
        $this->assertCount(1, $allPropertiesA);
        $this->assertEquals($propertyA->id, $allPropertiesA->first()->id);
    }

    public function test_workspace_id_required_invariant()
    {
        // Invariant 1: Property cannot be created without workspace_id
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Property must belong to a Workspace.');
        $this->crudService->create([
            'nitelik' => 'Villa Without Workspace',
        ]);
    }

    public function test_tkgm_immutability_invariant()
    {
        $property = $this->crudService->create([
            'workspace_id' => 1,
            'tkgm_id' => 'tkgm-original',
            'ada' => '101',
            'parsel' => '5',
            'nitelik' => 'Villa',
        ]);

        // Invariant 3: TKGM identity cannot be modified after creation
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('TKGM identity is immutable');
        $this->crudService->update($property, [
            'tkgm_id' => 'tkgm-modified',
        ]);
    }

    public function test_tapu_ada_immutability_invariant()
    {
        $property = $this->crudService->create([
            'workspace_id' => 1,
            'ada' => 'original-ada',
            'nitelik' => 'Villa',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Tapu ada is immutable');
        $this->crudService->update($property, [
            'ada' => 'modified-ada',
        ]);
    }

    public function test_idempotency_key_returns_existing_property()
    {
        $key = 'idempotency-key-' . uniqid();

        $property1 = $this->crudService->create([
            'workspace_id' => 1,
            'nitelik' => 'First Creation',
            'idempotency_key' => $key,
        ]);

        $property2 = $this->crudService->create([
            'workspace_id' => 1,
            'nitelik' => 'Second Attempt — Should Not Duplicate',
            'idempotency_key' => $key,
        ]);

        // Invariant 6: Same idempotency key returns existing Property, no duplicate
        $this->assertEquals($property1->id, $property2->id);
        $this->assertEquals('First Creation', $property2->nitelik);

        // Verify only one property exists
        $this->assertCount(1, Property::all());
    }

    public function test_domain_events_are_dispatched_on_state_transitions()
    {
        Event::fake([PropertyCreated::class, PropertyVerified::class, PropertyActivated::class]);

        $property = $this->crudService->create([
            'workspace_id' => 1,
            'il_id' => 48,
            'ilce_id' => 1,
            'mahalle_id' => 2,
            'lat' => '37.1042',
            'lng' => '27.2900',
            'ada' => '102',
            'parsel' => '4',
            'nitelik' => 'Villa',
        ]);

        Event::assertDispatched(PropertyCreated::class);

        $this->crudService->verify($property);
        Event::assertDispatched(PropertyVerified::class);

        $this->crudService->activate($property);
        Event::assertDispatched(PropertyActivated::class);
    }
}
