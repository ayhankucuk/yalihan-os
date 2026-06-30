<?php

namespace App\Actions\Admin\Calendar;

use App\Models\Season;

class DeleteSeasonAction
{
    public function handle(Season $season): bool
    {
        return $season->delete();
    }
}
