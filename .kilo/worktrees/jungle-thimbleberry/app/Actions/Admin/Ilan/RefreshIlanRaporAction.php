<?php

namespace App\Actions\Admin\Ilan;

use App\Models\Ilan;

class RefreshIlanRaporAction
{
    public function handle(Ilan $ilan, array $reportData, int $userId, string $locale): bool
    {
        return $ilan->update([
            'rapor_yolu' => $reportData['path'],
            'rapor_hash' => $reportData['hash'],
            'rapor_uretildi_at' => now(),
            'rapor_uretildi_by' => $userId,
            'rapor_locale' => $locale,
        ]);
    }
}
