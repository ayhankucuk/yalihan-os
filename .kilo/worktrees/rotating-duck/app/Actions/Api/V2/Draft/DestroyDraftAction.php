<?php

namespace App\Actions\Api\V2\Draft;

use App\Models\V2\AiIlanTaslagi;

class DestroyDraftAction
{
    public function handle(AiIlanTaslagi $draft): bool
    {
        return $draft->delete();
    }
}
