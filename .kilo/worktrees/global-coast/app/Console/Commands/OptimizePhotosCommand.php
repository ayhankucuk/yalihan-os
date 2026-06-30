<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ilan;
use App\Services\AI\CortexVisionService;

class OptimizePhotosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cortex:optimize-photos {ilan_id : The ID of the Ilan to optimize}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reorders Ilan photos based on Cortex Vision AI criteria (Resolution, Ratio, Scene)';

    /**
     * Execute the console command.
     */
    public function handle(CortexVisionService $visionService)
    {
        $ilanId = $this->argument('ilan_id');
        $ilan = Ilan::find($ilanId);

        if (!$ilan) {
            $this->error("Ilan #{$ilanId} bulunamadı!");
            return 1;
        }

        $this->info("Cortex Vision AI: Ilan #{$ilanId} fotoğrafları analiz ediliyor...");

        $result = $visionService->smartRankPhotos($ilan);

        if (!$result['success'] ?? false) {
            $this->error("Hata: " . ($result['message'] ?? 'Bilinmeyen hata'));
            return 1;
        }

        $this->info("✅ Başarılı!");
        $this->line("- İşlenen Fotoğraf Sayısı: " . ($result['processed_count'] ?? 0));
        $this->line("- Seçilen Kapak Fotoğrafı ID: " . ($result['top_pick'] ?? 'Yok'));
        $this->line("- En Yüksek Skor: " . ($result['top_score'] ?? 0));

        return 0;
    }
}
