<?php

namespace App\Actions\Admin\DependencyRule;

use App\Models\FeatureAssignment;

class ClearDependencyRulesAction
{
    public function handle(FeatureAssignment $assignment): FeatureAssignment
    {
        $assignment->update([
            'visible_if_json' => null,
            'required_if_json' => null,
            'enabled_if_json' => null,
        ]);

        return $assignment->fresh();
    }
}
