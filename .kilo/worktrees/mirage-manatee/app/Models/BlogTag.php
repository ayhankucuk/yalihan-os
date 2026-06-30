<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 🛡️ SAB SEALED: BlogTag
 *
 * @property int    $id
 * @property string $name
 * @property string $slug
 * @property bool   $aktiflik_durumu
 */
class BlogTag extends BaseModel
{
    use HasCountryScope;

    protected $table = 'blog_tags';

    protected $fillable = [
        'name',
        'slug',
        'aktiflik_durumu',
    ];

    protected $casts = [
        'aktiflik_durumu' => 'boolean',
    ];

    // ─── Relations ────────────────────────────────────────────────

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag', 'tag_id', 'post_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('aktiflik_durumu', true);
    }
}
