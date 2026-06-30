<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IlanFavori Model (Context7 Compliant)
 *
 * Tracks listing favorites by visitors/leads.
 */
class IlanFavori extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'ilan_favorileri';

    protected $fillable = [
        'ilan_id',
        'user_id',
    ];

    protected $casts = [];

    /**
     * İlan ilişkisi
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    /**
     * Kullanıcı (Müşteri) ilişkisi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
