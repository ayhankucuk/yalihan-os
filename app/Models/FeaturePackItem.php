<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturePackItem extends Model
{
    use HasFactory;

    protected $table = 'feature_pack_items';

    protected $fillable = [
        'feature_pack_id',
        'ozellik_id',
        'template_ref',
        'field_slug',
        'value',
        'display_order',
        'notes',
    ];

    protected $casts = [
        'ozellik_id'     => 'integer',
        'feature_pack_id' => 'integer',
        'display_order'   => 'integer',
    ];

    public function pack(): BelongsTo
    {
        return $this->belongsTo(FeaturePack::class, 'feature_pack_id');
    }

    public function ozellik(): BelongsTo
    {
        return $this->belongsTo(Ozellik::class, 'ozellik_id');
    }
}
