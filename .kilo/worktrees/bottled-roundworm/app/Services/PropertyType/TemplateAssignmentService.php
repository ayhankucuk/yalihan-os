<?php

namespace App\Services\PropertyType;

use App\Models\FeatureAssignment;
use App\Models\YayinTipiSablonu;
use App\Traits\GuardsAgentWrites;

class TemplateAssignmentService
{
    use GuardsAgentWrites;
    /**
     * Update a template's core attributes.
     */
    public function updateTemplate(YayinTipiSablonu $template, array $data): YayinTipiSablonu
    {
        $this->blockAgentWrite(__FUNCTION__);

        $template->update($data);

        return $template;
    }

    public function syncFeatures(YayinTipiSablonu $template, array $features): void
    {
        $this->blockAgentWrite(__FUNCTION__);

        $normalized = collect($features)
            ->filter(fn (array $feature): bool => isset($feature['id']))
            ->keyBy(fn (array $feature): int => (int) $feature['id']);

        $incomingFeatureIds = $normalized->keys()->map(fn ($id): int => (int) $id)->all();

        $existingAssignments = $template->featureAssignments()->get()->keyBy('feature_id');

        foreach ($existingAssignments as $featureId => $assignment) {
            if (! in_array((int) $featureId, $incomingFeatureIds, true)) {
                $assignment->delete();
            }
        }

        foreach ($normalized as $featureId => $payload) {
            $resolvedOrder = isset($payload['display_order'])
                ? (int) $payload['display_order']
                : ((int) $template->featureAssignments()->max('display_order') + 1);

            $assignment = $existingAssignments->get((int) $featureId);

            if ($assignment instanceof FeatureAssignment) {
                $assignment->update([
                    'is_required' => (bool) ($payload['is_required'] ?? false),
                    'is_visible' => (bool) ($payload['is_visible'] ?? true),
                    'display_order' => $resolvedOrder,
                    'aktiflik_durumu' => (bool) ($payload['aktiflik_durumu'] ?? true),
                ]);

                continue;
            }

            $template->featureAssignments()->create([
                'feature_id' => (int) $featureId,
                'is_required' => (bool) ($payload['is_required'] ?? false),
                'is_visible' => (bool) ($payload['is_visible'] ?? true),
                'display_order' => $resolvedOrder,
                'aktiflik_durumu' => (bool) ($payload['aktiflik_durumu'] ?? true),
            ]);
        }
    }
}
