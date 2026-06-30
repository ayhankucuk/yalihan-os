<?php

namespace App\Actions\Admin\AI;

use App\Models\AIMessage;

class UpdateAIMessageContentAction
{
    public function handle(AIMessage $message, string $content): bool
    {
        return $message->update([
            'content' => $content,
        ]);
    }
}
