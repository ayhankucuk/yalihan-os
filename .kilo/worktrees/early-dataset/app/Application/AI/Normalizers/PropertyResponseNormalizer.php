<?php

namespace App\Application\AI\Normalizers;

use App\Models\Feature;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * ️ PropertyResponseNormalizer
 * 
 * Standardizes AI responses and maps suggested features to database records.
 * Cleaned up from PropertyHubController logic.
 */
class PropertyResponseNormalizer
{
    /** @var Collection|null */
    protected ?Collection $allFeatures = null;

    /**
     * Map AI-suggested groups and features to Database records.
     */
    public function normalizeGroups(array $aiData): array
    {
        $mappedGroups = [];
        $features = $this->getAllFeatures();

        foreach ($aiData['groups'] ?? [] as $group) {
            $mappedFeatures = [];
            foreach ($group['features'] ?? [] as $aiFeature) {
                $name = $aiFeature['name'] ?? '';
                if (empty($name)) continue;

                $dbFeature = $this->findMatchingFeature($name, $features);

                $mappedFeatures[] = [
                    'name' => $name,
                    'exists' => (bool)$dbFeature,
                    'id' => $dbFeature?->id,
                    'db_name' => $dbFeature?->name,
                    'type' => $aiFeature['type'] ?? 'checkbox' // context7-ignore
                ];
            }

            if (!empty($mappedFeatures)) {
                $mappedGroups[] = [
                    'name' => $group['name'] ?? 'Genel',
                    'features' => $mappedFeatures
                ];
            }
        }

        return $mappedGroups;
    }

    /**
     * Get all active features for matching.
     */
    protected function getAllFeatures(): Collection
    {
        if ($this->allFeatures === null) {
            $this->allFeatures = Feature::select('id', 'name', 'slug')
                ->where('aktiflik_durumu', true)
                ->get();
        }
        return $this->allFeatures;
    }

    /**
     * Find a matching feature in the DB list using exact and fuzzy matching.
     */
    protected function findMatchingFeature(string $name, Collection $features): ?Feature
    {
        $search = Str::lower($name);

        // 1. Exact match
        $match = $features->first(fn($f) => Str::lower($f->name) === $search);
        if ($match) return $match;

        // 2. Fuzzy match (contains)
        return $features->first(function ($f) use ($search) {
            $dbName = Str::lower($f->name);
            return str_contains($dbName, $search) || str_contains($search, $dbName);
        });
    }
}
