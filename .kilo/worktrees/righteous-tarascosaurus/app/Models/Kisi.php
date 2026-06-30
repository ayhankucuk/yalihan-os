<?php

namespace App\Models;

use App\Enums\KisiDurumu;
use App\Enums\KisiTipi;
use App\Enums\YatirimciProfili;
use App\Traits\HasActiveScope;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
// ❌ Context7: Deprecated CRM models removed - tables don't exist

/**
 * App\Models\Kisi
 *
 * @SAB SEALED 🛡️ (CRM Foundation Lock)
 * @SSOT: This is the definitive Kişi model for all domains (Finance, Task, AI, CRM).
 * ❌ DEPRECATED: App\Modules\Crm\Models\Kisi has been removed.
 *
 * @property int $id
 * @property string $ad
 * @property string $soyad
 * @property string|null $telefon
 * @property string|null $email
 * @property string|null $notlar
 * @property \App\Enums\KisiTipi|null $kisi_tipi Context7: Primary field (Enum)
 * @property string|null $musteri_tipi Deprecated: Use kisi_tipi instead
 * @property bool $aktiflik_durumu Context7: tinyInteger(1) 0=inactive, 1=active (mapped to aktiflik_durumu column)
 * @property string|null $kaynak
 * @property int|null $danisman_id
 *
 * // Global Adres İlişkisel Alanları
 * @property int|null $ulke_id
 * @property int|null $il_id
 * @property int|null $ilce_id
 * @property int|null $mahalle_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * // Accessors
 * @property-read string $tam_ad
 * @property-read string $tam_adres
 *
 * // İlişkiler (Relationships)
 * @property-read User|null $danisman
 * @property-read Ulke|null $ulke
 * @property-read Il|null $il
 * @property-read Ilce|null $ilce
 * @property-read Mahalle|null $mahalle
 * @property-read \Illuminate\Database\Eloquent\Collection|Talep[] $talepler
 * @property-read int|null $talepler_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Ilan[] $ilanlarAsSahibi
 * @property-read int|null $ilanlar_as_sahibi_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Ilan[] $ilanlarAsIlgili
 * @property-read int|null $ilanlar_as_ilgili_count
 */
