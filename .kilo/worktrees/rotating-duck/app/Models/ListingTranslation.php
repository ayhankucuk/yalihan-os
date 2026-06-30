<?php

namespace App\Models;

use App\Traits\EnforcesContext7Guard;
use App\Traits\SabGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingTranslation extends \App\Models\BaseModel
{
    use EnforcesContext7Guard, SabGuard;

    protected $fillable = [
        'listing_id',
        'locale',
        'translated_title',
        'translated_description',
        'translated_summary',
        'cevirme_durumu',
        'translated_by',
        'review_required',
        'last_translated_at',
        'metadata',
    ];

    protected $casts = [
        'review_required' => 'boolean',
        'last_translated_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * İlgili ilan.
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'listing_id');
    }
}
