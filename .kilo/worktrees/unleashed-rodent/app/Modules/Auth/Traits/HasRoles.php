<?php

namespace App\Modules\Auth\Traits;

use App\Modules\Auth\Models\Role;

trait HasRoles
{
    /**
     * Boot metodu - model kaydedildiğinde çalışır
     */
    public static function bootHasRoles()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->roles()->detach();
        });
    }

    /**
     * Kullanıcının rollerini döndüren ilişki
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * Kullanıcıya rol atar
     *
     * @param  array|string|int|\App\Modules\Auth\Models\Role  ...$roles
     * @return $this
     */
    public function assignRole(...$roles)
    {
        $roles = collect($roles)
            ->flatten()
            ->map(function ($role) {
                if (is_string($role)) {
                    return Role::where('name', $role)->first()->id ?? null;
                }

                if (is_numeric($role)) {
                    return $role;
                }

                return $role->id ?? null;
            })
            ->filter()
            ->toArray();

        $this->roles()->syncWithoutDetaching($roles);

        return $this;
    }

    /**
     * Kullanıcının belirli bir role sahip olup olmadığını kontrol eder
     *
     * @param  string|array  $roles
     * @return bool
     */
    public function hasRole($roles)
    {
        if (is_string($roles)) {
            return $this->roles->contains('name', $roles);
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }

            return false;
        }

        return $roles->intersect($this->roles)->isNotEmpty();
    }

    /**
     * Kullanıcının süper yönetici olup olmadığını kontrol eder
     *
     * @return bool
     */
    public function isSuperAdmin()
    {
        return $this->hasRole('superadmin');
    }

    /**
     * Kullanıcının danışman olup olmadığını kontrol eder
     *
     * @return bool
     */
    public function isDanisman()
    {
        return $this->hasRole('danisman');
    }

    /**
     * Kullanıcının editör olup olmadığını kontrol eder
     *
     * @return bool
     */
    public function isEditor()
    {
        return $this->hasRole('editor');
    }

    /**
     * Kullanıcının belirli rol(ler)den herhangi birine sahip olup olmadığını kontrol eder
     *
     * @param  array|string  $roles
     * @return bool
     */
    public function hasAnyRole($roles)
    {
        return $this->hasRole($roles);
    }

    /**
     * Kullanıcının belirtilen tüm rollere sahip olup olmadığını kontrol eder
     *
     * @param  array|string  $roles
     * @return bool
     */
    public function hasAllRoles($roles)
    {
        if (is_string($roles)) {
            return $this->hasRole($roles);
        }

        $hasAllRoles = true;

        foreach ($roles as $role) {
            if (! $this->hasRole($role)) {
                $hasAllRoles = false;
                break;
            }
        }

        return $hasAllRoles;
    }
}