class Kisi extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasActiveScope;
    use LogsActivity;
    use HasCountryScope;

    protected $table = 'kisiler';

    protected $fillable = [
        // Temel Kişi Bilgileri
        'ad',
        'soyad',
        'telefon',
        'telefon_2',
        'eposta', // Context7: email → eposta (canonical)
        'notlar',
        'user_id',

        // Context7 Standart Alanları
        'aktiflik_durumu', // ✅ SAB: Fixed from is_active
        'kisi_tipi', // ✅ SAB: PREFERRED field name
        'danisman_id',

        // Global Adres Sistemi (Context7 uyumlu)
        'il_id',
        'ilce_id',
        'mahalle_id',
        'adres',

        // CRM Genişletilmiş Alanları
        'tc_kimlik',
        'meslek',

        // AI Scoring Fields (Context7: CRM Intelligence)
        'crm_surec_asamasi', // ✅ SAB: Added to fillable
        'skor',              // Context7: CRM lead scoring 0-100

        // CRM Pipeline & Segmentation
        'kaynak', // Context7: website, telefon, referans, etc.
        'ulke_id',
        'sesli_onay_verildi',
    ];

    protected $appends = ['tam_ad', 'danisman_verisi'];

    protected $casts = [
        'son_etkilesim' => 'datetime',
        'karar_verici_mi' => 'boolean',
        'satis_potansiyeli' => 'integer',
        'aktiflik_durumu' => 'boolean', // ✅ SAB: Force boolean cast for tests
        'kisi_tipi' => \App\Enums\KisiTipi::class, // Context7: Added enum cast
        'crm_surec_asamasi' => KisiDurumu::class, // Context7: renamed from crm_aktiflik_durumu
        'yatirimci_profili' => \App\Casts\NullableYatirimciProfiliCast::class, // PHP 8.4 safe cast
        'ulke_id' => 'integer',
        'sesli_onay_verildi' => 'boolean',
    ];

    // ======================================================================
    // ERİŞİMCİLER & DEĞİŞTİRİCİLER (ACCESSORS & MUTATORS)
    // ======================================================================

    /**
     * Context7: TC Kimlik mutator (encryption removed - no tc_kimlik_encrypted column exists)
     */
    public function setTcKimlikAttribute($value): void
    {
        $this->attributes['tc_kimlik'] = $value;
    }

    public function getTcKimlikMaskedAttribute(): ?string
    {
        $v = $this->attributes['tc_kimlik'] ?? null;
        if (! $v) {
            return null;
        }
        $len = strlen($v);
        if ($len <= 4) {
            return str_repeat('*', max(0, $len));
        }

        return str_repeat('*', $len - 4) . substr($v, -4);
    }

    /**
     * Context7: Legacy accessor removed in Phase 3A
     */



    /**
     * yatirimci_profili accessor: Veritabanındaki string'i Enum'a çevirir
     */
    public function getYatirimciProfiliAttribute($value): ?YatirimciProfili
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof YatirimciProfili) {
            return $value;
        }

        return YatirimciProfili::tryFrom($value);
    }

    /**
     * yatirimci_profili mutator: Enum'ı veritabanına kaydedilecek string'e çevirir
     */
    public function setYatirimciProfiliAttribute($value): void
    {
        if ($value instanceof YatirimciProfili) {
            $this->attributes['yatirimci_profili'] = $value->value;
        } elseif (is_string($value)) {
            $enum = YatirimciProfili::tryFrom($value);
            $this->attributes['yatirimci_profili'] = $enum?->value ?? $value;
        } else {
            $this->attributes['yatirimci_profili'] = $value;
        }
    }

    public function getTamAdAttribute(): string
    {
        return trim($this->ad . ' ' . $this->soyad);
    }

    public function getTamAdresAttribute(): string
    {
        $adresParcalari = [
            $this->mahalle->name ?? null,
            $this->ilce->name ?? null,
            $this->il->il_adi ?? null,
            $this->ulke->name ?? null,
        ];

        return implode(', ', array_filter($adresParcalari));
    }

    /**
     * Danışman verilerini hem User hem de Danisman modelinden alır
     */
    public function getDanismanVerisiAttribute()
    {
        if (! $this->danisman_id) {
            return null;
        }

        // Önce User modelinden kontrol et
        $userDanisman = User::find($this->danisman_id);
        if ($userDanisman) {
            return (object) [
                'id' => $userDanisman->id,
                'name' => $userDanisman->name,
                'email' => $userDanisman->email,
                'phone_number' => $userDanisman->phone_number,
                'source' => 'user_model',
            ];
        }

        // User modelinde bulunamazsa null döndür
        return null;
    }

    // ======================================================================
    // İLİŞKİLER (RELATIONSHIPS)
    // ======================================================================

    /**
     * Bu kişinin danışmanını döndürür (User modeli ile)
     */
    public function danisman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'danisman_id');
    }

    /**
     * User modeli ile danışman ilişkisi (Eloquent için)
     */
    public function userDanisman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'danisman_id');
    }

    /**
     * Context7: Kişinin favorilediği ilanlar (BelongsToMany - ilan_favorileri pivot)
     */
    public function favoriIlanlar(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Ilan::class, 'ilan_favorileri', 'kisi_id', 'ilan_id')
            ->withTimestamps()
            ->withPivot('is_active') // context7-ignore
            ->wherePivot('is_active', 1) // context7-ignore
            ->orderByDesc('ilan_favorileri.created_at'); // context7-ignore
    }

    /**
     * Context7: Tüm favori ilişkileri (aktif ve pasif)
     */
    public function tumFavoriIlanlar(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Ilan::class, 'ilan_favorileri', 'kisi_id', 'ilan_id')
            ->withTimestamps()
            ->withPivot('is_active') // context7-ignore
            ->orderByDesc('ilan_favorileri.created_at'); // context7-ignore
    }

    public function talepler(): HasMany
    {
        return $this->hasMany(Talep::class, 'kisi_id');
    }

    /**
     * İlanlar (Alias for ilanlarAsSahibi)
     * Context7: Fix for RelationNotFoundException
     */
    public function ilanlar(): HasMany
    {
        return $this->ilanlarAsSahibi();
    }

    /**
     * Bu kişinin "ilan sahibi" olduğu ilanları döndürür.
     * Context7: Foreign key is 'user_id' in ilanlar table
     */
    public function ilanlarAsSahibi(): HasMany
    {
        return $this->hasMany(Ilan::class, 'user_id');
    }
    /**
     * CRM - Referans veren kişi
     */
    public function referansVeren(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'referans_kisi_id');
    }

    /**
     * CRM - Bu kişinin referans verdiği kişiler
     */
    public function referanslar(): HasMany
    {
        return $this->hasMany(Kisi::class, 'referans_kisi_id');
    }

    /**
     * Bu kişinin "ilgili kişi" olduğu ilanları döndürür.
     */
    public function ilanlarAsIlgili(): HasMany
    {
        return $this->hasMany(Ilan::class, 'ilgili_kisi_id');
    }

    // --- Global Adres İlişkileri ---

    public function ulke(): BelongsTo
    {
        return $this->belongsTo(Ulke::class, 'ulke_id');
    }

    // Context7 kuralı: il() relationship kullanımı

    /**
     * İl relationship (External Context standard)
     */
    public function il(): BelongsTo
    {
        return $this->belongsTo(Il::class, 'il_id');
    }

    // Context7 kuralı: il() relationship kullanımı

    public function ilce(): BelongsTo
    {
        return $this->belongsTo(Ilce::class, 'ilce_id');
    }

    public function mahalle(): BelongsTo
    {
        return $this->belongsTo(Mahalle::class, 'mahalle_id');
    }

    /**
     * Bu kişinin etiketlerini döndürür.
     */
    public function etiketler()
    {
        return $this->belongsToMany(Etiket::class, 'etiket_kisi', 'kisi_id', 'etiket_id')
            ->withPivot('user_id')
            ->withTimestamps();
    }

    /**
     * Bu kişinin durum etiketlerini döndürür (Context7 uyumlu).
     */
    public function durumEtiketler()
    {
        return $this->belongsToMany(Etiket::class, 'etiket_kisi', 'kisi_id', 'etiket_id')
            ->active() // context7-ignore
            ->withPivot('user_id')
            ->withTimestamps();
    }



    // ======================================================================
    // SCOPES (Context7 Uyumlu)
    // ======================================================================

    /**
     * Aktif kişileri getir (Context7 uyumlu).
     */
    public function scopeDurumAktif($query)
    {
        return $query->active(); // context7-ignore
    }

    /**
     * Pasif kişileri getir (Context7 uyumlu).
     */
    public function scopeDurumPasif($query)
    {
        return $query->where('aktiflik_durumu', false);
    }

    /**
     * Alias for scopeDurumAktif (Context7: Backward compatibility)
     */
    public function scopeAktif($query)
    {
        return $query->where('aktiflik_durumu', true);
    }

    /**
     * Alias for scopeDurumPasif (Context7: Backward compatibility)
     */
    public function scopePasif($query)
    {
        return $this->scopeDurumPasif($query);
    }

    /**
     * Kişi arama scope (Context7 uyumlu).
     */
    public function scopeSearch($query, $searchTerm)
    {
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->where(function ($q) use ($searchTerm) {
            $q->where('ad', 'like', "%{$searchTerm}%")
                ->orWhere('soyad', 'like', "%{$searchTerm}%")
                ->orWhere('telefon', 'like', "%{$searchTerm}%")
                ->orWhere('email', 'like', "%{$searchTerm}%")
                ->orWhere('tc_kimlik', 'like', "%{$searchTerm}%");
        });
    }

    /**
     * Danışmana göre filtrele (Context7 uyumlu).
     */
    public function scopeByDanisman(
        \Illuminate\Database\Eloquent\Builder $query,
        int $danismanId
    ): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('danisman_id', $danismanId);
    }



    /**
     * Kişi tipine göre filtrele (Context7 standard).
     */
    public function scopeByKisiTipi($query, $kisiTipi)
    {
        return $query->where('kisi_tipi', $kisiTipi);
    }

    // ======================================================================
    // HELPER METHODS (Context7 Uyumlu)
    // ======================================================================

    /**
     * Kişinin tam adını döndürür (Context7 uyumlu).
     */
    public function getFullNameAttribute(): string
    {
        return $this->tam_ad;
    }

    /**
     * Kişinin iletişim bilgilerini döndürür (Context7 uyumlu).
     */
    public function getIletisimBilgileriAttribute(): array
    {
        return [
            'telefon' => $this->telefon,
            'email' => $this->email,
            'adres' => $this->tam_adres,
        ];
    }

    /**
     * Kişinin CRM skorunu hesaplar (Context7 uyumlu).
     */
    public function getCrmScoreAttribute(): int
    {
        $score = 0;

        // Temel bilgiler (40 puan)
        if ($this->ad && $this->soyad) {
            $score += 10;
        }
        if ($this->telefon) {
            $score += 10;
        }
        if ($this->email) {
            $score += 10;
        }
        if ($this->tc_kimlik) {
            $score += 10;
        }

        // Adres bilgileri (30 puan)
        if ($this->il_id) {
            $score += 10;
        }
        if ($this->ilce_id) {
            $score += 10;
        }
        if ($this->mahalle_id) {
            $score += 10;
        }

        // CRM bilgileri (30 puan)
        // ✅ SAB: kisi_tipi preferred, musteri_tipi backward compat
        if ($this->kisi_tipi ?? $this->musteri_tipi) {
            $score += 10;
        }
        if ($this->meslek) {
            $score += 10;
        }
        if ($this->gelir_duzeyi) {
            $score += 10;
        }

        return min($score, 100); // Maksimum 100 puan
    }

    /**
     * Kişinin ilan sahibi olma uygunluğunu kontrol eder (Context7 uyumlu).
     */
    public function isOwnerEligible(): bool
    {
        return $this->aktiflik_durumu === true &&
            $this->tc_kimlik &&
            $this->telefon &&
            $this->il_id;
    }

    /**
     * Kişinin potansiyel müşteri olma durumunu kontrol eder (Context7 uyumlu).
     */
    public function isPotentialCustomer(): bool
    {
        // ✅ SAB: kisi_tipi preferred, musteri_tipi backward compat
        $tip = $this->kisi_tipi ?? $this->musteri_tipi;
        return in_array($tip, ['alici', 'kiraci']) &&
            $this->aktiflik_durumu === true;
    }

    /**
     * Kişinin satıcı olma durumunu kontrol eder (Context7 uyumlu).
     */
    public function isSeller(): bool
    {
        // ✅ SAB: kisi_tipi preferred, musteri_tipi backward compat
        $tip = $this->kisi_tipi ?? $this->musteri_tipi;
        return in_array($tip, ['satici', 'ev_sahibi']) &&
            $this->aktiflik_durumu === true;
    }

    /**
     * Kişinin aktif durumunu kontrol et
     * Context7: Harici yardım metodu (Modül modelinden taşındı)
     */
    public function isActive(): bool
    {
        return $this->aktiflik_durumu === true;
    }

    /**
     * Görüntüleme metni (dropdown için)
     * Context7: Display helper metodu (Modül modelinden taşındı)
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
    /**
     * Advisor photos (Phase 5.3: Photo Intelligence System)
     */
    public function photos(): HasMany
    {
        return $this->hasMany(AdvisorPhoto::class, 'kisi_id');
    }

    /**
     * Featured advisor photo
     */
    public function featuredPhoto()
    {
        return $this->hasOne(AdvisorPhoto::class, 'kisi_id')
            ->where('featured', true);
    }

    /**
     * Context7 Safe-Bridge Accessor
     * Legacy field → 'is_active' (sealed column)
     *
     * @context7-proxy
     */
    public function getAktiflikBilgisiAttribute()
    {
        return $this->aktiflik_durumu ?? $this->durum ?? 'Pasif';
    }

    /**
     * AI/Intelligence Embedding Relationship
     * Context7: CRM Intelligence Integration
     */
    public function embedding(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LeadEmbedding::class, 'kisi_id');
    }

    /**
     * Activity Log Configuration
     * Architectural Enhancement: Audit trail for all contact changes
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'ad',
                'soyad',
                'telefon',
                'email',
                'aktiflik_durumu',
                'kisi_tipi',
                'crm_surec_asamasi',
                'danisman_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Kişi {$eventName}")
            ->useLogName('kisiler');
    }
}
