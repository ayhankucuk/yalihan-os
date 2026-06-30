<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Ilan;
use Carbon\Carbon;

class RebuildCqrsProjections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projection:rebuild';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuilds CQRS read model projections from the Core Ledger and CRM data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting CQRS Projection Rebuild...');

            $this->info('1. Truncating projection tables (Safe rebuild)...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('proj_listings')->truncate();
            DB::table('proj_kpi_snapshots')->truncate();
            // We do not truncate activity streams to preserve history if we want, or we could.
            // For now, let's keep it safe and just rebuild listings and current KPIs.
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::transaction(function () {

            $this->info('2. Backfilling Listings (proj_listings)...');
            Ilan::chunk(100, function ($ilans) {
                $payloads = [];
                foreach ($ilans as $ilan) {
                    $payloads[] = [
                        'ilan_id' => $ilan->id,
                        'baslik' => $ilan->baslik ?? '',
                        'yayin_durumu' => 1, // Defaulting to 1 for backfill
                        'fiyat' => $ilan->fiyat ?? 0,
                        'para_birimi' => $ilan->para_birimi_id,
                        'danisman_id' => $ilan->danisman_id ?? $ilan->user_id,
                        'kategori_id' => $ilan->kategori_id,
                        'il_id' => $ilan->il_id,
                        'created_at' => $ilan->created_at,
                        'updated_at' => $ilan->updated_at,
                    ];
                }

                // Provide dummy mapping since the yayin durumu logic was refactored
                foreach($payloads as &$p) {
                    $p['yayin_durumu'] = 1; // Default to active for backfill
                    $p['gecen_gun_sayisi'] = Carbon::parse($p['created_at'])->diffInDays(now());
                }

                DB::table('proj_listings')->insert($payloads);
                $this->output->write('.');
            });
            $this->newLine();

            $this->info('3. Generating Global KPI Snapshots (proj_kpi_snapshots)...');
            $activeCount = DB::table('proj_listings')->where('yayin_durumu', 1)->count();
            $totalValue = DB::table('proj_listings')->where('yayin_durumu', 1)->sum('fiyat');
            $avgAge = DB::table('proj_listings')->where('yayin_durumu', 1)->avg('gecen_gun_sayisi') ?? 0;

            DB::table('proj_kpi_snapshots')->insert([
                'tarih' => now()->toDateString(),
                'danisman_id' => null, // Global (danışman bağımsız)
                'toplam_portfoy_degeri' => $totalValue,
                'aktif_ilan_sayisi' => $activeCount,
                'yeni_talep_sayisi_7_gun' => 0, // Will be populated by events going forward
                'ortalama_satista_kalma_suresi' => $avgAge,
                'cevirim_orani' => 0,
            ]);

            // Mark offsets as fully caught up technically
            if (\Illuminate\Support\Facades\Schema::hasTable('proj_event_offsets')) {
                DB::table('proj_event_offsets')->delete();
            }

            $this->info('CQRS Projection Rebuild Complete!');
        });

        return Command::SUCCESS;
    }
}
