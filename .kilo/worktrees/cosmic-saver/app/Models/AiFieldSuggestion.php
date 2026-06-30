<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiFieldSuggestion extends BaseModel
{
    use HasCountryScope;

    protected $table = 'ai_field_suggestions';

    protected $fillable = [
        'slug',
        'label',
        'field_type',
        'group_name',
        'main_category_id',
        'sub_category_id',
        'listing_type_id',
        'reason',
        'score_json',
        'total_score',
        'priority',
        'source',
        'oneri_durumu',
        'conflicts_json',
        'feature_id',
        'applied_assignment_id',
    ];

    protected $casts = [
        'score_json' => 'array',
        'conflicts_json' => 'array',
        'total_score' => 'integer',
        'main_category_id' => 'integer',
        'sub_category_id' => 'integer',
        'listing_type_id' => 'integer',
        'feature_id' => 'integer',
        'applied_assignment_id' => 'integer',
    ];

    public function actions(): HasMany
    {
        return $this->hasMany(AiSuggestionAction::class, 'suggestion_id');
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }

    public function appliedAssignment()
    {
        return $this->belongsTo(FeatureAssignment::class, 'applied_assignment_id');
    }

    public function scopePending($query)
    {
        return $query->where('oneri_durumu', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('oneri_durumu', 'approved');
    }

    public function scopeApplied($query)
    {
        return $query->where('oneri_durumu', 'applied');
    }

    public function scopeByCategory($query, int $mainCategoryId)
    {
        return $query->where('main_category_id', $mainCategoryId);
    }

    public function scopeByListingType($query, int $listingTypeId)
    {
        return $query->where('listing_type_id', $listingTypeId);
    }

    public function isPending(): bool
    {
        return $this->oneri_durumu === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->oneri_durumu === 'approved';
    }

    public function isApplied(): bool
    {
        return $this->oneri_durumu === 'applied';
    }

    public function isRolledBack(): bool
    {
        return $this->oneri_durumu === 'rolled_back';
    }
}
