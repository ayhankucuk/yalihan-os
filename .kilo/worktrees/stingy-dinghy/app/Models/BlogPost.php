<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * 🛡️ SAB SEALED: BlogPost
 *
 * Public read-model. Yazma işlemleri Admin\BlogController üzerinden.
 *
 * @property int         $id
 * @property string      $title
 * @property string      $slug
 * @property string|null $content
 * @property string|null $excerpt
 * @property int|null    $category_id
 * @property int|null    $author_id
 * @property bool        $yayinlandi
 * @property string|null $yayin_durumu
 * @property int         $view_count
 * @property string|null $kapak_resmi
 * @property string|null $kapak_resmi_alt
 * @property \Carbon\Carbon|null $published_at
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property bool        $is_breaking_news
 * @property bool        $is_featured
 * @property bool        $is_sticky
 */
class BlogPost extends BaseModel
{
    use HasCountryScope;

    protected $table = 'blog_posts';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'category_id',
        'author_id',
        'yayinlandi',
        'yayin_durumu',
        'view_count',
        'kapak_resmi',
        'kapak_resmi_alt',
        'published_at',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_breaking_news',
        'is_featured',
        'is_sticky',
    ];

    protected $casts = [
        'yayinlandi'      => 'boolean',
        'is_breaking_news' => 'boolean',
        'is_featured'     => 'boolean',
        'is_sticky'       => 'boolean',
        'published_at'    => 'datetime',
        'view_count'      => 'integer',
    ];

    // ─── Relations ────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    /**
     * author() alias — admin controller 'author' relation kullanıyor
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * user() alias — view'lar $post->user->name kullanıyor
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag', 'post_id', 'tag_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class, 'post_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('yayinlandi', true)
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // ─── Computed Attributes ──────────────────────────────────────

    /**
     * Excerpt yoksa içeriğin ilk 200 karakteri döner.
     */
    public function getExcerptOrContentAttribute(): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }
        return Str::limit(strip_tags($this->content ?? ''), 200);
    }

    /**
     * Tahmini okuma süresi (dakika cinsinden metin).
     */
    public function getReadingTimeFormattedAttribute(): string
    {
        $wordCount = str_word_count(strip_tags($this->content ?? ''));
        $minutes   = max(1, (int) ceil($wordCount / 200));

        return $minutes . ' dk';
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * Görüntülenme sayısını artır.
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
}
