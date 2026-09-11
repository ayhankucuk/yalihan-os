<?php

namespace Tests\Feature\Console;

use App\Console\Commands\RentalSyncAirbnbCommand;
use App\Models\IlanTakvimSync;
use App\Models\Ilan;
use App\Services\CalendarSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalSyncAirbnbCommandTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    public function test_dry_run_reports_zero_records_when_none_due(): void
    {
        $this->artisan('rental:sync-airbnb', ['--dry-run' => true])
            ->expectsOutputToContain('Synclenecek kayıt yok')
            ->assertExitCode(0);
    }

    public function test_dry_run_lists_due_sync_records(): void
    {
        $ilan = Ilan::factory()->create(['tenant_id' => $this->tenantId]);
        IlanTakvimSync::factory()->create([
            'ilan_id' => $ilan->id,
            'platform' => 'airbnb',
            'senkron_durumu' => 'active',
            'auto_sync' => true,
            'next_sync_at' => now()->subMinutes(5),
            'external_listing_id' => 'airbnb-123',
        ]);

        $this->artisan('rental:sync-airbnb', ['--dry-run' => true])
            ->expectsOutputToContain('Toplam: 1 kayıt')
            ->assertExitCode(0);
    }

    public function test_dry_run_skips_non_airbnb_platform(): void
    {
        $ilan = Ilan::factory()->create(['tenant_id' => $this->tenantId]);
        IlanTakvimSync::factory()->create([
            'ilan_id' => $ilan->id,
            'platform' => 'booking',
            'senkron_durumu' => 'active',
            'auto_sync' => true,
            'next_sync_at' => now()->subMinutes(5),
        ]);

        $this->artisan('rental:sync-airbnb', ['--dry-run' => true])
            ->expectsOutputToContain('Toplam: 0 kayıt')
            ->assertExitCode(0);
    }

    public function test_dry_run_skips_inactive_sync(): void
    {
        $ilan = Ilan::factory()->create(['tenant_id' => $this->tenantId]);
        IlanTakvimSync::factory()->create([
            'ilan_id' => $ilan->id,
            'platform' => 'airbnb',
            'senkron_durumu' => 'pasif',
            'auto_sync' => true,
            'next_sync_at' => now()->subMinutes(5),
        ]);

        $this->artisan('rental:sync-airbnb', ['--dry-run' => true])
            ->expectsOutputToContain('Toplam: 0 kayıt')
            ->assertExitCode(0);
    }

    public function test_force_flag_syncs_all_active_regardless_of_next_sync(): void
    {
        $ilan = Ilan::factory()->create(['tenant_id' => $this->tenantId]);
        IlanTakvimSync::factory()->create([
            'ilan_id' => $ilan->id,
            'platform' => 'airbnb',
            'senkron_durumu' => 'active',
            'auto_sync' => true,
            'next_sync_at' => now()->addDays(3),
            'external_listing_id' => 'airbnb-456',
        ]);

        $this->artisan('rental:sync-airbnb', ['--dry-run' => true, '--force' => true])
            ->expectsOutputToContain('Toplam: 1 kayıt')
            ->assertExitCode(0);
    }

    public function test_ilan_filter_limits_scope_to_specified_ids(): void
    {
        $ilan1 = Ilan::factory()->create(['tenant_id' => $this->tenantId]);
        $ilan2 = Ilan::factory()->create(['tenant_id' => $this->tenantId]);

        IlanTakvimSync::factory()->create([
            'ilan_id' => $ilan1->id,
            'platform' => 'airbnb',
            'senkron_durumu' => 'active',
            'auto_sync' => true,
            'next_sync_at' => now()->subMinutes(5),
        ]);
        IlanTakvimSync::factory()->create([
            'ilan_id' => $ilan2->id,
            'platform' => 'airbnb',
            'senkron_durumu' => 'active',
            'auto_sync' => true,
            'next_sync_at' => now()->subMinutes(5),
        ]);

        $this->artisan('rental:sync-airbnb', [
            '--dry-run' => true,
            '--ilan' => [$ilan1->id],
        ])->expectsOutputToContain('Toplam: 1 kayıt')
            ->assertExitCode(0);
    }
}
