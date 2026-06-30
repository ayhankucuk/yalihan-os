<?php

namespace Tests\Feature\PropertyEngine;

use App\Models\Feature;
use App\Models\FeatureCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Route determinism tests for Property Hub feature routes.
 *
 * Ensures static routes (/categories, /create) are never shadowed
 * by {feature} wildcard route.
 */
class FeatureRouteResolutionTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $roleClass = \App\Models\Role::class;
        if (! $roleClass::where('name', 'admin')->where('guard_name', 'web')->exists()) {
            $roleClass::create(['name' => 'admin', 'guard_name' => 'web']);
        }
        DB::table('model_has_roles')->insertOrIgnore([
            'role_id' => $roleClass::where('name', 'admin')->first()->id,
            'model_type' => User::class,
            'model_id' => $this->admin->id,
        ]);
    }

    // ── Static route NOT shadowed by wildcard ──

    /** @test */
    public function categories_route_resolves_to_feature_categories_handler(): void
    {
        $route = Route::getRoutes()->match(
            request()->create(route('admin.property-hub.features.categories.index'), 'GET')
        );

        $this->assertEquals(
            'admin.property-hub.features.categories.index',
            $route->getName(),
            'categories route must resolve to its own named route, not {feature} wildcard'
        );
    }

    /** @test */
    public function categories_url_resolves_to_correct_handler(): void
    {
        // Verify the URL resolves to the categories handler, not {feature} wildcard
        $routeRequest = request()->create('/admin/property-hub/features/categories', 'GET');
        $resolved = Route::getRoutes()->match($routeRequest);

        $this->assertEquals('admin.property-hub.features.categories.index', $resolved->getName());

        // The action should point to featureCategories, not the show/edit closure
        $action = $resolved->getActionName();
        $this->assertStringContainsString('featureCategories', $action);
    }

    /** @test */
    public function create_route_not_shadowed_by_wildcard(): void
    {
        $route = Route::getRoutes()->match(
            request()->create('/admin/property-hub/features/create', 'GET')
        );

        $this->assertEquals(
            'admin.property-hub.features.create',
            $route->getName(),
            '/create must not be caught by {feature} wildcard'
        );
    }

    // ── Wildcard constraint validation ──

    /** @test */
    public function wildcard_accepts_numeric_ids(): void
    {
        $feature = Feature::factory()->create([
            'feature_category_id' => FeatureCategory::factory()->create()->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/property-hub/features/{$feature->id}");

        // show route redirects to edit
        $response->assertRedirect();
    }

    /** @test */
    public function wildcard_rejects_non_numeric_strings(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/property-hub/features/not-a-number');

        $response->assertNotFound();
    }

    /** @test */
    public function edit_route_works_with_numeric_id(): void
    {
        $feature = Feature::factory()->create([
            'feature_category_id' => FeatureCategory::factory()->create()->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/property-hub/features/{$feature->id}/edit");

        $response->assertSuccessful();
    }
}
