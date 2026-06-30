<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 🛡️ SAB SEALED
 * GovernanceAuditLog — Yönetişim (Governance) süreçlerindeki (Draft, Promote vb.) yapıların immutable salt-okunur denetim kaydı.
 */
class GovernanceAuditLog extends BaseModel
{
    use HasCountryScope;

    protected $table = 'governance_audit_logs';

    /**
     * DB column 1:1 mapping directly. No ghost models.
     */
    protected $fillable = [
        'entity_type',
        'entity_id',
        'ulke_id',
        'action_type',
        'from_state',
        'to_state',
        'actor_id',
        'correlation_id',
        'reason',
        'payload_snapshot',
    ];

    protected $casts = [
        'entity_id' => 'integer',
        'actor_id' => 'integer',
        'payload_snapshot' => 'array',
    ];

    // --- Relationships ---

    /**
     * İlgili log kaydını oluşturan kullanıcı (eğer sistem oluşturmamışsa)
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
