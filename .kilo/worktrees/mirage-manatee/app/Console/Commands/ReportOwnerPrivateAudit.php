<?php

namespace App\Console\Commands;

use App\Models\IlanPrivateAudit;
use Illuminate\Console\Command;

class ReportOwnerPrivateAudit extends Command
{
    protected $signature = 'report:owner-private-audit';

    protected $description = 'Mahrem veri audit özetini rapor dosyasına yazar';

    public function handle(): int
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $dayCount = IlanPrivateAudit::whereDate('created_at', $now->toDateString())->count();
        $monthCount = IlanPrivateAudit::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $uniqueListings = IlanPrivateAudit::distinct('ilan_id')->whereBetween('created_at', [$monthStart, $monthEnd])->count('ilan_id');
        $ym = $now->format('Y-m');
        $dir = base_path('yalihan-bekci/reports/'.$ym);
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $file = $dir.'/owner-private-audit-summary.txt';
        $lines = [
            'day_changes: '.$dayCount,
            'month_changes: '.$monthCount,
            'unique_listings_month: '.$uniqueListings,
            'generated_at: '.$now->toIso8601String(),
        ];
        @file_put_contents($file, implode("\n", $lines));
        $this->info('Audit summary written: '.$file);

        return self::SUCCESS;
    }
}
