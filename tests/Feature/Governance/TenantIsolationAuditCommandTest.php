<?php

declare(strict_types=1);

namespace Tests\Feature\Governance;

use App\Services\Governance\TenantIsolationAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class TenantIsolationAuditCommandTest extends TestCase
{
    public function test_source_audit_flags_tenant_column_without_trait(): void
    {
        // PHPUnit çalışma anındaki etkin veritabanı sürücüsü ve hedefi assertion kontrolü
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        $root = sys_get_temp_dir() . '/yalihan-tenant-audit-' . bin2hex(random_bytes(6));
        $models = $root . '/models';
        $migrations = $root . '/migrations';
        File::makeDirectory($models, 0755, true);
        File::makeDirectory($migrations, 0755, true);

        try {
            File::put($models . '/Example.php', <<<'PHP'
<?php
namespace App\Models;
class Example extends \App\Models\BaseModel
{
    protected $table = 'examples';
    use \App\Traits\HasCountryScope;
}
PHP);
            File::put($migrations . '/create_examples.php', <<<'PHP'
<?php
Schema::create('examples', function ($table) {
    $table->foreignId('tenant_id');
});
PHP);

            $report = (new TenantIsolationAuditService($models, $migrations, []))->audit();

            $this->assertSame(1, $report['blocking_count']);
            $this->assertSame('TENANT_COLUMN_WITHOUT_SCOPE', $report['findings'][0]['rule']);
        } finally {
            File::deleteDirectory($root);
        }
    }

    public function test_command_is_report_only_by_default_and_strict_is_explicit(): void
    {
        // PHPUnit çalışma anındaki etkin veritabanı sürücüsü ve hedefi assertion kontrolü
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        $this->artisan('bekci:tenant-audit')
            ->assertExitCode(0)
            ->expectsOutputToContain('source-read-only');
    }
}
