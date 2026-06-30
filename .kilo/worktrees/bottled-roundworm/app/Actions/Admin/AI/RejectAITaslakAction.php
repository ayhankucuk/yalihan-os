<?php

namespace App\Actions\Admin\AI;

use App\Models\AIIlanTaslagi;
use App\Enums\IlanDurumu;
use App\Enums\TaslakDurumu;

class RejectAITaslakAction
{
    public function handle(AIIlanTaslagi $taslak): bool
    {
        return $taslak->update([
            'yayin_durumu' => IlanDurumu::PASIF->value,
        ]);
    }
}
