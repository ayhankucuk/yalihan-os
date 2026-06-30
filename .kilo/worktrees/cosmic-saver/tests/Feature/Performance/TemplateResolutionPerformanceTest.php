<?php

namespace Tests\Feature\Performance;

use Tests\TestCase;
use App\Models\IlanKategori;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TemplateResolutionPerformanceTest extends TestCase
{

    /**
     * Performance Guard: Ensure template resolution doesn't cause N+1
     * 
     * @test
     * @group performance
     */
    public function template_resolution_is_efficient(): void
    {
        // Arrange
        IlanKategori::factory()->create(['id' => 6]);
        $admin = User::factory()->create(['role_id' => 1]);

        // Capture queries
        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            // Ignore migrations and user-factory setup if they happen here
            if (str_contains($query->sql, 'select * from `ilan_templates`')) {
                $queryCount++;
            }
            if (str_contains($query->sql, 'select * from `feature_assignments`')) {
                $queryCount++;
            }
        });

        // Act
        $response = $this->actingAs($admin, 'web')
            ->getJson("/api/v1/admin/template/field-visibility/6/3");

        // Assert
        $this->assertLessThanOrEqual(5, $queryCount, "Template resolution caused too many queries ({$queryCount}). Potential N+1 detected.");
    }
}
