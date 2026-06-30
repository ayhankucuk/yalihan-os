<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateChangeLog extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $fillable = [
        'yayin_tipi_sablonu_id', // V2 SSOT — junction reference
        'ups_template_id',
        'user_id',
        'aksiyon_tipi',
        'feature_id',
        'entity_type',
        'entity_id',
        'aciklama',
        'eski_degerler',
        'yeni_degerler',
        'versiyon_numarasi',
    ];

    protected $casts = [
        'eski_degerler' => 'array',
        'yeni_degerler' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Template relationship (V2: yayin_tipi_sablonu_id)
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(YayinTipiSablonu::class, 'yayin_tipi_sablonu_id');
    }

    /**
     * User who made the change
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Feature that was changed
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class)->withDefault();
    }

    /**
     * Log a feature addition (V2: yayin_tipi_sablonu_id)
     */
    public static function logFeatureAdded(
        int $templateId,
        int $featureId,
        int $userId,
        int $version,
        array $featureData = []
    ): self {
        return self::create([
            'yayin_tipi_sablonu_id' => $templateId,
            'user_id' => $userId,
            'feature_id' => $featureId,
            'aksiyon_tipi' => 'feature_added',
            'yeni_degerler' => $featureData,
            'versiyon_numarasi' => $version,
        ]);
    }

    /**
     * Log a feature removal (V2: yayin_tipi_sablonu_id)
     */
    public static function logFeatureRemoved(
        int $templateId,
        int $featureId,
        int $userId,
        int $version,
        array $featureData = []
    ): self {
        return self::create([
            'yayin_tipi_sablonu_id' => $templateId,
            'user_id' => $userId,
            'feature_id' => $featureId,
            'aksiyon_tipi' => 'feature_removed',
            'eski_degerler' => $featureData,
            'versiyon_numarasi' => $version,
        ]);
    }

    /**
     * Get changelog for a template (V2: yayin_tipi_sablonu_id)
     */
    public static function forTemplate(int $templateId)
    {
        return self::where('yayin_tipi_sablonu_id', $templateId)
            ->orderBy('created_at', 'desc') // context7-ignore
            ->with(['user', 'feature']);
    }

    /**
     * P0-B FIX: View uyumluluk accessor — $change->action → aksiyon_tipi
     * "Son Değişiklikler" timeline view 'action' okur, DB 'aksiyon_tipi' yazar.
     * Context7: sanal accessor, fiziksel kolon değil.
     */
    public function getActionAttribute(): string
    {
        return $this->aksiyon_tipi ?? '';
    }

    /**
     * P0-B FIX: View uyumluluk accessor — $change->description → aciklama
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->aciklama;
    }

    /**
     * Açıkça okunabilir action label
     *
     * @return string
     */
    public function getActionLabel(): string
    {
        return match($this->aksiyon_tipi) {
            'create' => '✨ Oluşturuldu',
            'update' => '✏️ Güncellendi',
            'delete' => '🗑️ Silindi',
            'feature_added' => '➕ Özellik Eklendi',
            'feature_removed' => '➖ Özellik Kaldırıldı',
            'feature_updated' => '🔄 Özellik Güncellendi',
            'ai_import' => '🤖 AI İçe Aktarma',
            'bulk_assign' => '📦 Toplu Atama',
            'master_apply' => '🏆 Master Şablon Uygulandı',
            default => $this->aksiyon_tipi,
        };
    }
}
