<?php

namespace App\Actions\Location;

use App\Models\Mahalle;

class UpdateMahalleCoordinatesAction
{
    public function handle(Mahalle $mahalle, float $lat, float $lng): bool
    {
        return $mahalle->update([
            'lat' => $lat,
            'lng' => $lng,
        ]);
    }
}
