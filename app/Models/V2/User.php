<?php

namespace App\Models\V2;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;

/**
 * V2 User Model
 *
 * Context7: Canonical field names
 * - Maps V1 schema (name, password, user_state) to Context7 names
 * - Accessors: ad_soyad, sifre_hash, aktiflik_durumu for API compatibility
 * - email, rol (via role_id relationship)
 * - deleted_at: soft delete
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory, HasRoles;

    public $table = 'users';

    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }

    protected $fillable = [
        'name',            // Maps to ad_soyad
        'email',
        'password',        // Maps to sifre_hash
        'role_id',
        'is_active',
        'telegram_chat_id',
    ];

    protected $hidden = [
        'password',        // Never expose password to API
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public $timestamps = true;

    /**
     * Role relationship
     */
    public function role()
    {
        return $this->belongsTo(\App\Modules\Auth\Models\Role::class);
    }

    /**
     * Get the password attribute (for authentication)
     * Laravel expects 'password' attribute for Auth
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Accessor: ad_soyad (maps to 'name' for Context7 compatibility)
     */
    public function getAdSoyadAttribute()
    {
        return $this->name;
    }

    /**
     * Accessor: sifre_hash (maps to 'password' for Context7 compatibility)
     */
    public function getSifreHashAttribute()
    {
        return $this->password;
    }

    /**
     * Accessor: aktiflik_durumu (maps to user state for Context7 compatibility)
     */
    public function getAktiflikDurumuAttribute()
    {
        return (bool) ($this->attributes['is_active'] ?? true);
    }

    /**
     * Accessor: rol (Context7 Canonical Name)
     */
    public function getRolAttribute()
    {
        return match((int)$this->role_id) {
            1 => 'admin',
            2 => 'danisman',
            default => 'musteri',
        };
    }

    /**
     * Mutator: rol (Context7 Canonical Name)
     */
    public function setRolAttribute($value)
    {
        $this->role_id = match($value) {
            'admin' => 1,
            'danisman' => 2,
            default => 3, // musteri
        };
    }
}
