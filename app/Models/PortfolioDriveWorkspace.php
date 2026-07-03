<?php

namespace App\Models;

use App\Scopes\TenantScope;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * PortfolioDriveWorkspace Model
 *
 * Sprint 4.4 — Digital Property Lifecycle: DriveWorkspace
 *
 * Stores Google Drive workspace metadata for each portfolio (ilan).
 * Each workspace consists of a root folder + 12 subfolders for organizing
 * property documents: Photos, Videos, Tapu, İmar, Ekspertiz, Airbnb,
 * Sahibinden, Hepsiemlak, CRM, Finance, AI, Archive.
 *
 * @sab-context7-table portfolio_drive_workspaces
 */
class PortfolioDriveWorkspace extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'portfolio_drive_workspaces';

    protected $fillable = [
        'ilan_id',
        'tenant_id',
        'drive_folder_id',
        'drive_folder_url',
        'workspace_status',
        'root_folder_name',
        'portfolio_no',
        'subfolders_json',
    ];

    protected $casts = [
        'ilan_id' => 'integer',
        'tenant_id' => 'integer',
        'subfolders_json' => 'array',
    ];

    // ─── Status Constants ──────────────────────────────────────────────
    public const STATUS_CREATING = 'creating';
    public const STATUS_READY = 'ready';
    public const STATUS_ERROR = 'error';

    // ─── Global Scopes ─────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    /**
     * Scope by ilan (portfolio)
     */
    public function scopeForPortfolio($query, int $ilanId)
    {
        return $query->where('ilan_id', $ilanId);
    }

    /**
     * Scope by workspace status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('workspace_status', $status);
    }

    /**
     * Scope ready workspaces
     */
    public function scopeReady($query)
    {
        return $query->where('workspace_status', self::STATUS_READY);
    }

    /**
     * Scope error workspaces
     */
    public function scopeWithError($query)
    {
        return $query->where('workspace_status', self::STATUS_ERROR);
    }

    // ─── Helpers ───────────────────────────────────────────────────────

    /**
     * Mark workspace as ready
     */
    public function markReady(): self
    {
        $this->update(['workspace_status' => self::STATUS_READY]);
        return $this;
    }

    /**
     * Mark workspace as error
     */
    public function markError(): self
    {
        $this->update(['workspace_status' => self::STATUS_ERROR]);
        return $this;
    }

    /**
     * Check if workspace is ready
     */
    public function isReady(): bool
    {
        return $this->workspace_status === self::STATUS_READY;
    }

    /**
     * Check if workspace creation failed
     */
    public function hasError(): bool
    {
        return $this->workspace_status === self::STATUS_ERROR;
    }

    /**
     * Get subfolder by name
     */
    public function getSubfolderId(string $name): ?string
    {
        $subfolders = $this->subfolders_json ?? [];
        foreach ($subfolders as $folder) {
            if (($folder['name'] ?? '') === $name) {
                return $folder['id'] ?? null;
            }
        }
        return null;
    }

    /**
     * Get all subfolder IDs as array
     *
     * @return array<string, string>
     */
    public function getSubfolderMap(): array
    {
        $map = [];
        $subfolders = $this->subfolders_json ?? [];
        foreach ($subfolders as $folder) {
            if (isset($folder['name'], $folder['id'])) {
                $map[$folder['name']] = $folder['id'];
            }
        }
        return $map;
    }

    /**
     * Get subfolder count
     */
    public function getSubfolderCount(): int
    {
        return count($this->subfolders_json ?? []);
    }
}
