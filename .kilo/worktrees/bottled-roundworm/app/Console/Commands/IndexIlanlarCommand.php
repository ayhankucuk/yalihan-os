<?php

namespace App\Console\Commands;

use App\Enums\IlanDurumu;

use App\Models\Ilan;
use App\Models\IlanEmbedding;
use App\Services\AI\EmbeddingService;
use Illuminate\Console\Command;

class IndexIlanlarCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:index-ilanlar {--force : Zorla yeniden indeksle} {--limit=100 : İşlenecek maksimum ilan sayısı}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mevcut ilanları vektör veritabanına (embeddings) kaydeder (RAG için).';

    /**
     * Embedding Service
     */
    protected EmbeddingService $embeddingService;

    public function __construct(EmbeddingService $embeddingService)
    {
        parent::__construct();
        $this->embeddingService = $embeddingService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 İlan İndeksleme Başlatılıyor (RAG)...');
        
        $limit = $this->option('limit');
        $force = $this->option('force');

        // İndekslenecek ilanları seç
        // Eğer force yoksa, sadece embedding'i olmayanları seç
        // Context7: 'yayin_durumu' string column (Aktif/Pasif)
        $query = Ilan::query()->where('yayin_durumu', IlanDurumu::YAYINDA->value);

        if (!$force) {
            $query->whereDoesntHave('embedding');
        }

        $ilanlar = $query->take($limit)->get();
        $total = $ilanlar->count();

        if ($total === 0) {
            $this->info('ℹ️ İndekslenecek yeni ilan bulunamadı.');
            return;
        }

        $this->info("📊 Toplam {$total} ilan işlenecek.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $success = 0;
        $fail = 0;

        foreach ($ilanlar as $ilan) {
            try {
                // Embedding oluştur
                $vector = $this->embeddingService->getIlanEmbedding($ilan);
                
                if ($vector) {
                    // Veritabanına kaydet
                    IlanEmbedding::updateOrCreate(
                        ['ilan_id' => $ilan->id],
                        [
                            'embedding' => $vector,
                            'model_name' => config('services.ai.embedding_provider') == 'ollama' ? 'nomic-embed-text' : 'text-embedding-3-small',
                            'dimensions' => count($vector),
                            'aktiflik_durumu' => 1
                        ]
                    );
                    $success++;
                } else {
                    $fail++;
                    // $this->error("Embedding başarısız: ID {$ilan->id}");
                }

            } catch (\Exception $e) {
                $fail++;
                // $this->error("Hata (ID {$ilan->id}): " . $e->getMessage());
            }

            $bar->advance();
            // Rate limit koruması (basit)
            usleep(100000); // 0.1s bekle
        }

        $bar->finish();
        $this->newLine();

        $this->info("✅ İŞLEM TAMAMLANDI");
        $this->info("Başarılı: {$success}");
        $this->info("Başarısız: {$fail}");
        
        if ($fail > 0) {
            $this->warn("⚠️ Bazı ilanlar indekslenemedi. API anahtarını veya sunucu durumunu kontrol edin.");
        }
    }
}
