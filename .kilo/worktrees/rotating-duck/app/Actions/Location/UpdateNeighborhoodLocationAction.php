<?php

namespace App\Actions\Location;

use App\Models\Mahalle;
use Exception;

class UpdateNeighborhoodLocationAction
{
    /**
     * @throws Exception
     */
    public function handle(int $id, float $lat, float $lng): Mahalle
    {
        $neighborhood = Mahalle::findOrFail($id);
        $neighborhood->update([
            'lat' => $lat,
            'lng' => $lng,
        ]);

        return $neighborhood;
    }
}
