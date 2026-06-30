<?php

namespace App\Actions\Admin\AI;

use App\Models\AIMessage;
use App\Enums\IlanDurumu;
use App\Enums\TaslakDurumu;

class RejectAIMessageAction
{
    public function handle(AIMessage $message): bool
    {
        return $message->update([
            'yayin_durumu' => IlanDurumu::PASIF->value,
        ]);
    }
}
