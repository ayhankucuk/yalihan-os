<?php

namespace App\Actions\Admin\DependencyRule;

use App\Models\FeatureAssignment;

class UpdateDependencyRulesAction
{
    public function handle(FeatureAssignment $assignment, array $updates): FeatureAssignment
    {
        $assignment->update($updates);

        return $assignment->fresh();
    }
}
