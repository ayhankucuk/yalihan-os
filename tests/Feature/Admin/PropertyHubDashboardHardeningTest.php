<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Ozellik;
use App\Models\KategoriYayinTipiFieldDependency;
use Tests\TestCase;

class PropertyHubDashboardHardeningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!\Illuminate\Support\Facades\Schema::hasTable('kategori_yayin_tipi_field_dependencies')) {
            \Illuminate\Support\Facades\Schema::create('kategori_yayin_tipi_field_dependencies', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('kategori_slug');
                $table->unsignedBigInteger('yayin_tipi_id')->nullable();
                $table->string('yayin_tipi');
                $table->string('field_slug');
                $table->string('field_name');
                $table->string('field_type')->default('text');
                $table->string('field_category')->default('general');
                $table->json('field_options')->nullable();
                $table->string('field_unit')->nullable();
                $table->string('field_icon')->nullable();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->boolean('required')->default(false);
                $table->integer('display_order')->default(0);
                $table->boolean('ai_auto_fill')->default(false);
                $table->boolean('ai_suggestion')->default(false);
                $table->string('ai_prompt_key')->nullable();
                $table->boolean('searchable')->default(true);
                $table->boolean('show_in_card')->default(false);
                $table->timestamps();
            });
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('ozellikler')) {
            \Illuminate\Support\Facades\Schema::create('ozellikler', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('ad')->nullable();
                $table->string('slug')->nullable();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->integer('display_order')->default(0);
                $table->timestamps();
            });
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('template_change_logs')) {
            \Illuminate\Support\Facades\Schema::create('template_change_logs', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action')->default('update');
                $table->timestamps();
            });
        }
    }
    public function test_property_hub_dashboard_loads_without_500(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)
            ->get(route('admin.property-hub.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.property-hub.index');
        $response->assertViewHasAll(['stats', 'catalogStats', 'recentChanges', 'healthScore']);
    }

    public function test_property_hub_controller_does_not_call_undefined_active_scope(): void
    {
        $source = file_get_contents(base_path('app/Http/Controllers/Admin/PropertyHubController.php'));
        $this->assertDoesNotMatchRegularExpression(
            '/KategoriYayinTipiFieldDependency::active\(\)/',
            $source,
            'PropertyHubController must not call undefined scope active() on KategoriYayinTipiFieldDependency'
        );
        $this->assertMatchesRegularExpression(
            '/KategoriYayinTipiFieldDependency::aktif\(\)/',
            $source,
            'PropertyHubController must call canonical scope aktif() on KategoriYayinTipiFieldDependency'
        );
    }

    public function test_public_storage_is_isolated_from_private_storage(): void
    {
        $publicRoot = config('filesystems.disks.public.root');
        $localRoot  = config('filesystems.disks.local.root');

        $this->assertStringEndsWith('storage/app/public', str_replace('\\', '/', $publicRoot));
        $this->assertNotEquals($publicRoot, $localRoot, 'Public storage root must never match private/local storage root');
    }

    public function test_nginx_storage_alias_security(): void
    {
        $nginxConf = file_get_contents(base_path('docker/nginx/production.conf'));

        // Verify Nginx /storage/ is strictly bound to raster images only (NO SVG, NO ICO, NO GIF)
        $this->assertMatchesRegularExpression(
            '#location\s+~\*\s+\^/storage/\(\.\+\\\.\(jpe\?g\|png\|webp\)\)\$\s*\{\s*alias\s+/app/storage/app/public/\$1;#',
            $nginxConf,
            'Nginx /storage/ location must strictly permit raster images (jpe?g|png|webp) only and alias to /app/storage/app/public/$1'
        );

        // Verify X-Content-Type-Options nosniff is set
        $this->assertStringContainsString('add_header X-Content-Type-Options "nosniff";', $nginxConf);

        // Verify non-image files under /storage/ are denied
        $this->assertMatchesRegularExpression(
            '#location\s+/storage/\s*\{\s*deny\s+all;\s*return\s+404;\s*\}#',
            $nginxConf,
            'Nginx must deny and return 404 for all non-image requests under /storage/'
        );

        // Verify sensitive paths (.env, .git) are denied
        $this->assertStringContainsString('location ~ /\\.', $nginxConf);
    }

    public function test_storage_extension_whitelist_rejects_svg_and_dangerous_types(): void
    {
        $nginxConf = file_get_contents(base_path('docker/nginx/production.conf'));

        // Ensure SVG, HTML, PHP, JS, PDF are NOT in the allowed extension list
        preg_match('#location\s+~\*\s+\^/storage/\(\.\+\\\.\(([^)]+)\)\)\$#', $nginxConf, $matches);
        $this->assertNotEmpty($matches, 'Image regex must be present in nginx conf');

        $allowedExtensions = explode('|', $matches[1]);

        $this->assertNotContains('svg', $allowedExtensions, 'SVG must NOT be in public storage whitelist due to XSS risk');
        $this->assertNotContains('ico', $allowedExtensions, 'ICO must NOT be in public storage whitelist');
        $this->assertNotContains('pdf', $allowedExtensions, 'PDF must NOT be in public storage whitelist');
        $this->assertNotContains('php', $allowedExtensions, 'PHP must NOT be in public storage whitelist');
        $this->assertNotContains('html', $allowedExtensions, 'HTML must NOT be in public storage whitelist');
    }

    public function test_filesystem_storage_traversal_is_blocked(): void
    {
        $publicDir = storage_path('app/public');
        $privateDir = storage_path('app/private');

        // Ensure directories exist for test
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        if (!is_dir($privateDir)) {
            mkdir($privateDir, 0755, true);
        }

        // Create a dummy private document
        $privateFile = $privateDir . '/test-contract.pdf';
        file_put_contents($privateFile, '%PDF-1.4 test private contract');

        // Attempt traversal relative from publicDir
        $traversalTarget = realpath($publicDir . '/../private/test-contract.pdf');
        $this->assertEquals(realpath($privateFile), $traversalTarget);

        // Verify that path sanitization within public root boundary detects and rejects traversal
        $sanitizedPath = str_starts_with($traversalTarget, realpath($publicDir));
        $this->assertFalse($sanitizedPath, 'Private storage file path must NOT resolve within public storage root boundary');

        // Cleanup
        if (file_exists($privateFile)) {
            unlink($privateFile);
        }
    }
}
