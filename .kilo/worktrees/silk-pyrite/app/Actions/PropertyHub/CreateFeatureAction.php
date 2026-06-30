<?php

namespace App\Actions\PropertyHub;

use App\Models\Feature;
use App\Models\TemplateChangeLog;
use App\Services\PropertyHub\PropertyHubOrchestrator;
use Illuminate\Support\Str;

class CreateFeatureAction
{
    public function __construct(
        private readonly PropertyHubOrchestrator $hub
    ) {}

    public function handle(array $data, int $userId): Feature
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['aktiflik_durumu'] = true;

        $feature = Feature::create($data);

        // Invalidate cache
        $this->hub->cacheService->invalidate('features');

        // Log change
        TemplateChangeLog::create([
            'aksiyon_tipi' => 'create',
            'entity_type' => Feature::class,
            'entity_id' => $feature->id,
            'aciklama' => "Feature oluşturuldu: {$feature->name}",
            'user_id' => $userId,
            'yeni_degerler' => $data,
        ]);

        return $feature;
    }
}
