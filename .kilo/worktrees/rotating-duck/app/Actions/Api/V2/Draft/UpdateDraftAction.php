<?php

namespace App\Actions\Api\V2\Draft;

use App\Models\V2\AiIlanTaslagi;

class UpdateDraftAction
{
    public function handle(AiIlanTaslagi $draft, array $data): bool
    {
        return $draft->update($data);
    }
}
