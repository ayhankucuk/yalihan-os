<?php

namespace App\Services\AIFrontend;

use App\Models\Projections\ListingSearchProjection;
use Illuminate\Database\Eloquent\Collection;

class AIListingSearchService
{
    /**
     * Search listings based on structured intent.
     * Uses the CQRS Read Model projection.
     */
    public function search(array $intent): Collection
    {
        $query = ListingSearchProjection::query();

        if (!empty($intent['location'])) {
            $query->where(function ($q) use ($intent) {
                $q->where('city', 'LIKE', '%' . $intent['location'] . '%')
                  ->orWhere('district', 'LIKE', '%' . $intent['location'] . '%');
            });
        }

        if (!empty($intent['price_max'])) {
            $query->where('price', '<=', $intent['price_max']);
        }

        if (!empty($intent['price_min'])) {
            $query->where('price', '>=', $intent['price_min']);
        }

        if (!empty($intent['rooms'])) {
            $query->where('room_count', 'LIKE', '%' . $intent['rooms'] . '%');
        }

        if (!empty($intent['search_type'])) {
            $query->where('property_type', 'LIKE', '%' . $intent['search_type'] . '%');
        }

        // Feature matching (if features are stored as JSON in projection)
        if (!empty($intent['features'])) {
            foreach ($intent['features'] as $feature) {
                $query->whereJsonContains('features', $feature);
            }
        }

        return $query->limit(10)->get();
    }
}
