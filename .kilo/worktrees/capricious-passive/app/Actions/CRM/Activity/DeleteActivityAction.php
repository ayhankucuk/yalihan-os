<?php

namespace App\Actions\CRM\Activity;

use App\Models\KisiEtkilesim;
use Exception;

class DeleteActivityAction
{
    /**
     * @throws Exception
     */
    public function handle(KisiEtkilesim $activity): bool
    {
        return $activity->delete();
    }
}
