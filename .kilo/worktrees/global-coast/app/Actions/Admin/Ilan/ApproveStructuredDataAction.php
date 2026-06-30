<?php

namespace App\Actions\Admin\Ilan;

use App\Models\Ilan;

class ApproveStructuredDataAction
{
    public function handle(Ilan $ilan, int $userId): bool
    {
        return $ilan->update([
            'approved_at' => now(),
            'approved_by' => $userId,
        ]);
    }
}
