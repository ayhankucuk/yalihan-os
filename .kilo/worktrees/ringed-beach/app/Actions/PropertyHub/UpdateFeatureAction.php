<?php

namespace App\Actions\PropertyHub;

use App\Models\Feature;
use App\Models\TemplateChangeLog;
use App\Services\PropertyHub\PropertyHubOrchestrator;
use Illuminate\Support\Str;

class UpdateFeatureAction
{
    public function __construct(
        private readonly PropertyHubOrchestrator $hub
    ) {}

    public function handle(Feature $feature, array $data, int $userId): Feature
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $oldValues = $feature->toArray();
        $feature->update($data);

        // Invalidate cache
        $this->hub->cacheService->invalidate('features');

        // Log change
        TemplateChangeLog::create([
            'aksiyon_tipi' => 'update',
            'entity_type' => Feature::class,
            'entity_id' => $feature->id,
            'aciklama' => "Feature güncellendi: {$feature->name}",
            'user_id' => $userId,
            'eski_degerler' => $oldValues,
            'yeni_degerler' => $data,
        ]);

        return $feature->fresh();
    }
}
