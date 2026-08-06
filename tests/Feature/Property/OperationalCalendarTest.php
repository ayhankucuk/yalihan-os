<?php

namespace Tests\Feature\Property;

use App\Contracts\Property\OperationalCalendarContract;
use App\DTOs\Property\CalendarEntry;
use App\DTOs\Property\CalendarView;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\Tenant;
use App\Services\Property\CanonicalAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * OperationalCalendarTest
 *
 * OPERATIONAL_CALENDAR — SAAB Mandated Test Suite (12 tests)
 *
 * Contract:
 * - READ-ONLY: never writes to the database
 * - Reads from PropertyAvailability projection (canonical SSOT)
 * - Deterministic: same input → same CalendarView
 * - Tenant-isolated: cross-tenant data invisible
 * - Date semantics: [startDate, endDate) — inclusive-exclusive
 */
class OperationalCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Tenant $otherTenant;
    protected Ilan $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'     => 'Bodrum Luxury Stays',
            'status'   => 'active',
            'is_active' => true,
        ]);

        $this->otherTenant = Tenant::create([
            'name'     => 'Other Tenant Co',
            'status'   => 'active',
            'is_active' => true,
        ]);

        $this->property = Ilan::create([
            'baslik'           => 'Yalıhan Luxury Villa Yalıkavak',
            'para_birimi'      => 'TRY',
            'fiyat'            => 50000.00,
            'yayin_durumu'     => 'aktif',
            'aktiflik_durumu'  => true,
            'tenant_id'        => $this->tenant->id,
        ]);
    }

    /** @test */
    public function calendar_returns_all_dates_in_range(): void
    {
        $service = app(OperationalCalendarContract::class);

        $result = $service->getCalendar(
            $this->tenant->id,
            $this->property->id,
            '2026-09-01',
            '2026-09-05'
        );

        // [2026-09-01, 2026-09-05) = 4 nights: Sep 1, 2, 3, 4
        $this->assertEquals(4, $result->totalNights);
        $this->assertCount(4, $result->entries);

        $dates = array_map(fn(CalendarEntry $e) => $e->date, $result->entries);
        $this->assertEquals(['2026-09-01', '2026-09-02', '2026-09-03', '2026-09-04'], $dates);
    }

    /** @test */
    public function confirmed_reservation_appears_as_blocked(): void
    {
        // Create a confirmed reservation
        $reservation = PropertyReservation::create([
            'tenant_id'         => $this->tenant->id,
            'property_id'       => $this->property->id,
            'start_date'        => '2026-09-01',
            'end_date'          => '2026-09-04',
            'reservation_state' => 'confirmed',
        ]);

        // Build projection manually (as CanonicalAvailabilityService would)
        PropertyAvailability::create([
            'tenant_id'    => $this->tenant->id,
            'property_id'  => $this->property->id,
            'date'         => '2026-09-01',
            'is_available' => false,
            'block_reason' => 'reservation',
            'priority_tier' => CanonicalAvailabilityService::TIER_RESERVATION,
            'origin'       => 'reservation',
            'reservation_id' => $reservation->id,
            'source_system' => 'internal',
        ]);
        PropertyAvailability::create([
            'tenant_id'    => $this->tenant->id,
            'property_id'  => $this->property->id,
            'date'         => '2026-09-02',
            'is_available' => false,
            'block_reason' => 'reservation',
            'priority_tier' => CanonicalAvailabilityService::TIER_RESERVATION,
            'origin'       => 'reservation',
            'reservation_id' => $reservation->id,
            'source_system' => 'internal',
        ]);
        PropertyAvailability::create([
            'tenant_id'    => $this->tenant->id,
            'property_id'  => $this->property->id,
            'date'         => '2026-09-03',
            'is_available' => false,
            'block_reason' => 'reservation',
            'priority_tier' => CanonicalAvailabilityService::TIER_RESERVATION,
            'origin'       => 'reservation',
            'reservation_id' => $reservation->id,
            'source_system' => 'internal',
        ]);

        $service = app(OperationalCalendarContract::class);
        $result = $service->getCalendar(
            $this->tenant->id,
            $this->property->id,
            '2026-09-01',
            '2026-09-04'
        );

        $this->assertEquals(3, $result->blockedNights);
        $this->assertEquals(0, $result->availableNights);

        foreach ($result->entries as $entry) {
            $this->assertFalse($entry->isAvailable);
            $this->assertEquals(CalendarEntry::TYPE_CONFIRMED_RESERVATION, $entry->entryType);
            $this->assertEquals('reservation', $entry->origin);
            $this->assertEquals($reservation->id, $entry->reservationId);
        }
    }

    /** @test */
    public function owner_block_appears_in_calendar(): void
    {
        PropertyAvailability::create([
            'tenant_id'    => $this->tenant->id,
            'property_id'  => $this->property->id,
            'date'         => '2026-09-10',
            'is_available' => false,
            'block_reason' => 'Owner Personal Stay',
            'priority_tier' => CanonicalAvailabilityService::TIER_OWNER_BLOCK,
            'origin'       => 'owner',
            'source_system' => 'internal',
        ]);

        $service = app(OperationalCalendarContract::class);
        $result = $service->getCalendar(
            $this->tenant->id,
            $this->property->id,
            '2026-09-10',
            '2026-09-11'
        );

        $this->assertEquals(1, $result->blockedNights);
        $entry = $result->entries[0];
        $this->assertEquals(CalendarEntry::TYPE_OWNER_BLOCK, $entry->entryType);
        $this->assertEquals('owner', $entry->origin);
        $this->assertEquals(CanonicalAvailabilityService::TIER_OWNER_BLOCK, $entry->priorityTier);
    }

    /** @test */
    public function maintenance_block_appears_in_calendar(): void
    {
        PropertyAvailability::create([
            'tenant_id'    => $this->tenant->id,
            'property_id'  => $this->property->id,
            'date'         => '2026-09-15',
            'is_available' => false,
            'block_reason' => 'Pool Maintenance',
            'priority_tier' => CanonicalAvailabilityService::TIER_MAINTENANCE,
            'origin'       => 'maintenance',
            'source_system' => 'internal',
        ]);

        $service = app(OperationalCalendarContract::class);
        $result = $service->getCalendar(
            $this->tenant->id,
            $this->property->id,
            '2026-09-15',
            '2026-09-16'
        );

        $this->assertEquals(1, $result->blockedNights);
        $entry = $result->entries[0];
        $this->assertEquals(CalendarEntry::TYPE_MAINTENANCE, $entry->entryType);
        $this->assertEquals('maintenance', $entry->origin);
        $this->assertEquals(CanonicalAvailabilityService::TIER_MAINTENANCE, $entry->priorityTier);
    }

    /** @test */
    public function external_block_appears_in_calendar(): void
    {
        PropertyAvailability::create([
            'tenant_id'    => $this->tenant->id,
            'property_id'  => $this->property->id,
            'date'         => '2026-09-20',
            'is_available' => false,
            'block_reason' => 'Airbnb Reservation',
            'priority_tier' => CanonicalAvailabilityService::TIER_EXTERNAL_SYNC,
            'origin'       => 'airbnb',
            'source_system' => 'airbnb',
        ]);

        $service = app(OperationalCalendarContract::class);
        $result = $service->getCalendar(
            $this->tenant->id,
            $this->property->id,
            '2026-09-20',
            '2026-09-21'
        );

        $this->assertEquals(1, $result->blockedNights);
        $entry = $result->entries[0];
        $this->assertEquals(CalendarEntry::TYPE_AIRBNB_BLOCK, $entry->entryType);
        $this->assertEquals('airbnb', $entry->origin);
        $this->assertEquals('airbnb', $entry->sourceSystem);
    }

    /** @test */
    public function available_dates_are_correctly_reported(): void
    {
        // No PropertyAvailability rows = available
        $service = app(OperationalCalendarContract::class);
        $result = $service->getCalendar(
            $this->tenant->id,
            $this->property->id,
            '2026-09-01',
            '2026-09-05'
        );

        $this->assertEquals(4, $result->availableNights);
        $this->assertEquals(0, $result->blockedNights);

        foreach ($result->entries as $entry) {
            $this->assertTrue($entry->isAvailable);
            $this->assertEquals(CalendarEntry::TYPE_AVAILABLE, $entry->entryType);
        }
    }

    /** @test */
    public function calendar_is_tenant_scoped(): void
    {
        // Create a block for the OTHER tenant
        PropertyAvailability::create([
            'tenant_id'    => $this->otherTenant->id,
            'property_id'  => $this->property->id,
            'date'         => '2026-09-10',
            'is_available' => false,
            'block_reason' => 'Other Tenant Reservation',
            'priority_tier' => CanonicalAvailabilityService::TIER_RESERVATION,
            'origin'       => 'reservation',
            'source_system' => 'internal',
        ]);

        $service = app(OperationalCalendarContract::class);
        $result = $service->getCalendar(
            $this->tenant->id,
            $this->property->id,
            '2026-09-10',
            '2026-09-11'
        );

        // Cross-tenant data should NOT be visible
        $this->assertEquals(1, $result->availableNights);
        $this->assertEquals(0, $result->blockedNights);
        $entry = $result->entries[0];
        $this->assertTrue($entry->isAvailable);
        $this->assertEquals(CalendarEntry::TYPE_AVAILABLE, $entry->entryType);
    }

    /** @test */
    public function calendar_is_read_only(): void
    {
        $service = app(OperationalCalendarContract::class);

        // Get initial count of PropertyAvailability rows
        $initialCount = PropertyAvailability::count();

        // Call getCalendar multiple times
        for ($i = 0; $i < 5; $i++) {
            $service->getCalendar(
                $this->tenant->id,
                $this->property->id,
                '2026-09-01',
                '2026-09-05'
            );
        }

        // No new rows should be created
        $this->assertEquals($initialCount, PropertyAvailability::count());

        // No rows should be updated
        $this->assertDatabaseCount('property_availabilities', $initialCount);
    }

    /** @test */
    public function calendar_is_deterministic(): void
    {
        $service = app(OperationalCalendarContract::class);

        // Create some availability data
        PropertyAvailability::create([
            'tenant_id'    => $this->tenant->id,
            'property_id'  => $this->property->id,
            'date'         => '2026-09-01',
            'is_available' => false,
            'block_reason' => 'Reservation',
            'priority_tier' => CanonicalAvailabilityService::TIER_RESERVATION,
            'origin'       => 'reservation',
            'source_system' => 'internal',
        ]);

        // Call getCalendar 10 times with same params
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $service->getCalendar(
                $this->tenant->id,
                $this->property->id,
                '2026-09-01',
                '2026-09-03'
            );
        }

        // All results should be identical
        $firstResult = $results[0]->toArray();
        for ($i = 1; $i < count($results); $i++) {
            $this->assertEquals($firstResult, $results[$i]->toArray());
        }
    }

    /** @test */
    public function summary_counts_are_accurate(): void
    {
        // Create mixed availability
        PropertyAvailability::create([
            'tenant_id'    => $this->tenant->id,
            'property_id'  => $this->property->id,
            'date'         => '2026-09-01',
            'is_available' => false,
            'block_reason' => 'Owner Stay',
            'priority_tier' => CanonicalAvailabilityService::TIER_OWNER_BLOCK,
            'origin'       => 'owner',
            'source_system' => 'internal',
        ]);
        PropertyAvailability::create([
            'tenant_id'    => $this->tenant->id,
            'property_id'  => $this->property->id,
            'date'         => '2026-09-02',
            'is_available' => false,
            'block_reason' => 'Reservation',
            'priority_tier' => CanonicalAvailabilityService::TIER_RESERVATION,
            'origin'       => 'reservation',
            'source_system' => 'internal',
        ]);
        // Sep 03 is available (no row)

        $service = app(OperationalCalendarContract::class);
        $result = $service->getCalendar(
            $this->tenant->id,
            $this->property->id,
            '2026-09-01',
            '2026-09-04'
        );

        $this->assertEquals(3, $result->totalNights);
        $this->assertEquals(1, $result->availableNights);
        $this->assertEquals(2, $result->blockedNights);

        // Summary counts should match
        $this->assertEquals(1, $result->summary[CalendarEntry::TYPE_OWNER_BLOCK] ?? 0);
        $this->assertEquals(1, $result->summary[CalendarEntry::TYPE_CONFIRMED_RESERVATION] ?? 0);

        // Sum of summary values should equal blockedNights
        $summaryTotal = array_sum($result->summary);
        $this->assertEquals($result->blockedNights, $summaryTotal);
    }

    /** @test */
    public function priority_tier_is_correctly_mapped(): void
    {
        // Maintenance should have priority_tier = 1
        PropertyAvailability::create([
            'tenant_id'    => $this->tenant->id,
            'property_id'  => $this->property->id,
            'date'         => '2026-09-15',
            'is_available' => false,
            'block_reason' => 'Pool Repair',
            'priority_tier' => CanonicalAvailabilityService::TIER_MAINTENANCE,
            'origin'       => 'maintenance',
            'source_system' => 'internal',
        ]);

        $service = app(OperationalCalendarContract::class);
        $result = $service->getCalendar(
            $this->tenant->id,
            $this->property->id,
            '2026-09-15',
            '2026-09-16'
        );

        $entry = $result->entries[0];
        $this->assertEquals(CanonicalAvailabilityService::TIER_MAINTENANCE, $entry->priorityTier);
        $this->assertEquals(1, $entry->priorityTier);
    }

    /** @test */
    public function calendar_service_is_bound_in_container(): void
    {
        // Verify the service can be resolved from the container
        $service = app(OperationalCalendarContract::class);
        $this->assertInstanceOf(\App\Services\Property\OperationalCalendarService::class, $service);

        // Verify it's a singleton (same instance each time)
        $service1 = app(OperationalCalendarContract::class);
        $service2 = app(OperationalCalendarContract::class);
        $this->assertSame($service1, $service2);
    }

    /** @test */
    public function throws_exception_for_invalid_date_range(): void
    {
        $service = app(OperationalCalendarContract::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('startDate must be strictly before endDate');

        $service->getCalendar(
            $this->tenant->id,
            $this->property->id,
            '2026-09-10',
            '2026-09-05'  // end before start
        );
    }
}
