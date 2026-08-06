<?php

namespace Tests\Feature\ConflictDetection;

use App\Contracts\Property\ConflictDetectionContract;
use App\Contracts\Property\ConflictOverrideContract;
use App\Contracts\Property\PropertyAvailabilityContract;
use App\DTOs\Property\OverrideAuditRecord;
use App\DTOs\Property\OverrideResult;
use App\Events\Reservation\ConflictOverriddenEvent;
use App\Exceptions\Reservation\OverrideNotAuthorizedException;
use App\Exceptions\Reservation\OverrideReasonRequiredException;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Property\ConflictOverrideService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * CONFLICT_DETECTION Phase 3C — Override Authorization
 *
 * SAAB mandated tests (ADR-003):
 * OA01: override_requires_authorization
 * OA02: unauthorized_actor_cannot_override
 * OA03: override_requires_reason
 * OA04: empty_reason_is_rejected
 * OA05: override_creates_audit_record
 * OA06: override_dispatches_conflict_overridden_event
 * OA07: admin_can_override
 * OA08: super_admin_can_override
 * OA09: owner_cannot_override
 * OA10: danisman_cannot_override
 * OA11: audit_record_contains_actor_and_reason
 * OA12: override_service_is_bound_in_container
 */
class ConflictOverrideAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected ConflictOverrideContract $overrideService;
    protected ConflictDetectionContract $detector;
    protected Tenant $tenant;
    protected Ilan $property;

    protected User $adminUser;
    protected User $superAdminUser;
    protected User $ownerUser;
    protected User $danismanUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideService = app(ConflictOverrideContract::class);
        $this->detector        = app(ConflictDetectionContract::class);

        $this->tenant = Tenant::create([
            'name'      => 'Override Tenant',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->property = Ilan::create([
            'baslik'          => 'Override Property',
            'fiyat'           => 1000,
            'para_birimi'     => 'TRY',
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => true,
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
        ]);

        DB::table('ilanlar')
            ->where('id', $this->property->id)
            ->update(['tenant_id' => $this->tenant->id]);

        // Ensure Spatie roles exist in test DB (RefreshDatabase wipes them)
        Role::firstOrCreate(['name' => 'admin',       'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'owner',       'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'danisman',    'guard_name' => 'web']);

        // Create users with different roles
        $this->adminUser      = User::factory()->create();
        $this->superAdminUser = User::factory()->create();
        $this->ownerUser      = User::factory()->create();
        $this->danismanUser   = User::factory()->create();
        $this->regularUser    = User::factory()->create();

        // Assign roles using Spatie
        $this->adminUser->assignRole('admin');
        $this->superAdminUser->assignRole('super-admin');
        $this->ownerUser->assignRole('owner');
        $this->danismanUser->assignRole('danisman');
        // regularUser has no role
    }

    // Helper: block dates in PropertyAvailability
    private function blockDates(string $startDate, string $endDate): void
    {
        $current = \Carbon\Carbon::parse($startDate);
        $end     = \Carbon\Carbon::parse($endDate);
        while ($current->lt($end)) {
            PropertyAvailability::create([
                'tenant_id'     => $this->tenant->id,
                'property_id'   => $this->property->id,
                'date'          => $current->format('Y-m-d'),
                'is_available'  => false,
                'block_reason'  => 'reservation',
                'priority_tier' => PropertyAvailabilityContract::TIER_RESERVATION,
                'source_system' => 'internal',
                'origin'        => PropertyAvailabilityContract::ORIGIN_RESERVATION,
            ]);
            $current->addDay();
        }
    }

    private function conflictData(): array
    {
        return [
            'conflict_dates'   => ['2036-01-10', '2036-01-11'],
            'blocking_sources' => [['origin' => 'reservation', 'date' => '2036-01-10']],
        ];
    }

    // =========================================================================
    // OA01: override_requires_authorization
    // =========================================================================

    /** @test */
    public function override_requires_authorization(): void
    {
        // Without a valid authorized user, override must throw
        $this->expectException(OverrideNotAuthorizedException::class);

        $this->overrideService->override(
            actorUserId:  $this->regularUser->id,
            tenantId:     $this->tenant->id,
            propertyId:   $this->property->id,
            startDate:    '2036-01-10',
            endDate:      '2036-01-13',
            reason:       'Force booking for VIP guest',
            conflictData: $this->conflictData()
        );
    }

    // =========================================================================
    // OA02: unauthorized_actor_cannot_override
    // =========================================================================

    /** @test */
    public function unauthorized_actor_cannot_override(): void
    {
        $this->assertFalse(
            $this->overrideService->canOverride($this->regularUser->id),
            'Regular user must not be authorized to override'
        );
    }

    // =========================================================================
    // OA03: override_requires_reason
    // =========================================================================

    /** @test */
    public function override_requires_reason(): void
    {
        $this->expectException(OverrideReasonRequiredException::class);

        $this->overrideService->override(
            actorUserId:  $this->adminUser->id,
            tenantId:     $this->tenant->id,
            propertyId:   $this->property->id,
            startDate:    '2036-02-01',
            endDate:      '2036-02-05',
            reason:       '',   // empty reason
            conflictData: $this->conflictData()
        );
    }

    // =========================================================================
    // OA04: empty_reason_is_rejected
    // =========================================================================

    /** @test */
    public function empty_reason_is_rejected(): void
    {
        $this->expectException(OverrideReasonRequiredException::class);

        $this->overrideService->override(
            actorUserId:  $this->adminUser->id,
            tenantId:     $this->tenant->id,
            propertyId:   $this->property->id,
            startDate:    '2036-03-01',
            endDate:      '2036-03-05',
            reason:       '   ',  // whitespace only
            conflictData: $this->conflictData()
        );
    }

    // =========================================================================
    // OA05: override_creates_audit_record
    // =========================================================================

    /** @test */
    public function override_creates_audit_record(): void
    {
        $result = $this->overrideService->override(
            actorUserId:  $this->adminUser->id,
            tenantId:     $this->tenant->id,
            propertyId:   $this->property->id,
            startDate:    '2036-04-10',
            endDate:      '2036-04-13',
            reason:       'VIP guest — management approved',
            conflictData: $this->conflictData()
        );

        $this->assertInstanceOf(OverrideResult::class, $result);
        $this->assertTrue($result->authorized);
        $this->assertInstanceOf(OverrideAuditRecord::class, $result->auditRecord);

        // Audit trail must contain this record
        $trail = $this->overrideService->getAuditTrail($this->tenant->id, $this->property->id);
        $this->assertCount(1, $trail);
        $this->assertEquals($this->adminUser->id, $trail[0]->actorUserId);
        $this->assertEquals('VIP guest — management approved', $trail[0]->reason);
    }

    // =========================================================================
    // OA06: override_dispatches_conflict_overridden_event
    // =========================================================================

    /** @test */
    public function override_dispatches_conflict_overridden_event(): void
    {
        Event::fake([ConflictOverriddenEvent::class]);

        $this->overrideService->override(
            actorUserId:  $this->adminUser->id,
            tenantId:     $this->tenant->id,
            propertyId:   $this->property->id,
            startDate:    '2036-05-01',
            endDate:      '2036-05-05',
            reason:       'Approved by property manager',
            conflictData: $this->conflictData()
        );

        Event::assertDispatched(ConflictOverriddenEvent::class, function ($event) {
            return $event->actorUserId === $this->adminUser->id
                && $event->tenantId === $this->tenant->id
                && $event->propertyId === $this->property->id
                && !empty($event->reason);
        });
    }

    // =========================================================================
    // OA07: admin_can_override
    // =========================================================================

    /** @test */
    public function admin_can_override(): void
    {
        $this->assertTrue(
            $this->overrideService->canOverride($this->adminUser->id),
            'Admin must be authorized to override'
        );

        $result = $this->overrideService->override(
            actorUserId:  $this->adminUser->id,
            tenantId:     $this->tenant->id,
            propertyId:   $this->property->id,
            startDate:    '2036-06-01',
            endDate:      '2036-06-05',
            reason:       'Admin override — special circumstance',
            conflictData: $this->conflictData()
        );

        $this->assertTrue($result->authorized);
    }

    // =========================================================================
    // OA08: super_admin_can_override
    // =========================================================================

    /** @test */
    public function super_admin_can_override(): void
    {
        $this->assertTrue(
            $this->overrideService->canOverride($this->superAdminUser->id),
            'Super-admin must be authorized to override'
        );

        $result = $this->overrideService->override(
            actorUserId:  $this->superAdminUser->id,
            tenantId:     $this->tenant->id,
            propertyId:   $this->property->id,
            startDate:    '2036-07-01',
            endDate:      '2036-07-05',
            reason:       'Super-admin override — executive decision',
            conflictData: $this->conflictData()
        );

        $this->assertTrue($result->authorized);
        $this->assertEquals($this->superAdminUser->id, $result->actorUserId);
    }

    // =========================================================================
    // OA09: owner_cannot_override
    // =========================================================================

    /** @test */
    public function owner_cannot_override(): void
    {
        $this->assertFalse(
            $this->overrideService->canOverride($this->ownerUser->id),
            'Owner must NOT be authorized to override (ADR-003)'
        );

        $this->expectException(OverrideNotAuthorizedException::class);

        $this->overrideService->override(
            actorUserId:  $this->ownerUser->id,
            tenantId:     $this->tenant->id,
            propertyId:   $this->property->id,
            startDate:    '2036-08-01',
            endDate:      '2036-08-05',
            reason:       'Owner wants to override',
            conflictData: $this->conflictData()
        );
    }

    // =========================================================================
    // OA10: danisman_cannot_override
    // =========================================================================

    /** @test */
    public function danisman_cannot_override(): void
    {
        $this->assertFalse(
            $this->overrideService->canOverride($this->danismanUser->id),
            'Danışman must NOT be authorized to override'
        );

        $this->expectException(OverrideNotAuthorizedException::class);

        $this->overrideService->override(
            actorUserId:  $this->danismanUser->id,
            tenantId:     $this->tenant->id,
            propertyId:   $this->property->id,
            startDate:    '2036-09-01',
            endDate:      '2036-09-05',
            reason:       'Danisman wants to override',
            conflictData: $this->conflictData()
        );
    }

    // =========================================================================
    // OA11: audit_record_contains_actor_and_reason
    // =========================================================================

    /** @test */
    public function audit_record_contains_actor_and_reason(): void
    {
        $reason = 'Corporate booking — CEO suite reservation';

        $result = $this->overrideService->override(
            actorUserId:  $this->adminUser->id,
            tenantId:     $this->tenant->id,
            propertyId:   $this->property->id,
            startDate:    '2036-10-01',
            endDate:      '2036-10-05',
            reason:       $reason,
            conflictData: $this->conflictData()
        );

        $record = $result->auditRecord;

        $this->assertEquals($this->adminUser->id, $record->actorUserId);
        $this->assertEquals($this->tenant->id, $record->tenantId);
        $this->assertEquals($this->property->id, $record->propertyId);
        $this->assertEquals($reason, $record->reason);
        $this->assertEquals('2036-10-01', $record->startDate);
        $this->assertEquals('2036-10-05', $record->endDate);
        $this->assertNotEmpty($record->overrideId);
        $this->assertNotEmpty($record->correlationId);
        $this->assertInstanceOf(\DateTimeImmutable::class, $record->overriddenAt);

        // toArray must be serializable
        $array = $record->toArray();
        $this->assertArrayHasKey('override_id', $array);
        $this->assertArrayHasKey('actor_user_id', $array);
        $this->assertArrayHasKey('reason', $array);
        $this->assertArrayHasKey('overridden_at', $array);
    }

    // =========================================================================
    // OA12: override_service_is_bound_in_container
    // =========================================================================

    /** @test */
    public function override_service_is_bound_in_container(): void
    {
        $service = app(ConflictOverrideContract::class);

        $this->assertInstanceOf(ConflictOverrideService::class, $service);
    }
}
