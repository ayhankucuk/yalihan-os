<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class GovernanceSuppression extends BaseModel
{
    use HasFactory;

    protected $table = 'governance_suppressions';

    protected $fillable = [
        'rule_key',
        'scope',
        'source',
        'domain',
        'reason',
        'suppressed_by',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeForSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeForDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    // ─── Methods ───────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->aktiflik_durumu && !$this->isExpired();
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function matchesFinding(string $source, string $domain, string $ruleKey): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->scope === 'global') {
            return $this->rule_key === $ruleKey;
        }

        if ($this->scope === 'source') {
            return $this->source === $source && $this->rule_key === $ruleKey;
        }

        if ($this->scope === 'domain') {
            return $this->source === $source
                && $this->domain === $domain
                && $this->rule_key === $ruleKey;
        }

        return false;
    }

    // ─── Relations ─────────────────────────────────────────────

    public function suppressedByUser()
    {
        return $this->belongsTo(User::class, 'suppressed_by');
    }
}
