<?php

namespace App\Actions\Admin\Calendar;

use App\Models\Season;

class StoreSeasonAction
{
    public function handle(array $data): Season
    {
        return Season::create($data);
    }
}
