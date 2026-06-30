<?php

namespace App\Actions\OwnerReport;

use App\Models\User;
use App\Models\OwnerReportExport;
use App\Jobs\OwnerReport\OwnerReportExportJob;
use Illuminate\Support\Str;

/**
 * ExportOwnerReportAction
 * Sorumluluk: Export talebini validate eder, kaydeder ve Job'ı tetikler.
 * Context7 Standard: SAB-ACTION-V1
 */
class ExportOwnerReportAction
{
    /**
     * Handle the export request.
     */
    public function handle(User $user, array $filters): OwnerReportExport
    {
        $format = $filters['format'] ?? 'csv';
        $filename = 'report_' . Str::random(10) . '.' . $format;
        $path = 'exports/owner/' . $user->id . '/' . $filename;

        // Export kaydını oluştur
        $export = OwnerReportExport::create([
            'owner_id' => $user->id,
            'dosya_adi' => $filename,
            'dosya_yolu' => $path,
            'format' => $format,
            'islem_durumu' => 'bekliyor',
            'filtreler' => $filters,
        ]);

        // Job'ı kuyruğa gönder
        OwnerReportExportJob::dispatch($export);

        return $export;
    }
}
