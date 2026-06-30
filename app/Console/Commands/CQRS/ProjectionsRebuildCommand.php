<?php

namespace App\Console\Commands\CQRS;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\CQRS\ProcessProjectionJob;

/**
 * Class ProjectionsRebuildCommand
 * * SAB Enforced Append-Only Event Replay and Read Model Reconstruction Engine.
 * Truncates projections and re-enacts the entire cryptographic history from Event Store.
 * * @package App\Console\Commands\CQRS
 */
class ProjectionsRebuildCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'projections:rebuild {--domain=hepsi : Yeniden inşa edilecek alan (leads|ilanlar|kisiler|hepsi)}';

    /**
     * @var string
     */
    protected $description = 'Değişmez Olay Mağazasındaki tüm geçmişi baştan oynatarak okuma modellerini sıfırdan inşa eder.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("=== [SAB CQRS] Read Model Reconstruction Engine Active ===");
        $domain = $this->option('domain');

        try {
            DB::transaction(function () use ($domain) {
                // 1. Projeksiyon Tablolarını Güvenli Biçimde Sıfırla (Truncate)
                if ($domain === 'hepsi' || $domain === 'leads') {
                    $this->comment("--> Cleaning leads_read_model...");
                    DB::table('leads_read_model')->truncate();
                }
                if ($domain === 'hepsi' || $domain === 'ilanlar') {
                    $this->comment("--> Cleaning ilanlar_read_model...");
                    DB::table('ilanlar_read_model')->truncate();
                }
                if ($domain === 'hepsi' || $domain === 'kisiler') {
                    $this->comment("--> Cleaning kisiler_read_model...");
                    DB::table('kisiler_read_model')->truncate();
                }
            });

            // 2. Event Store İçeriğini Kronolojik Sırayla Çek (Idempotent Append-Only Log)
            $this->comment("--> Fetching historical event streams from immutable store...");
            
            DB::table('etki_alani_olaylari')
                ->orderBy('id', 'asc')
                ->chunk(500, function ($olaylar) {
                    foreach ($olaylar as $olay) {
                        // Ham veriyi asenkron işleme işine (Job) pasla ve kuyrukta eşzamanlı (sync) koştur
                        $olayDizisi = [
                            'tenant_id'        => (int) $olay->tenant_id,
                            'event_type'       => $olay->event_type,
                            'aggregate_type'   => $olay->aggregate_type,
                            'aggregate_id'     => (int) $olay->aggregate_id,
                            'payload'          => is_array($olay->payload) ? $olay->payload : json_decode($olay->payload, true),
                            'sequence_number'  => (int) $olay->sequence_number
                        ];

                        dispatch_sync(new ProcessProjectionJob($olayDizisi));
                    }
                });

            $this->info("✅ [SUCCESS] Projections successfully reconstructed from historical truths.");
            return Command::SUCCESS;

        } catch (\Throwable $exception) {
            Log::critical("SAB RECONSTRUCTION ABORTED: " . $exception->getMessage(), [
                'trace' => $exception->getTraceAsString()
            ]);
            $this->error("🚨 CRITICAL: Reconstruction crashed! Read models might be in an inconsistent state.");
            return Command::FAILURE;
        }
    }
}
