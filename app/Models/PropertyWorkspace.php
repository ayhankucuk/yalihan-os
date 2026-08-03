<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\PropertyWorkspace\PropertyWorkspaceAggregate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Class PropertyWorkspace
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Eloquent model for property_workspaces table.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $ilan_id
 * @property string $workspace_uuid
 * @property string|null $intent
 * @property string|null $template_id
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @package App\Models
 */
class PropertyWorkspace extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'property_workspaces';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'property_id',
        'ilan_id',
        'workspace_uuid',
        'intent',
        'template_id',
        'state',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'property_id' => 'integer',
        'workspace_uuid' => 'string',
        'intent' => 'string',
        'template_id' => 'string',
        'state' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PropertyWorkspace $model) {
            if (empty($model->workspace_uuid)) {
                $model->workspace_uuid = (string) Str::uuid();
            }
            if (empty($model->state)) {
                $model->state = PropertyWorkspaceAggregate::STATE_WORKSPACE_CREATED;
            }
        });
    }

    /**
     * Scope: tenant isolation
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $tenantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTenantScope($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: by workspace UUID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $uuid
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUuid($query, string $uuid)
    {
        return $query->where('workspace_uuid', $uuid);
    }

    /**
     * Scope: by state
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $state
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByState($query, string $state)
    {
        return $query->where('state', $state);
    }

    /**
     * Scope: by Property
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $propertyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    /**
     * Relationship: Workspace → Property (canonical ownership)
     */
    public function property(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Property::class, 'property_id');
    }

    /**
     * Convert model to aggregate
     *
     * @return PropertyWorkspaceAggregate
     */
    public function toAggregate(): PropertyWorkspaceAggregate
    {
        return PropertyWorkspaceAggregate::fromModel($this);
    }

    /**
     * Create model from aggregate
     *
     * @param PropertyWorkspaceAggregate $aggregate
     * @return self
     */
    public static function fromAggregate(PropertyWorkspaceAggregate $aggregate): self
    {
        $state = $aggregate->getState();

        return new self([
            'tenant_id' => $state['tenant_id'],
            'property_id' => $state['property_id'],
            'workspace_uuid' => $state['workspace_id'],
            'intent' => $state['intent'],
            'template_id' => $state['template_id'],
            'state' => $state['state'],
        ]);
    }

    /**
     * Check if workspace is in draft state
     *
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->state === PropertyWorkspaceAggregate::STATE_DRAFT;
    }

    /**
     * Check if workspace is ready for review
     *
     * @return bool
     */
    public function isReadyForReview(): bool
    {
        return $this->state === PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW;
    }

    /**
     * Check if workspace is published
     *
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->state === PropertyWorkspaceAggregate::STATE_PUBLISHED;
    }

    /**
     * Check if workspace is archived
     *
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->state === PropertyWorkspaceAggregate::STATE_ARCHIVED;
    }

    /**
     * Alias accessor: ilan_id maps to property_id column
     */
    public function getIlanIdAttribute(): ?int
    {
        return isset($this->attributes['property_id']) ? (int) $this->attributes['property_id'] : null;
    }

    /**
     * Alias mutator: ilan_id maps to property_id column
     */
    public function setIlanIdAttribute(?int $value): void
    {
        $this->attributes['property_id'] = $value;
    }
}
