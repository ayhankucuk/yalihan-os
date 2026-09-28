<?php

namespace Tests\Feature\Listing;

use App\Http\Controllers\Admin\IlanSearchController;
use App\Http\Controllers\Api\V2\IlanController;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Class IlanLookupRouteShadowingTest
 *
 * Task: ILAN_06_PORTAL_ROUTE_SHADOWING_REMEDIATION_02
 * Role: IMPLEMENTER
 * Decision Maker: Ayhan
 *
 * Permanent regression test suite verifying that named lookup routes
 * under /api/v1/ilanlar (by-portal, by-telefon, by-site) are NOT shadowed
 * by the wildcard show route /api/v1/ilanlar/{id}.
 */
class IlanLookupRouteShadowingTest extends TestCase
{
    /**
     * Prove that GET /api/v1/ilanlar/by-portal dispatches to
     * IlanSearchController@findByPortalId and not IlanController@show.
     */
    public function test_by_portal_route_dispatches_to_ilan_search_controller_and_not_shadowed(): void
    {
        $request = Request::create('/api/v1/ilanlar/by-portal', 'GET');
        $route = app('router')->getRoutes()->match($request);

        $this->assertSame(
            IlanSearchController::class.'@findByPortalId',
            $route->getActionName(),
            'Route /api/v1/ilanlar/by-portal must dispatch to IlanSearchController@findByPortalId'
        );
        $this->assertNotSame(
            IlanController::class.'@show',
            $route->getActionName(),
            'Route /api/v1/ilanlar/by-portal must NOT be shadowed by IlanController@show'
        );
        $this->assertSame('api.ilanlar.by-portal', $route->getName());

        // HTTP behavior: missing params returns 422 validation failure from findByPortalId, NOT 404 from show
        $response = $this->getJson('/api/v1/ilanlar/by-portal');
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['portal', 'id']);
    }

    /**
     * Prove that GET /api/v1/ilanlar/by-telefon dispatches to
     * IlanSearchController@findByTelefon and not IlanController@show.
     */
    public function test_by_telefon_route_dispatches_to_ilan_search_controller_and_not_shadowed(): void
    {
        $request = Request::create('/api/v1/ilanlar/by-telefon', 'GET');
        $route = app('router')->getRoutes()->match($request);

        $this->assertSame(
            IlanSearchController::class.'@findByTelefon',
            $route->getActionName(),
            'Route /api/v1/ilanlar/by-telefon must dispatch to IlanSearchController@findByTelefon'
        );
        $this->assertNotSame(
            IlanController::class.'@show',
            $route->getActionName(),
            'Route /api/v1/ilanlar/by-telefon must NOT be shadowed by IlanController@show'
        );
        $this->assertSame('api.ilanlar.by-telefon', $route->getName());

        // HTTP behavior: returns 200 with empty dataset
        $response = $this->getJson('/api/v1/ilanlar/by-telefon');
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [],
        ]);
    }

    /**
     * Prove that GET /api/v1/ilanlar/by-site dispatches to
     * IlanSearchController@findBySite and not IlanController@show.
     */
    public function test_by_site_route_dispatches_to_ilan_search_controller_and_not_shadowed(): void
    {
        $request = Request::create('/api/v1/ilanlar/by-site', 'GET');
        $route = app('router')->getRoutes()->match($request);

        $this->assertSame(
            IlanSearchController::class.'@findBySite',
            $route->getActionName(),
            'Route /api/v1/ilanlar/by-site must dispatch to IlanSearchController@findBySite'
        );
        $this->assertNotSame(
            IlanController::class.'@show',
            $route->getActionName(),
            'Route /api/v1/ilanlar/by-site must NOT be shadowed by IlanController@show'
        );
        $this->assertSame('api.ilanlar.by-site', $route->getName());

        // HTTP behavior: returns 200 with empty dataset
        $response = $this->getJson('/api/v1/ilanlar/by-site');
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [],
        ]);
    }

    /**
     * Prove that GET /api/v1/ilanlar/123 dispatches to
     * IlanController@show with id parameter = 123.
     */
    public function test_numeric_id_dispatches_to_ilan_controller_show(): void
    {
        $request = Request::create('/api/v1/ilanlar/123', 'GET');
        $route = app('router')->getRoutes()->match($request);

        $this->assertSame(
            IlanController::class.'@show',
            $route->getActionName(),
            'Route /api/v1/ilanlar/123 must dispatch to IlanController@show'
        );
        $this->assertSame('api.ilanlar.show', $route->getName());
        $this->assertSame('123', $route->parameter('id'));

        // HTTP behavior: non-existent ID returns 404 with İlan bulunamadı
        $response = $this->getJson('/api/v1/ilanlar/123');
        $response->assertStatus(404);
        $response->assertJson(['message' => 'İlan bulunamadı']);
    }

    /**
     * Prove that non-numeric slug does NOT dispatch to IlanController@show
     * due to whereNumber('id') constraint.
     */
    public function test_non_numeric_slug_does_not_dispatch_to_ilan_controller_show(): void
    {
        $response = $this->getJson('/api/v1/ilanlar/unknown-non-numeric-slug');
        // Because GET {id} only accepts numbers, an arbitrary non-numeric slug does not match GET,
        // and Laravel returns 405 (Method Not Allowed) due to PUT/DELETE {ilan} existing.
        $response->assertStatus(405);
    }
}
