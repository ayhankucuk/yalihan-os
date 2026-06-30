<?php

namespace App\Modules\Emlak\Models;

use App\Models\Ilan;
use App\Modules\BaseModule\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $gelistirici_adi
 * @property \Illuminate\Support\Carbon|null $tamamlanma_tarihi
 * @property string $yayin_durumu
 * @property bool $one_cikan
 * @property string $adres_il
 * @property string $adres_ilce
 * @property string|null $adres_mahalle
 * @property numeric|null $lat
 * @property numeric|null $lng
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read mixed $aciklama
 * @property-read mixed $baslik
 * @property-read mixed $kapak_foto
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Emlak\Models\ProjeGorsel> $gorseller
 * @property-read int|null $gorseller_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Ilan> $ilanlar
 * @property-read int|null $ilanlar_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Emlak\Models\ProjeTranslation> $translations
 * @property-read int|null $translations_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje whereAdresIl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje whereAdresIlce($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje whereAdresMahalle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje whereGelistiriciAdi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje whereLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje whereOneCikan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje whereYayinDurumu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje whereTamamlanmaTarihi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proje withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Proje extends BaseModel
{
    use HasFactory, SoftDeletes;

    /**
     * İlişkilendirilmiş tablo adı
     */
    protected $table = 'projeler';

    /**
     * Toplu atanabilir alanlar
     */
    protected $fillable = [
        
    ];

    /**
     * Cast edilecek özellikler
     */
    protected $casts = [
        'tamamlanma_tarihi' => 'date',
        'one_cikan' => 'boolean',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    /**
     * Projeye ait çeviriler
     */
    public function translations()
    {
        return $this->hasMany(ProjeTranslation::class, 'proje_id');
    }

    /**
     * Projenin görselleri
     */
    public function gorseller()
    {
        return $this->hasMany(ProjeGorsel::class, 'proje_id')->orderBy('sira', 'asc');
    }

    /**
     * Proje içindeki ilanlar
     */
    public function ilanlar()
    {
        return $this->hasMany(Ilan::class, 'proje_id');
    }

    /**
     * Projenin il bilgisi
     */
    public function il()
    {
        return $this->belongsTo(\App\Models\Il::class, 'adres_il');
    }

    /**
     * Projenin ilçe bilgisi
     */
    public function ilce()
    {
        return $this->belongsTo(\App\Models\Ilce::class, 'adres_ilce');
    }

    /**
     * Şu anki dildeki başlığı alır
     */
    public function getBaslikAttribute()
    {
        $translation = $this->translations()->where('locale', app()->getLocale())->first();
        if ($translation) {
            return $translation->proje_adi;
        }
        // Fallback to first available translation
        $firstTranslation = $this->translations()->first();

        return $firstTranslation ? $firstTranslation->proje_adi : 'Başlık Bulunamadı';
    }

    /**
     * Şu anki dildeki açıklamayı alır
     */
    public function getAciklamaAttribute()
    {
        $translation = $this->translations()->where('locale', app()->getLocale())->first();
        if ($translation) {
            return $translation->aciklama;
        }
        // Fallback to first available translation
        $firstTranslation = $this->translations()->first();

        return $firstTranslation ? $firstTranslation->aciklama : '';
    }

    /**
     * Kapak fotoğrafını alır
     */
    public function getKapakFotoAttribute()
    {
        return $this->gorseller()->first()->dosya_yolu ?? 'img/default-project.jpg';
    }
}
