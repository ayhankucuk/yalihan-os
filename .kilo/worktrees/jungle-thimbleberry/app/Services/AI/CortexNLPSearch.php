<?php

namespace App\Services\AI;

use App\Models\Ilan;
use Illuminate\Support\Str;

class CortexNLPSearch
{
    /**
     * Parse natural language query into structured filter array.
     *
     * @param string $query
     * @return array
     */
    public function parseQuery(string $query): array
    {
        $query = mb_strtolower($query, 'UTF-8');
        $filters = [
            'category_id' => null,
            'features' => [],
            'price_min' => null,
            'price_max' => null,
            'room_count' => null,
            'keywords' => [],
            'intent' => 'search' // search, investment, rental
        ];

        // 1. Detect Category
        if (Str::contains($query, ['villa', 'yalı'])) $filters['category_id'] = [11, 12]; // Villa IDs (approx)
        if (Str::contains($query, ['daire', 'ev', 'konut'])) $filters['category_id'] = [1, 2]; // Daire IDs
        if (Str::contains($query, ['arsa', 'tarla', 'arazi'])) $filters['category_id'] = [27, 29]; // Arsa IDs
        if (Str::contains($query, ['işyeri', 'dükkan', 'ofis'])) $filters['category_id'] = [35, 40]; // Ticari IDs

        // 2. Detect Features (Simple Keyword Mapping)
        if (Str::contains($query, ['havu', 'pool'])) $filters['features'][] = 'Havuz';
        if (Str::contains($query, ['deniz', 'sea', 'manzara'])) $filters['features'][] = 'Deniz Manzarası';
        if (Str::contains($query, ['bahçe', 'garden'])) $filters['features'][] = 'Bahçe';
        if (Str::contains($query, ['otopark', 'park'])) $filters['features'][] = 'Otopark';
        if (Str::contains($query, ['site'])) $filters['features'][] = 'Site İçerisinde';

        // 3. Detect Intent
        if (Str::contains($query, ['yatırım', 'fırsat', 'ucuz', 'kelepir'])) $filters['intent'] = 'investment';
        if (Str::contains($query, ['kiralık'])) $filters['intent'] = 'rent';

        // 4. Room Count (Regex)
        // Matches "2+1", "3+1", "3 oda"
        if (preg_match('/(\d+)\+(\d+)/', $query, $matches)) {
            $filters['room_count'] = $matches[0];
        }

        return $filters;
    }

    /**
     * Execute search based on natural language query.
     *
     * @param string $query
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $query)
    {
        $filters = $this->parseQuery($query);
        $q = Ilan::query();

        // Apply Category
        if ($filters['category_id']) {
            $q->whereIn('kategori_id', $filters['category_id']);
        }

        // Apply Room Count
        if ($filters['room_count']) {
            $q->where('oda_sayisi', 'LIKE', '%' . $filters['room_count'] . '%');
        }

        // Apply Features (Generic Logic - needs distinct table or logic depending on implementation)
        // Assuming 'ozellikler' JSON or relationship exists.
        // For MVP, we use simple WHERE like logic if columns exist or check generic text search
        // Ideally: $q->whereHas('features', fn($q) => $q->whereIn('name', $filters['features']));

        // For now, simple text search for features in title/desc as fallback
        if (!empty($filters['features'])) {
            $q->where(function($sub) use ($filters) {
                foreach ($filters['features'] as $feature) {
                    $sub->orWhere('baslik', 'LIKE', "%{$feature}%")
                        ->orWhere('aciklama', 'LIKE', "%{$feature}%");
                }
            });
        }

        // Apply Intent Sorting
        if ($filters['intent'] === 'investment') {
            // Sort by Price ASC (Cheapest first for now, later ROI)
            $q->orderBy('fiyat', 'asc'); // context7-ignore
        } else {
            $q->orderBy('created_at', 'desc'); // context7-ignore
        }

        return $q->take(10)->get();
    }
}
