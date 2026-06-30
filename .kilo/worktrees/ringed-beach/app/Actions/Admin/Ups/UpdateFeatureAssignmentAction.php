<?php

namespace App\Actions\Admin\Ups;

use App\Models\FeatureAssignment;

class UpdateFeatureAssignmentAction
{
    public function handle(FeatureAssignment $assignment, array $data): bool
    {
        return $assignment->update($data);
    }
}
