<?php

namespace App\Actions\Admin\FieldSuggestion;

use App\Models\AiFieldSuggestion;

class SetSuggestionStatusAction
{
    public function handle(AiFieldSuggestion $suggestion, string $status, ?int $appliedAssignmentId = null): AiFieldSuggestion
    {
        $payload = ['oneri_durumu' => $status];

        if ($appliedAssignmentId !== null) {
            $payload['applied_assignment_id'] = $appliedAssignmentId;
        }

        $suggestion->update($payload);

        return $suggestion->fresh();
    }
}
