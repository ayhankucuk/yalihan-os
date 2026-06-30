<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ilan;
use App\Services\AI\VisionTaggingService;
use App\Services\AI\YalihanCortex;

class VisionAnalyzeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vision:analyze {ilan_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze Ilan photos using Cortex Vision Tagging (Mock/Restb.ai Parity)';

    /**
     * Execute the console command.
     */
    public function handle(VisionTaggingService $visionService)
    {
        $ilanId = $this->argument('ilan_id');
        $ilan = Ilan::find($ilanId);

        if (!$ilan) {
            $this->error("Ilan #{$ilanId} bulunamadı!");
            return 1;
        }

        $this->info("Vision Tagging başlatılıyor: Ilan #{$ilanId}");

        $photos = $ilan->fotograflar ?? collect([]);
        if ($photos->isEmpty() && method_exists($ilan, 'photos')) {
             $photos = $ilan->photos;
        }

        if ($photos->isEmpty()) {
            $this->warn("Fotoğraf bulunamadı.");
            return 0;
        }

        $bar = $this->output->createProgressBar($photos->count());
        $bar->start();

        foreach ($photos as $photo) {
            $result = $visionService->analyze($photo);

            if ($result['success']) {
                $data = $result['data'];
                $this->line("");
                $this->info(" Foto ID: {$photo->id} -> " . ($data['room_type'] ?? 'Unknown'));
                $this->line("  Condition: " . ($data['condition'] ?? 'N/A'));
                $this->line("  Alt Text: " . ($data['alt_text'] ?? ''));
            } else {
                $this->error("Hata: " . ($result['message'] ?? 'Unknown error'));
            }
            $bar->advance();
        }

        $bar->finish();
        $this->line("");
        $this->info("✅ Vision Analizi Tamamlandı.");

        return 0;
    }
}
