<?php

namespace App\Services\Property;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\FeaturePack;
use App\Models\YayinTipiSablonu;
use App\Models\TemplateChangeLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Traits\GuardsAgentWrites;

/**
 * Feature Pack Service
 *
 * @deprecated Pack uygulama islemleri PropertyTypeConfiguration Aggregate Root'a tasinmistir.
 * Pack CRUD islemleri gecis doneminde burada kalacaktir.
 *
 * @see \App\Domain\PropertyHub\PropertyTypeConfiguration::applyFeaturePack()
 *
 * Context7 Compliance:
 * @context7-ignore-next-line
 * - aktiflik_durumu (NOT status/active)
 * - display_order (NOT sort_order)
 */
class FeaturePackService
{
    use GuardsAgentWrites;
    /**
     * Forbidden fields — reads from SSOT config
     */
    private static ?array $forbiddenFieldsCache = null;

    private static function getForbiddenFields(): array
    {
        if (self::$forbiddenFieldsCache === null) {
            self::$forbiddenFieldsCache = config('context7.forbidden_fields.runtime_guard', []);
        }
        return self::$forbiddenFieldsCache;
    }

    /**
     * Create a new feature pack
     *
     * @throws ValidationException
     * @throws \InvalidArgumentException
     */
    public function createPack(array $data): FeaturePack
    {
        $this->blockAgentWrite(__FUNCTION__);

        // Context7 Guard: Check for forbidden fields
        $this->guardAgainstForbiddenFields($data);

        // Validate input
        $validated = $this->validatePackData($data);

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        DB::beginTransaction();

        try {
            $pack = FeaturePack::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'aktiflik_durumu' => $validated['aktiflik_durumu'] ?? true,
            ]);

            // Attach features if provided
            if (!empty($validated['feature_ids'])) {
                $this->attachFeatures($pack->id, $validated['feature_ids']);
            }

            DB::commit();

            // Log change
            $this->logChange('create', $pack->id, "Feature Pack oluşturuldu: {$pack->name}");

            return $pack->fresh()->load('features');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing feature pack
     *
     * @throws ValidationException
     * @throws \InvalidArgumentException
     */
    public function updatePack(int $packId, array $data): FeaturePack
    {
        $this->blockAgentWrite(__FUNCTION__);

        $pack = FeaturePack::findOrFail($packId);

        // Context7 Guard
        $this->guardAgainstForbiddenFields($data);

        // Validate with pack ID for unique slug check
        $validated = $this->validatePackData($data, $packId);

        DB::beginTransaction();

        try {
            $pack->update([
                'name' => $validated['name'] ?? $pack->name,
                'slug' => $validated['slug'] ?? $pack->slug,
                'description' => $validated['description'] ?? $pack->description,
                'aktiflik_durumu' => $validated['aktiflik_durumu'] ?? $pack->aktiflik_durumu,
            ]);

            // Update features if provided
            if (isset($validated['feature_ids'])) {
                $pack->features()->detach();
                $this->attachFeatures($pack->id, $validated['feature_ids']);
            }

            DB::commit();

            // Log change
            $this->logChange('update', $pack->id, "Feature Pack güncellendi: {$pack->name}");

            return $pack->fresh()->load('features');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Attach features to a pack
     */
    public function attachFeatures(int $packId, array $featureIds): bool
    {
        $pack = FeaturePack::findOrFail($packId);

        // Validate feature IDs exist
        $validFeatures = Feature::whereIn('id', $featureIds)->pluck('id');

        foreach ($validFeatures as $index => $featureId) {
            // Use sync to prevent duplicates
            $pack->features()->syncWithoutDetaching([
                $featureId => ['display_order' => $index]
            ]);
        }

        return true;
    }

    /**
     * Apply pack to a kategori yayin_tipi
     *
     * @return array ['success' => bool, 'assigned_count' => int, 'skipped_count' => int]
     */
    public function applyPackToYayinTipi(int $packId, int $yayinTipiId): array
    {
        $pack = FeaturePack::with('features')->findOrFail($packId);
        $yayinTipi = YayinTipiSablonu::findOrFail($yayinTipiId);

        $assignedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();

        try {
            // Batch pre-fetch: mevcut atamaları tek sorguda al (N+1 önleme)
            $existingFeatureIds = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
                ->where('assignable_id', $yayinTipiId)
                ->whereIn('feature_id', $pack->features->pluck('id'))
                ->pluck('feature_id')
                ->toArray();

            foreach ($pack->features as $feature) {
                // Context7: FeatureAssignment uses polymorphic relationship
                if (in_array($feature->id, $existingFeatureIds)) {
                    $skippedCount++;
                    continue;
                }

                FeatureAssignment::create([
                    'feature_id' => $feature->id,
                    'assignable_type' => YayinTipiSablonu::class,
                    'assignable_id' => $yayinTipiId,
                    'aktiflik_durumu' => true,
                    'display_order' => $feature->pivot->display_order ?? 0,
                    'source_type' => 'pack_apply',
                ]);

                $assignedCount++;
            }

            DB::commit();

            // Log change
            $this->logChange(
                'apply_pack',
                $pack->id,
                "Pack uygulandı: {$pack->name} → YayinTipi ID: {$yayinTipiId}"
            );

            return [
                'success' => true,
                'assigned_count' => $assignedCount,
                'skipped_count' => $skippedCount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get all active feature packs
     */
    public function getActivePacks(): Collection
    {
        return FeaturePack::where('aktiflik_durumu', true)
            ->with('features')
            ->orderBy('name') // context7-ignore
            ->get();
    }

    /**
     * Get pack by ID with features
     */
    public function getPackById(int $packId): FeaturePack
    {
        return FeaturePack::with('features')->findOrFail($packId);
    }

    /**
     * Delete a pack
     */
    public function deletePack(int $packId): bool
    {
        $this->blockAgentWrite(__FUNCTION__);

        $pack = FeaturePack::findOrFail($packId);

        DB::beginTransaction();

        try {
            // Detach all features
            $pack->features()->detach();

            // Delete pack
            $pack->delete();

            DB::commit();

            // Log change
            $this->logChange('delete', $packId, "Feature Pack silindi: {$pack->name}");

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate pack data
     *
     * @throws ValidationException
     */
    private function validatePackData(array $data, ?int $packId = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'aktiflik_durumu' => 'nullable|boolean',
            'feature_ids' => 'nullable|array',
            'feature_ids.*' => 'exists:features,id',
        ];

        // Add unique slug rule if pack ID provided
        if ($packId) {
            $rules['slug'] .= '|unique:feature_packs,slug,' . $packId;
        } else {
            $rules['slug'] .= '|unique:feature_packs,slug';
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Guard against forbidden Context7 fields
     *
     * @throws \InvalidArgumentException
     */
    private function guardAgainstForbiddenFields(array $data): void
    {
        foreach (self::getForbiddenFields() as $forbiddenField) {
            if (array_key_exists($forbiddenField, $data)) {
                throw new \InvalidArgumentException(
                    "Forbidden field: {$forbiddenField}. Use Context7 canonical fields instead."
                );
            }
        }
    }

    /**
     * Log pack changes
     */
    private function logChange(string $actionType, int $packId, string $description): void
    {
        TemplateChangeLog::create([
            'aksiyon_tipi' => $actionType,
            'entity_type' => FeaturePack::class,
            'entity_id' => $packId,
            'aciklama' => $description,
            'user_id' => auth()->id(),
        ]);
    }
}
