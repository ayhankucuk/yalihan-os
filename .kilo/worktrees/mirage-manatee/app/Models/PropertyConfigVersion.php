<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * PropertyConfigVersion Model
 *
 * Represents a versioned snapshot of the entire PropertyHub configuration.
 */
class PropertyConfigVersion extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    private static ?string $yonetimDurumuKolonu = null;

    protected $fillable = [
        'tenant_id',
        'version_hash',
        'governance_state',
        'risk_score',
        'description',
        'snapshot_json',
        'signature',
        'is_immutable',
        'is_approved_by_dual_control',
        'created_by',
        'parent_version_hash',
        'applied_at',
    ];

    protected $casts = [
        'governance_state' => 'string',
        'applied_at' => 'datetime',
        'snapshot_json' => 'array',
        'is_immutable' => 'boolean',
        'is_approved_by_dual_control' => 'boolean',
        'risk_score' => 'float',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        $durumKolonu = self::resolveYonetimDurumuKolonu();

        static::updating(function (PropertyConfigVersion $version) use ($durumKolonu) {
            // 🚨 ZERO-TRUST FIREWALL: Block any mutation of ACTIVE version
            if (
                $version->getOriginal($durumKolonu)
                === \App\Modules\GovernanceCore\Core\VersionStateMachine::DURUM_AKTIF
            ) {
                // Allow only transitions FROM ACTIVE (e.g., to ARSIVLENDI) if managed by governed services
                if (!$version->isDirty($durumKolonu)) {
                    throw new \App\Exceptions\PropertyHub\ActiveMutationViolationException(
                        "ZERO-TRUST ERROR: Version {$version->version_hash} "
                        . 'is currently ACTIVE and immutable. '
                        . 'Direct field mutation is forbidden.'
                    );
                }
            }

            // Guard: Global immutability flag
            if ($version->is_immutable && !$version->isDirty('is_immutable')) {
                throw new \DomainException(
                    'CONTEXT7 ERROR: This version is sealed (immutable) and cannot be modified.'
                );
            }

            // Guard: Prevent modification of critical fields once finalized (ONAYLANDI or AKTIF)
            $nonDraftStates = [
                \App\Modules\GovernanceCore\Core\VersionStateMachine::DURUM_ONAYLANDI,
                \App\Modules\GovernanceCore\Core\VersionStateMachine::DURUM_AKTIF,
                \App\Modules\GovernanceCore\Core\VersionStateMachine::DURUM_ARSIVLENDI
            ];

            if (in_array($version->getOriginal($durumKolonu), $nonDraftStates)) {
                $criticalFields = ['version_hash', 'snapshot_json', 'signature', 'parent_version_hash'];

                foreach ($criticalFields as $field) {
                    if ($version->isDirty($field)) {
                        throw new \DomainException(
                            "CONTEXT7 ERROR: Cannot modify critical field '{$field}' of a finalized version."
                        );
                    }
                }
            }
        });

        static::saving(function (PropertyConfigVersion $version) use ($durumKolonu) {
            // Prevent ANY save on ACTIVE version unless it's a state transition
            if ($version->exists &&
                $version->getOriginal($durumKolonu)
                    === \App\Modules\GovernanceCore\Core\VersionStateMachine::DURUM_AKTIF &&
                !$version->isDirty($durumKolonu)
            ) {
                 throw new \App\Exceptions\PropertyHub\ActiveMutationViolationException(
                    "ZERO-TRUST ERROR: Unauthorized save() call on ACTIVE configuration [{$version->version_hash}]."
                );
            }
        });
    }

    /**
     * Relationship: Rules defined in this version.
     */
    public function rules()
    {
        return $this->hasMany(RuleDefinition::class, 'version_id');
    }

    /**
     * Scope: Get the ACTIVE version for a specific tenant.
     */
    public function scopeActiveForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId)->where(self::resolveYonetimDurumuKolonu(), 'AKTIF');
    }

    public function setYonetimDurumuAttribute(string $value): void
    {
        $this->attributes[self::resolveYonetimDurumuKolonu()] = $value;
    }

    public function getYonetimDurumuAttribute(): ?string
    {
        $kolon = self::resolveYonetimDurumuKolonu();
        return $this->attributes[$kolon] ?? null;
    }

    public static function resolveYonetimDurumuKolonu(): string
    {
        return 'governance_state';
    }
}
