<?php

namespace App\Repositories;

use App\Models\AiLog;

class SystemMonitorRepository
{
    /**
     * Belirli bir tarihten sonraki tüm logları getirir (N+1 engellemek ve PHP'de işlemek için).
     */
    public function getLogsSince($since)
    {
        return AiLog::where('olusturma_tarihi', '>=', $since)->get();
    }

    /**
     * Yakın zamandaki hataları getirir.
     */
    public function getRecentErrors(int $limit = 10)
    {
        return AiLog::where('aktiflik_kodu', '>=', 400)
            ->orderBy('olusturma_tarihi', 'desc')
            ->limit($limit)
            ->get();
    }
}
