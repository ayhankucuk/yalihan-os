<?php

namespace App\Console\Commands\CQRS;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\CQRS\ProcessProjectionJob;

/**
 * Class DlqRetryCommand
 * * SAB Enforced Dead Letter Queue Forensic Management and Re-injection Engine.
 * Inspects and retries failed projections from etki_alani_olaylari_hatali.
 * * @package App\Console\Commands\CQRS
 */
class DlqRetryCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'sentinel:dlq-retry {--id= : Yeniden oynatılacak spesifik hata ID\'si} {--tumu : Tüm hataları sıraya sok}';

    /**
     * @var string
     */
    protected $description = 'Dead Letter Queue (DLQ) üzerindeki hatalı projeksiyon olaylarını adli forensic sonrası yeniden hatta akıtır.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("=== [SAB SENTINEL] Dead Letter Queue Console Management ===");
        
        $id = $this->option('id');
        $tumu = $this->option('tumu');

        if (!$id && !$tumu) {
            $this->error("🚨 ERROR: You must specify an --id or use the --tumu flag.");
            return Command::FAILURE;
        }

        $sorgu = DB::table('etki_alani_olaylari_hatali')->where('islem_durumu', 1); // 1: İncelemede

        if ($id) {
            $sorgu->where('id', $id);
        }

        $hataliOlaylar = $sorgu->get();

        if ($hataliOlaylar->isEmpty()) {
            $this->info("⚡ NOTICE: No pending failed events found in DLQ.");
            return Command::SUCCESS;
        }

        foreach ($hataliOlaylar as $hata) {
            try {
                $hamOlay = json_decode($hata->olay_verisi, true);

                $this->comment("--> Re-injecting failed event [ID: {$hata->id}] [Type: {$hata->olay_turu}] into asynchronous pipe...");
                
                // Olayı yeniden asenkron işleme hattına fırlat
                dispatch(new ProcessProjectionJob($hamOlay));

                // Hata kaydının durumunu güncelle (Yeniden Oynatıldı)
                DB::table('etki_alani_olaylari_hatali')
                    ->where('id', $hata->id)
                    ->update([
                        'islem_durumu' => 2, // 2: Yeniden Oynatıldı
                        'islenme_zamani' => now()->toIso8601String()
                    ]);

            } catch (\Throwable $exception) {
                Log::error("DLQ RE-INJECTION CRITICAL FAILURE for ID {$hata->id}: " . $exception->getMessage());
                $this->error("🚨 Failed to process DLQ item ID: {$hata->id}");
            }
        }

        $this->info("✅ [PASS] Specified DLQ operations successfully executed.");
        return Command::SUCCESS;
    }
}
