<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateDesignAudit extends BaseModel
{
    use HasCountryScope;

    protected $table = 'template_design_audits';

    protected $fillable = [
        'yayin_tipi_id',
        'kategori_id',
        'user_id',
        'run_uuid',
        'apply_mode',
        'before_snapshot',
        'changes',
        'design_payload',
        'rolled_back',
        'rolled_back_at',
        'rolled_back_by',
    ];

    protected $casts = [
        'before_snapshot' => 'array',
        'changes' => 'array',
        'design_payload' => 'array',
        'rolled_back' => 'boolean',
        'rolled_back_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(YayinTipiSablonu::class, 'yayin_tipi_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rolledBackByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rolled_back_by');
    }

    public function scopeActive($query)
    {
        return $query->where('rolled_back', false);
    }

    public function scopeForTemplate($query, int $templateId)
    {
        return $query->where('yayin_tipi_id', $templateId);
    }
}
