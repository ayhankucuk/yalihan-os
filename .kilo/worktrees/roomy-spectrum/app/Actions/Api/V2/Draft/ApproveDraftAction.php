<?php

namespace App\Actions\Api\V2\Draft;

use App\Models\V2\AiIlanTaslagi;

class ApproveDraftAction
{
    public function handle(AiIlanTaslagi $draft, int $moderatorId): bool
    {
        return $draft->update([
            'taslak_durumu' => 'Onaylı',
            'approved_by' => $moderatorId,
            'approved_at' => now(),
        ]);
    }
}
