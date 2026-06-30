<?php

namespace App\Modules\TakimYonetimi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gorev extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'gorevler';

    protected $fillable = [
        'baslik',
        'aciklama',
        'oncelik',
        'atanan_user_id',
        'olusturan_user_id',
        'kisi_id',
        'lead_id',
        'proje_id',
        'baslangic_tarihi',
        'bitis_tarihi',
        'tamamlanma_yuzdesi',
        'notlar',
        'gorev_durumu',
        'gorev_tipi',
    ];

    protected $casts = [
        'baslangic_tarihi' => 'datetime',
        'bitis_tarihi' => 'datetime',
        'tamamlanma_yuzdesi' => 'integer',
    ];

    /**
     * Canonical 'durum' bridge for Context7 compliance
     */
    public function getDurumAttribute()
    {
        return $this->attributes['gorev_durumu'] ?? null;
    }

    public function setDurumAttribute($value)
    {
        $this->attributes['gorev_durumu'] = $value;
    }

    // Enum değerleri
    public static function getOncelikler(): array
    {
        return ['acil', 'yuksek', 'normal', 'dusuk'];
    }

    public static function getDurumlar(): array
    {
        return ['bekliyor', 'devam_ediyor', 'tamamlandi', 'iptal', 'beklemede'];
    }

    public static function getTipler(): array
    {
        return ['musteri_takibi', 'ilan_hazirlama', 'musteri_ziyareti', 'dokuman_hazirlama', 'diger'];
    }

    // Relationships
    public function admin(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'olusturan_user_id');
    }

    public function danisman(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'atanan_user_id');
    }

    public function musteri(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Kisi::class, 'kisi_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Lead::class, 'lead_id');
    }

    public function proje(): BelongsTo
    {
        return $this->belongsTo(Proje::class, 'proje_id');
    }

    public function gorevTakip(): HasMany
    {
        return $this->hasMany(GorevTakip::class, 'gorev_id');
    }

    public function dosyalar(): HasMany
    {
        return $this->hasMany(GorevDosya::class, 'gorev_id');
    }

    public function durumTakip(): HasOne
    {
        return $this->hasOne(GorevTakip::class, 'gorev_id')
            ->where('gorev_durumu', '!=', 'tamamlandi')
            ->latest();
    }

    // Scopes
    public function scopeAktif($query)
    {
        return $query->whereIn('gorev_durumu', ['bekliyor', 'devam_ediyor', 'beklemede']);
    }

    public function scopeOncelik($query, $oncelik)
    {
        return $query->where('oncelik', $oncelik);
    }

    public function scopeTip($query, $tip)
    {
        return $query->where('tip', $tip);
    }

    public function scopeDanisman($query, $danismanId)
    {
        return $query->where('atanan_user_id', $danismanId);
    }

    public function scopeDeadlineYaklasan($query, $gun = 1)
    {
        return $query->where('bitis_tarihi', '<=', now()->addDays($gun))
            ->where('gorev_durumu', '!=', 'tamamlandi');
    }

    public function scopeGeciken($query)
    {
        return $query->where('bitis_tarihi', '<', now())
            ->where('gorev_durumu', '!=', 'tamamlandi');
    }

    public function scopeGecikmis($query)
    {
        return $this->scopeGeciken($query);
    }

    public function scopeByDurum($query, $durum)
    {
        return $query->where('gorev_durumu', $durum);
    }

    // Accessors
    public function getOncelikEtiketiAttribute(): string
    {
        $etiketler = [
            'acil' => '<span class="badge bg-danger">Acil</span>',
            'yuksek' => '<span class="badge bg-warning">Yüksek</span>',
            'normal' => '<span class="badge bg-info">Normal</span>',
            'dusuk' => '<span class="badge bg-secondary">Düşük</span>',
        ];

        return $etiketler[$this->oncelik] ?? $etiketler['normal'];
    }

    public function getDurumEtiketiAttribute(): string
    {
        $etiketler = [
            'bekliyor' => '<span class="badge bg-warning">Bekliyor</span>',
            'devam_ediyor' => '<span class="badge bg-primary">Devam Ediyor</span>',
            'tamamlandi' => '<span class="badge bg-success">Tamamlandı</span>',
            'iptal' => '<span class="badge bg-danger">İptal</span>',
            'beklemede' => '<span class="badge bg-secondary">Beklemede</span>',
        ];

        return $etiketler[$this->gorev_durumu] ?? $etiketler['bekliyor'];
    }

    public function getTipEtiketiAttribute(): string
    {
        $etiketler = [
            'musteri_takibi' => '<span class="badge bg-info">Müşteri Takibi</span>',
            'ilan_hazirlama' => '<span class="badge bg-primary">İlan Hazırlama</span>',
            'musteri_ziyareti' => '<span class="badge bg-success">Müşteri Ziyareti</span>',
            'dokuman_hazirlama' => '<span class="badge bg-warning">Doküman Hazırlama</span>',
            'diger' => '<span class="badge bg-secondary">Diğer</span>',
        ];

        return $etiketler[$this->tip] ?? $etiketler['diger'];
    }

    public function getGecikmeDurumuAttribute(): string
    {
        if ($this->gorev_durumu === 'tamamlandi') {
            return 'tamamlandi';
        }

        if (! $this->deadline) {
            return 'deadline_yok';
        }

        if ($this->deadline < now()) {
            return 'gecikti';
        }

        if ($this->deadline <= now()->addDay()) {
            return 'yaklasiyor';
        }

        return 'normal';
    }

    public function getGecikmeGunuAttribute(): ?int
    {
        if (! $this->deadline || $this->gorev_durumu === 'tamamlandi') {
            return null;
        }

        return now()->diffInDays($this->deadline, false);
    }

    // Methods
    public function geciktiMi(): bool
    {
        return $this->deadline && $this->deadline < now() && $this->gorev_durumu !== 'tamamlandi';
    }

    public function deadlineYaklasiyorMu(int $gun = 1): bool
    {
        return $this->deadline &&
               $this->deadline <= now()->addDays($gun) &&
               $this->gorev_durumu !== 'tamamlandi';
    }

    public function tamamlanabilirMi(): bool
    {
        return in_array($this->gorev_durumu, ['bekliyor', 'devam_ediyor']);
    }

    public function atanabilirMi(): bool
    {
        return $this->gorev_durumu === 'bekliyor' && ! $this->atanan_user_id;
    }

    public function tamamla()
    {
        return $this->update([
            'gorev_durumu' => 'tamamlandi',
            'bitis_tarihi' => now(),
        ]);
    }

    public function iptalEt()
    {
        return $this->update([
            'gorev_durumu' => 'iptal',
        ]);
    }

    protected function deadline(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value, array $attributes) {
                return isset($attributes['bitis_tarihi']) ? \Illuminate\Support\Carbon::parse($attributes['bitis_tarihi']) : null;
            },
            set: function ($value) {
                return ['bitis_tarihi' => $value];
            }
        );
    }

}
