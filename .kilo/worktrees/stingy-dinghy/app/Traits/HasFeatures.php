<?php

namespace App\Traits;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\FeatureValue;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasFeatures
{
    /**
     * Get all feature assignments for this model
     */
    public function featureAssignments(): MorphMany
    {
        return $this->morphMany(FeatureAssignment::class, 'assignable');
    }

    /**
     * Get all feature values for this model
     */
    public function featureValues(): MorphMany
    {
        return $this->morphMany(FeatureValue::class, 'valuable');
    }

    /**
     * Get visible feature assignments
     */
    public function visibleFeatureAssignments()
    {
        return $this->featureAssignments()
            ->visible()
            ->withFeature()
            ->ordered()
            ->get();
    }

    /**
     * Get required feature assignments
     */
    public function requiredFeatureAssignments()
    {
        return $this->featureAssignments()
            ->required()
            ->withFeature()
            ->ordered()
            ->get();
    }

    /**
     * Get grouped feature assignments
     */
    public function groupedFeatureAssignments()
    {
        return $this->featureAssignments()
            ->visible()
            ->withFeature()
            ->ordered()
            ->get()
            ->groupBy('group_name');
    }

    /**
     * Assign a feature to this model
     */
    public function assignFeature(Feature $feature, array $config = [])
    {
        return $feature->assignTo($this, $config);
    }

    /**
     * Assign multiple features
     */
    public function assignFeatures(array $featureIds, array $config = [])
    {
        // ✅ PERFORMANCE FIX: N+1 query önlendi - Tüm feature'ları tek query'de al
        $features = Feature::whereIn('id', $featureIds)->get()->keyBy('id');

        foreach ($featureIds as $featureId) {
            $feature = $features->get($featureId);
            if ($feature) {
                $this->assignFeature($feature, $config);
            }
        }
    }

    /**
     * Unassign a feature
     */
    public function unassignFeature(Feature $feature)
    {
        return $feature->unassignFrom($this);
    }

    /**
     * Sync features (like sync for many-to-many)
     */
    public function syncFeatures(array $featureIds)
    {
        // Delete existing assignments not in the new list
        $this->featureAssignments()
            ->whereNotIn('feature_id', $featureIds)
            ->delete();

        // ✅ PERFORMANCE FIX: N+1 query önlendi - Tüm feature'ları tek query'de al
        $features = Feature::whereIn('id', $featureIds)->get()->keyBy('id');

        // ✅ PERFORMANCE FIX: N+1 query önlendi - Tüm mevcut assignment'ları tek query'de al
        $assignableType = get_class($this);
        $assignableId = $this->id;
        $existingAssignments = FeatureAssignment::where('assignable_type', $assignableType)
            ->where('assignable_id', $assignableId)
            ->whereIn('feature_id', $featureIds)
            ->pluck('feature_id')
            ->toArray();

        // Add new assignments
        foreach ($featureIds as $featureId) {
            $feature = $features->get($featureId);
            // ✅ OPTIMIZED: Mevcut assignment kontrolü için database query yerine array kontrolü kullan
            if ($feature && ! in_array($featureId, $existingAssignments)) {
                $this->assignFeature($feature);
            }
        }
    }

    /**
     * Get feature value by slug
     */
    public function getFeatureValue(string $featureSlug)
    {
        $featureValue = $this->featureValues()
            ->whereHas('feature', function ($q) use ($featureSlug) {
                $q->where('slug', $featureSlug);
            })
            ->first();

        return $featureValue?->typed_value;
    }

    /**
     * Get all feature values as key-value array
     */
    public function getAllFeatureValues(): array
    {
        return FeatureValue::getForModel($this);
    }

    /**
     * Set feature value by slug
     */
    public function setFeatureValue(string $featureSlug, $value)
    {
        $feature = Feature::where('slug', $featureSlug)->first();
        if (! $feature) {
            return null;
        }

        return FeatureValue::setForModel($this, $feature, $value);
    }

    /**
     * Set multiple feature values
     */
    public function setFeatureValues(array $values)
    {
        FeatureValue::bulkSetForModel($this, $values);
    }

    /**
     * Check if feature is assigned
     */
    public function hasFeature(Feature $feature): bool
    {
        return $this->featureAssignments()
            ->where('feature_id', $feature->id)
            ->exists();
    }

    /**
     * Check if feature has value
     */
    public function hasFeatureValue(string $featureSlug): bool
    {
        return $this->featureValues()
            ->whereHas('feature', function ($q) use ($featureSlug) {
                $q->where('slug', $featureSlug);
            })
            ->exists();
    }
}
