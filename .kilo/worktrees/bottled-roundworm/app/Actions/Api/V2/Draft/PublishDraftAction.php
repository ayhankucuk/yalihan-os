<?php

namespace App\Actions\Api\V2\Draft;

use App\Models\V2\AiIlanTaslagi;

class PublishDraftAction
{
    public function handle(AiIlanTaslagi $draft): bool
    {
        return $draft->update(['taslak_durumu' => 'Yayınlandı']);
    }
}
