<?php

namespace App\Actions\Admin\Calendar;

use App\Models\Season;

class UpdateSeasonAction
{
    public function handle(Season $season, array $data): Season
    {
        $season->update($data);
        return $season->fresh();
    }
}
