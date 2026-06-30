<?php

namespace App\Jobs\OwnerReport;

use App\Models\OwnerReportExport;
use App\Models\OwnerReportRow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * OwnerReportExportJob
 * Sorumluluk: Raporu oluşturur ve storage'a kaydeder.
 * Context7 Standard: SAB-JOB-V1
 */
class OwnerReportExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 600];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public OwnerReportExport $export
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->export->update(['islem_durumu' => 'isleniyor']);

            $data = OwnerReportRow::where('owner_id', $this->export->owner_id)
                ->when(isset($this->export->filtreler['ilan_id']), function ($query) {
                    return $query->where('ilan_id', $this->export->filtreler['ilan_id']);
                })
                ->when(isset($this->export->filtreler['start_date']), function ($query) {
                    return $query->where('kayit_tarihi', '>=', $this->export->filtreler['start_date']);
                })
                ->when(isset($this->export->filtreler['end_date']), function ($query) {
                    return $query->where('kayit_tarihi', '<=', $this->export->filtreler['end_date']);
                })
                ->orderBy('kayit_tarihi', 'desc')
                ->get();

            $content = $this->generateCsv($data);

            Storage::disk('local')->put($this->export->dosya_yolu, $content);

            $this->export->update([
                'islem_durumu' => 'tamamlandi',
                'tamamlanma_tarihi' => now(),
            ]);

        } catch (Exception $e) {
            $this->export->update([
                'islem_durumu' => 'hata',
                'hata_mesaji' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Simple CSV Generator
     */
    private function generateCsv($data): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['ID', 'Tarih', 'İşlem Tipi', 'Tutar', 'Para Birimi', 'Açıklama']);

        foreach ($data as $row) {
            fputcsv($handle, [
                $row->id,
                $row->kayit_tarihi->format('Y-m-d'),
                $row->islem_tipi,
                $row->tutar,
                $row->para_birimi,
                $row->aciklama
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }
}
