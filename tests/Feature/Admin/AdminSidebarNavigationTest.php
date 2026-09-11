<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * AdminSidebarNavigationTest — Comprehensive audit and navigation verification
 * for the admin sidebar and Property Engine consolidation (P1 Roadmap).
 *
 * Ensures:
 * 1. Every route in config('menus.admin.sidebar') exists in Laravel's route collection.
 * 2. Property Engine menu is fully consolidated with all 10 operational and schema tools.
 * 3. Authenticated super-admin user gets HTTP 200 for all sidebar routes without exceptions.
 * 4. Unauthenticated users are safely redirected to login (302).
 */
class AdminSidebarNavigationTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (!\Illuminate\Support\Facades\Schema::hasTable('ai_field_suggestions')) {
            \Illuminate\Support\Facades\Schema::create('ai_field_suggestions', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('slug', 100);
                $table->string('label', 255);
                $table->string('field_type', 50)->nullable();
                $table->string('group_name', 100)->nullable();
                $table->unsignedBigInteger('main_category_id')->default(1);
                $table->unsignedBigInteger('sub_category_id')->nullable();
                $table->unsignedBigInteger('listing_type_id')->default(1);
                $table->text('reason')->nullable();
                $table->json('score_json')->nullable();
                $table->unsignedSmallInteger('total_score')->default(0);
                $table->string('priority', 20)->default('medium');
                $table->string('source', 50)->default('ai_engine');
                $table->string('oneri_durumu', 20)->default('pending');
                $table->json('conflicts_json')->nullable();
                $table->unsignedBigInteger('feature_id')->nullable();
                $table->unsignedBigInteger('applied_assignment_id')->nullable();
                $table->timestamps();
            });
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('takim_uyeleri')) {
            \Illuminate\Support\Facades\Schema::create('takim_uyeleri', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('departman')->nullable();
                $table->string('rol')->nullable();
                $table->string('aktiflik_durumu')->default('aktif');
                $table->decimal('performans_skoru', 5, 2)->default(0.00);
                $table->string('lokasyon')->nullable();
                $table->string('telefon')->nullable();
                $table->string('eposta')->nullable();
                $table->string('profil_resmi')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('projeler')) {
            \Illuminate\Support\Facades\Schema::create('projeler', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('aciklama')->nullable();
                $table->string('proje_durumu')->default('aktif');
                $table->string('oncelik')->default('Orta');
                $table->date('baslangic_tarihi')->nullable();
                $table->date('bitis_tarihi')->nullable();
                $table->unsignedBigInteger('takim_lideri_id')->nullable();
                $table->decimal('butce', 15, 2)->nullable();
                $table->integer('tamamlanma_yuzdesi')->default(0);
                $table->text('notlar')->nullable();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            \Illuminate\Support\Facades\Schema::table('projeler', function (\Illuminate\Database\Schema\Blueprint $table) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('projeler', 'deleted_at')) {
                    $table->softDeletes();
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('projeler', 'aciklama')) {
                    $table->text('aciklama')->nullable();
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('projeler', 'oncelik')) {
                    $table->string('oncelik')->default('normal');
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('projeler', 'baslangic_tarihi')) {
                    $table->date('baslangic_tarihi')->nullable();
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('projeler', 'bitis_tarihi')) {
                    $table->date('bitis_tarihi')->nullable();
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('projeler', 'admin_id')) {
                    $table->unsignedBigInteger('admin_id')->nullable();
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('projeler', 'proje_durumu')) {
                    $table->string('proje_durumu')->default('aktif');
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('projeler', 'takim_lideri_id')) {
                    $table->unsignedBigInteger('takim_lideri_id')->nullable();
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('projeler', 'butce')) {
                    $table->decimal('butce', 15, 2)->nullable();
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('projeler', 'tamamlanma_yuzdesi')) {
                    $table->integer('tamamlanma_yuzdesi')->default(0);
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('projeler', 'notlar')) {
                    $table->text('notlar')->nullable();
                }
            });
        }

        $this->admin = User::factory()->admin()->create([
            'email' => 'sidebar-admin-' . uniqid() . '@yalihan.local',
        ]);
    }

    /**
     * Helper to recursively collect all routes defined in sidebar menu.
     *
     * @return array<string>
     */
    protected function getSidebarRouteNames(): array
    {
        $menus = config('menus.admin.sidebar', []);
        $routes = [];

        $collector = function (array $items) use (&$collector, &$routes) {
            foreach ($items as $item) {
                if (!empty($item['route'])) {
                    $routes[] = $item['route'];
                }
                if (!empty($item['children']) && is_array($item['children'])) {
                    $collector($item['children']);
                }
            }
        };

        $collector($menus);

        return array_unique($routes);
    }

    /**
     * Verify that every route name in config('menus.admin.sidebar') is defined in routes.
     */
    public function test_all_sidebar_routes_exist_in_route_collection(): void
    {
        $routes = $this->getSidebarRouteNames();

        $this->assertNotEmpty($routes, 'Sidebar routes configuration should not be empty.');

        foreach ($routes as $routeName) {
            $this->assertTrue(
                Route::has($routeName),
                "Sidebar route '{$routeName}' is defined in config/menus.php but does NOT exist in the route collection!"
            );
        }
    }

    /**
     * Verify that Property Engine (L2) menu group contains the canonical consolidated 10 tools.
     */
    public function test_property_engine_menu_contains_consolidated_10_tools(): void
    {
        $menus = config('menus.admin.sidebar', []);
        $propertyEngine = collect($menus)->firstWhere('id', 'property-engine');

        $this->assertNotNull($propertyEngine, 'Property Engine group (id: property-engine) must exist in config/menus.php.');
        $this->assertEquals('group', $propertyEngine['type']);

        $children = collect($propertyEngine['children'] ?? []);
        $this->assertCount(10, $children, 'Property Engine must contain exactly 10 consolidated tools.');

        $expectedTools = [
            'property-hub-dashboard' => 'admin.property-hub.index',
            'features' => 'admin.property-hub.features.index',
            'templates' => 'admin.property-hub.templates.index',
            'packs' => 'admin.property-hub.packs.index',
            'ilan-kategorileri' => 'admin.ilan-kategorileri.index',
            'ozellik-kategorileri' => 'admin.ozellikler.kategoriler.index',
            'property-types' => 'admin.property_types.index',
            'dependency-rules' => 'admin.property-hub.dependency-rules.index',
            'field-suggestions' => 'admin.property-hub.field-suggestions.index',
            'tkgm-parsel' => 'admin.tkgm-parsel.index',
        ];

        foreach ($expectedTools as $toolId => $expectedRoute) {
            $child = $children->firstWhere('id', $toolId);
            $this->assertNotNull($child, "Tool '{$toolId}' is missing from Property Engine menu.");
            $this->assertEquals($expectedRoute, $child['route'], "Tool '{$toolId}' has unexpected route.");
        }
    }

    /**
     * Verify that an authenticated super-admin can access all sidebar routes with HTTP 200.
     */
    public function test_authenticated_admin_can_access_all_sidebar_routes(): void
    {
        $routes = $this->getSidebarRouteNames();

        foreach ($routes as $routeName) {
            $url = route($routeName);
            $response = $this->actingAs($this->admin)->get($url);

            $errorMsg = $response->getStatusCode() !== 200 && $response->exception
                ? 'Details: ' . get_class($response->exception) . ': ' . $response->exception->getMessage()
                : '';

            $this->assertEquals(
                200,
                $response->getStatusCode(),
                "Route '{$routeName}' at '{$url}' returned status {$response->getStatusCode()} instead of 200 for authenticated admin. {$errorMsg}"
            );
        }
    }

    /**
     * Verify that unauthenticated requests to protected sidebar routes are redirected to login.
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $routes = $this->getSidebarRouteNames();

        // Sample critical routes across L1 to L5
        $sampleRoutes = [
            'admin.dashboard.index',
            'admin.property-hub.index',
            'admin.ilanlar.index',
            'admin.kisiler.index',
            'admin.takim.gorevler.index',
        ];

        foreach ($sampleRoutes as $routeName) {
            if (Route::has($routeName)) {
                $response = $this->get(route($routeName));
                $this->assertEquals(
                    302,
                    $response->getStatusCode(),
                    "Unauthenticated request to '{$routeName}' should redirect to login (302), got {$response->getStatusCode()}."
                );
            }
        }
    }
}
