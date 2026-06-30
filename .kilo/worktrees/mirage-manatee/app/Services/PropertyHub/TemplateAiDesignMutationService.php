<?php

namespace App\Services\PropertyHub;

use App\Models\Feature;
use App\Models\TemplateDesignAudit;
use App\Models\YayinTipiSablonu;
use Illuminate\Support\Facades\DB;

class TemplateAiDesignMutationService
{
    public function applyDesign(array $validated, int $userId): array
    {
        $design = $validated['design_payload']['design'] ?? [];
        $mode = $validated['apply_mode'];

        $template = YayinTipiSablonu::findOrFail((int) $validated['yayin_tipi_id']);

        $beforeSnapshot = $template->featureAssignments()
            ->with('feature:id,name,slug')
            ->get(['id', 'feature_id', 'is_required', 'is_visible', 'display_order'])
            ->map(fn ($a) => [
                'feature_id' => $a->feature_id,
                'slug' => $a->feature?->slug,
                'name' => $a->feature?->name,
                'is_required' => $a->is_required,
            ])
            ->toArray();

        $changes = ['added' => [], 'made_required' => [], 'skipped' => []];
        $auditRecord = null;

        DB::transaction(function () use ($template, $design, $mode, $userId, $validated, $beforeSnapshot, &$changes, &$auditRecord) {
            $existingFeatureIds = $template->featureAssignments()->pluck('feature_id')->toArray();
            $maxOrder = $template->featureAssignments()->max('display_order') ?? 0;

            if (in_array($mode, ['full', 'add_only'], true)) {
                $addItems = $design['add'] ?? [];

                foreach ($addItems as $item) {
                    $slug = is_array($item) ? ($item['slug'] ?? $item['name'] ?? null) : $item;
                    if (! $slug) {
                        continue;
                    }

                    $feature = Feature::where('slug', $slug)
                        ->orWhere('name', $slug)
                        ->first();

                    if (! $feature) {
                        $changes['skipped'][] = ['slug' => $slug, 'reason' => 'feature_not_found'];
                        continue;
                    }

                    if (in_array($feature->id, $existingFeatureIds, true)) {
                        $changes['skipped'][] = ['slug' => $slug, 'reason' => 'already_assigned'];
                        continue;
                    }

                    $maxOrder++;
                    $template->featureAssignments()->create([
                        'feature_id' => $feature->id,
                        'is_required' => (bool) (is_array($item) ? ($item['required'] ?? false) : false),
                        'is_visible' => true,
                        'display_order' => $maxOrder,
                        'source_type' => 'ai_design',
                        'aktiflik_durumu' => 1,
                    ]);

                    $existingFeatureIds[] = $feature->id;
                    $changes['added'][] = [
                        'feature_id' => $feature->id,
                        'slug' => $feature->slug,
                        'name' => $feature->name,
                    ];
                }
            }

            if (in_array($mode, ['full', 'required_only'], true)) {
                $requiredItems = $design['make_required'] ?? [];

                foreach ($requiredItems as $item) {
                    $slug = is_array($item) ? ($item['slug'] ?? $item['name'] ?? null) : $item;
                    if (! $slug) {
                        continue;
                    }

                    $feature = Feature::where('slug', $slug)
                        ->orWhere('name', $slug)
                        ->first();

                    if (! $feature) {
                        $changes['skipped'][] = ['slug' => $slug, 'reason' => 'feature_not_found'];
                        continue;
                    }

                    $assignment = $template->featureAssignments()
                        ->where('feature_id', $feature->id)
                        ->first();

                    if (! $assignment) {
                        $changes['skipped'][] = ['slug' => $slug, 'reason' => 'not_assigned_to_template'];
                        continue;
                    }

                    if (! $assignment->is_required) {
                        $assignment->update(['is_required' => true]);
                        $changes['made_required'][] = [
                            'feature_id' => $feature->id,
                            'slug' => $feature->slug,
                            'name' => $feature->name,
                        ];
                    }
                }
            }

            $auditRecord = TemplateDesignAudit::create([
                'yayin_tipi_id' => $validated['yayin_tipi_id'],
                'kategori_id' => $validated['kategori_id'],
                'user_id' => $userId,
                'run_uuid' => $validated['run_uuid'] ?? null,
                'apply_mode' => $mode,
                'before_snapshot' => $beforeSnapshot,
                'changes' => $changes,
                'design_payload' => $validated['design_payload'],
            ]);
        });

        return [
            'template' => $template,
            'before_snapshot' => $beforeSnapshot,
            'changes' => $changes,
            'audit' => $auditRecord,
            'mode' => $mode,
        ];
    }

    public function rollbackDesign(int $auditId, int $userId): array
    {
        $audit = TemplateDesignAudit::where('id', $auditId)
            ->where('user_id', $userId)
            ->where('rolled_back', false)
            ->firstOrFail();

        $template = YayinTipiSablonu::findOrFail($audit->yayin_tipi_id);
        $changes = $audit->changes ?? [];
        $reverted = ['removed' => [], 'reverted_required' => []];

        DB::transaction(function () use ($template, $changes, $audit, $userId, &$reverted) {
            $addedFeatureIds = collect($changes['added'] ?? [])
                ->pluck('feature_id')
                ->filter()
                ->toArray();

            if (! empty($addedFeatureIds)) {
                $template->featureAssignments()
                    ->whereIn('feature_id', $addedFeatureIds)
                    ->where('source_type', 'ai_design')
                    ->delete();

                $reverted['removed'] = $changes['added'] ?? [];
            }

            $requiredFeatureIds = collect($changes['made_required'] ?? [])
                ->pluck('feature_id')
                ->filter()
                ->toArray();

            if (! empty($requiredFeatureIds)) {
                $template->featureAssignments()
                    ->whereIn('feature_id', $requiredFeatureIds)
                    ->update(['is_required' => false]);

                $reverted['reverted_required'] = $changes['made_required'] ?? [];
            }

            $audit->update([
                'rolled_back' => true,
                'rolled_back_at' => now(),
                'rolled_back_by' => $userId,
            ]);
        });

        return [
            'audit' => $audit,
            'reverted' => $reverted,
        ];
    }
}
