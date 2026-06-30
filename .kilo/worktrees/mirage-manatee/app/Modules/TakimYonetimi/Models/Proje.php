<?php

namespace App\Modules\TakimYonetimi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proje extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projeler';

    protected $fillable = [
        'aciklama',
        'baslangic_tarihi',
        'bitis_tarihi',
        'oncelik',
        
    ];

    protected $casts = [
        'baslangic_tarihi' => 'date',
        'bitis_tarihi' => 'date',
        'ilerleme_yuzdesi' => 'decimal:2',
        'takim_uyeleri' => 'array',
        'hedefler' => 'array',
        'metadata' => 'array',
    ];

    // Enum değerleri
    public static function getProjeDurumlari(): array
    {
        return ['Planlama', 'İnşaat', 'Tamamlandı'];
    }

    public static function getOncelikler(): array
    {
        return ['acil', 'yuksek', 'normal', 'dusuk'];
    }

    // Relationships
    public function admin(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'admin_id');
    }

    public function gorevler(): HasMany
    {
        return $this->hasMany(Gorev::class, 'proje_id');
    }

    // Scopes
    public function scopeAktif($query)
    {
        return $query->whereIn('proje_statusu', ['Planlama', 'İnşaat']);
    }

    public function scopeDurum($query, $status)
    {
        return $query->where('proje_statusu', $status);
    }

    public function scopeOncelik($query, $oncelik)
    {
        return $query->where('oncelik', $oncelik);
    }

    public function scopeGeciken($query)
    {
        return $query->where('bitis_tarihi', '<', now())
            ->where('proje_statusu', '!=', 'Tamamlandı');
    }

    // Accessors
    public function getDurumEtiketiAttribute(): string
    {
        $etiketler = [
            'Planlama' => '<span class="badge bg-info">Planlama</span>',
            'İnşaat' => '<span class="badge bg-primary">İnşaat</span>',
            'Tamamlandı' => '<span class="badge bg-success">Tamamlandı</span>',
        ];

        return $etiketler[$this->proje_statusu] ?? $etiketler['Planlama'];
    }

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

    public function getGecikmeDurumuAttribute(): string
    {
        if ($this->proje_statusu === 'Tamamlandı') {
            return 'tamamlandi';
        }

        if (! $this->bitis_tarihi) {
            return 'bitis_tarihi_yok';
        }

        if ($this->bitis_tarihi < now()) {
            return 'gecikti';
        }

        if ($this->bitis_tarihi <= now()->addWeek()) {
            return 'yaklasiyor';
        }

        return 'normal';
    }

    public function getGecikmeGunuAttribute(): ?int
    {
        if (! $this->bitis_tarihi || $this->proje_statusu === 'Tamamlandı') {
            return null;
        }

        return now()->diffInDays($this->bitis_tarihi, false);
    }

    // Methods
    public function geciktiMi(): bool
    {
        return $this->bitis_tarihi && $this->bitis_tarihi < now() && $this->proje_statusu !== 'Tamamlandı';
    }

    public function bitisTarihiYaklasiyorMu(int $gun = 7): bool
    {
        return $this->bitis_tarihi &&
               $this->bitis_tarihi <= now()->addDays($gun) &&
               $this->proje_statusu !== 'Tamamlandı';
    }

    public function statusMi(): bool
    {
        return in_array($this->proje_statusu, ['Planlama', 'İnşaat']);
    }

    public function tamamlanabilirMi(): bool
    {
        return $this->proje_statusu === 'İnşaat';
    }

    public function ilerlemeYuzdesiGuncelle(): void
    {
        if ($this->gorevler()->count() === 0) {
            $this->update(['ilerleme_yuzdesi' => 0]);

            return;
        }

        $tamamlananGorevler = $this->gorevler()->where('yayin_durumu', 'tamamlandi')->count();
        $toplamGorevler = $this->gorevler()->count();

        $yuzde = round(($tamamlananGorevler / $toplamGorevler) * 100, 2);

        $this->update(['ilerleme_yuzdesi' => $yuzde]);
    }
}
