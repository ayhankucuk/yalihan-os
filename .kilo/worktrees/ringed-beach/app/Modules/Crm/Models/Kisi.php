<?php

namespace App\Modules\Crm\Models;

use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $danisman_id
 * @property string $ad
 * @property string $soyad
 * @property string|null $email
 * @property string|null $telefon
 * @property string|null $adres
 * @property \Illuminate\Database\Eloquent\Collection<int, KisiNot> $notlar
 * @property string|null $kaynak
 * @property bool $aktiflik_durumu
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read User|null $danisman
 * @property-read string $name
 * @property-read int|null $notlar_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Talep> $talepler
 * @property-read int|null $talepler_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi whereAd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi whereAdres($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi whereDanismanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi whereAktiflikDurumu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi whereKaynak($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi whereNotlar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi whereSoyad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi whereTelefon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kisi whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Kisi extends Model
{
    use HasFactory;

    /**
     * Tablo adı
     *
     * @var string
     */
    protected $table = 'kisiler';

    /**
     * Toplu atanabilir alanlar
     *
     * @var array
     */
    protected $fillable = [
        'ad',
        'soyad',
        'email',
        'telefon',
        'adres', // ✅ SAB: Adres kolonu (text) - adres_detay YOK
        'notlar', // migration'daki 'notlar' text alanı için
        // migration'daki  json alanı için
        // migration'daki enum alanı için
        'kisi_tipi', // Context7: Kişi tipi eklendi (2025-11-01)
        'danisman_id',
        // Context7: Location fields eklendi (2025-11-01)
        'il_id',
        'ilce_id',
        'aktiflik_durumu',
    ];

    /**
     * Cast edilecek alanlar
     *
     * @var array
     */
    protected $casts = [
        'etiketler' => 'array', // JSON alanı array olarak cast edilecek
        // SSOT: kisiler.aktiflik_durumu (tinyint 0/1)
        'aktiflik_durumu' => 'boolean',
    ];

    /**
     * Arama sorgusu için scope.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $searchTerm
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $searchTerm)
    {
        if ($searchTerm) {
            return $query->where(function ($q) use ($searchTerm) {
                $q->where('ad', 'like', "%{$searchTerm}%")
                    ->orWhere('soyad', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('telefon', 'like', "%{$searchTerm}%");
            });
        }

        return $query;
    }

    public function scopePasif($query)
    {
        return $query->where('aktiflik_durumu', 0);
    }

    public function scopeAktif($query)
    {
        return $query->where('aktiflik_durumu', 1);
    }

    public function scopeByDanisman($query, $danismanId)
    {
        return $query->where('danisman_id', $danismanId);
    }

    public function scopeByMusteriTipi($query, $tip)
    {
        return $query->where('kisi_tipi', $tip);
    }

    /**
     * Danışman ilişkisi
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function danisman()
    {
        return $this->belongsTo(User::class, 'danisman_id');
    }

    public function il()
    {
        return $this->belongsTo(\App\Models\Il::class, 'il_id');
    }

    public function ilce()
    {
        return $this->belongsTo(\App\Models\Ilce::class, 'ilce_id');
    }

    public function mahalle()
    {
        return $this->belongsTo(\App\Models\Mahalle::class, 'mahalle_id');
    }

    /**
     * Talepler ilişkisi
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function talepler()
    {
        return $this->hasMany(Talep::class, 'kisi_id');
    }

    /**
     * Notlar ilişkisi
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notlar()
    {
        return $this->hasMany(KisiNot::class, 'kisi_id');
    }

    /**
     * Etiketler ilişkisi (many-to-many)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function etiketler()
    {
        return $this->belongsToMany(
            Etiket::class,
            'etiket_kisi',
            'kisi_id',
            'etiket_id'
        )
        ->withPivot('user_id')
        ->withTimestamps();
    }

    /**
     * Kişinin tam adını döndürür
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->ad} {$this->soyad}";
    }

    /**
     * Kişinin tam adını döndürür (tam_ad alias)
     *
     * @return string
     */
    public function getTamAdAttribute()
    {
        return "{$this->ad} {$this->soyad}";
    }

    /**
     * Kişinin ilan sahibi olma uygunluğunu kontrol et
     * Context7: View helper metodu
     *
     * @return bool
     */
    public function isOwnerEligible(): bool
    {
        return !empty($this->ad) &&
            !empty($this->soyad) &&
            !empty($this->telefon) &&
            (bool) $this->aktiflik_durumu;
    }

    /**
     * Kişinin aktiflik durumunu kontrol et
     * Context7: Aktiflik helper metodu
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->aktiflik_durumu;
    }

    /**
     * Görüntüleme metni (dropdown için)
     * Context7: Display helper metodu
     *
     * @return string
     */
    public function getDisplayTextAttribute(): string
    {
        $parts = [$this->tam_ad];

        if ($this->telefon) {
            $parts[] = $this->telefon;
        }

        if ($this->il) {
            $parts[] = $this->il->il_adi;
        }

        return implode(' - ', $parts);
    }
}
