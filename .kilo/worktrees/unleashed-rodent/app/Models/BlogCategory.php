<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 🛡️ SAB SEALED: BlogCategory
 *
 * Blog kategori modeli. Public read-only; yazma işlemleri admin üzerinden.
 *
 * @property int    $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $color
 * @property bool   $aktiflik_durumu
 */
class BlogCategory extends BaseModel
{
    use HasCountryScope;

    protected $table = 'blog_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'aktiflik_durumu',
    ];

    protected $casts = [
        'aktiflik_durumu' => 'boolean',
    ];

    // ─── Relations ────────────────────────────────────────────────

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'category_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('aktiflik_durumu', true);
    }
}
