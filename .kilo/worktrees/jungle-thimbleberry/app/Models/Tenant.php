<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use App\Models\BaseModel;
use Illuminate\Support\Str;

/**
 * 🏢 TENANT MODEL
 * Represents a business entity in the multi-tenant SaaS environment.
 */
class Tenant extends BaseModel
{
    use HasCountryScope;
    
    protected static function booted(): void
    {
        static::creating(function ($tenant) {
            if (empty($tenant->uuid)) {
                $tenant->uuid = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'uuid',
        'name',
        'domain',
        'durum',
    ];

    protected $casts = [
        'settings' => 'array'
    ];

    /**
     * Users belonging to this tenant.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Context7 Proxy: durum -> status
     */
    public function getStatusAttribute(): string
    {
        return $this->durum;
    }

    public function setStatusAttribute(string $value): void
    {
        $this->attributes['durum'] = $value;
    }
}
