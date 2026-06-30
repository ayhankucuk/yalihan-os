<?php

namespace App\Models;

use App\Models\SaaS\Tenant;
use App\Traits\EnforcesContext7Guard;
use App\Traits\HasActiveScope;
use App\Traits\SabGuard;
use App\Modules\Auth\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property int $role_id
 * @property int|null $ulke_id
 * @property bool $aktiflik_durumu
 * @property int $display_order
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property string|null $profile_photo_path
 * @property string|null $telefon
 * @property string|null $baslik
 * @property string|null $bio
 * @property string|null $ofis_adres
 * @property string|null $whatsapp_numara
 * @property string|null $instagram_profil
 * @property string|null $linkedin_profil
 * @property string|null $website
 * @property int $dogrulanmis_mi
 * @property string|null $uzmanlik_alanlari
 * @property string|null $bolge_uzmanliklari
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ilan> $ilanlar
 * @property-read int|null $ilanlar_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Kisi> $musteriler
 * @property-read int|null $musteriler_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int,
 *      \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Role $role
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Talep> $talepler
 * @property-read int|null $talepler_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAktiflikDurumu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBolgeUzmanliklari($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUzmanlikAlanlari($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereInstagramProfil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDogrulanmisMi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastActivityAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastLoginAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLinkedinProfile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereOfisAdres($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTelefon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProfilePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBaslik($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereWhatsappNumara($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use \App\Models\Traits\EncryptsAttributes, HasApiTokens, HasFactory, Notifiable,
        SoftDeletes, HasActiveScope, HasRoles, SabGuard, EnforcesContext7Guard;

    protected $guard_name = 'web';

    /**
     * Context7 Mühürlü $fillable
     * Sadece veritabanındaki kolonlar
     * @sealed 2025-12-31
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role_id',
        'ulke_id',
        'is_active',
        'last_activity_at',
        'telegram_chat_id',
        'baslik',
        'telefon',
        'bio',
        'ofis_adres',
        'whatsapp_numara',
        'instagram_profil',
        'linkedin_profil',
        'website',
        'profile_photo_path',
    ];

    /**
     * Tenant belonging to this user.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Context7 Mühürlü $casts
     * @sealed 2025-12-31
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_activity_at' => 'datetime',
        'ulke_id' => 'integer',
        'telegram_paired_at' => 'datetime',
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'is_verified' => 'boolean',
        'uzmanlik_alanlari' => 'array',
        'bolge_uzmanliklari' => 'array',
        'diller' => 'array',
        'calisma_saatleri' => 'array',
        'iletisim_tercihleri' => 'array',
        'deneyim_yili' => 'integer',
    ];

    protected $encrypted = [
        'tc_kimlik',
        'iban',
    ];

    /**
     * Context7: Direct column access - NO OBFUSCATION
     * Database column: hesap_aktiflik_durumu (user account activity state)
     */
    public function getIsActiveAttribute(): bool
    {
        return (bool) $this->aktiflik_durumu;
    }

    public function getProfilePhotoUrlAttribute()
    {
        return $this->profile_photo_path
            ? \Illuminate\Support\Facades\Storage::url($this->profile_photo_path)
            : null;
    }

    /**
     * Kullanıcının rolüne erişim için ilişki
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Not: danisman() relationship kaldırıldı.
     * Artık users tablosunda Spatie Permission role sistemi kullanılıyor.
     * Danışman kontrolü için: $user->hasRole('danisman') kullanın.
     */

    /**
     * Kullanıcının ilanlarına erişim için ilişki (danışman ise)
     */
    public function ilanlar()
    {
        return $this->hasMany(Ilan::class, 'danisman_id');
    }

    /**
     * Kullanıcının sorumlu olduğu müşterilere erişim için ilişki (danışman ise)
     */
    public function musteriler()
    {
        return $this->hasMany(Kisi::class, 'danisman_id');
    }

    /**
     * Kullanıcının sorumlu olduğu taleplere erişim için ilişki (danışman ise)
     */
    public function talepler()
    {
        return $this->hasMany(Talep::class, 'danisman_id');
    }

    /**
     * Danışman yorumları (tüm durum)
     */
    public function danismanYorumlari()
    {
        return $this->hasMany(\App\Models\DanismanYorum::class, 'danisman_id');
    }

    /**
     * Onaylanmış danışman yorumları
     */
    public function onayliDanismanYorumlari()
    {
        return $this->hasMany(\App\Models\DanismanYorum::class, 'danisman_id')
                    ->where('onay_durumu', 'approved');
    }

    /**
     * User'in sahip olduğu rol(ler) - RENAMED to avoid conflict with Spatie HasRoles
     * role_id alanını kullanarak Role modeliyle HasOne relationship
     */
    public function legacyRoles()
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }

    /**
     * Kullanıcının belirli bir role sahip olup olmadığını kontrol eder
     * Hem Spatie Permission (model_has_roles) hem legacy role_id FK destekler.
     *
     * @param  string|array|\Spatie\Permission\Contracts\Role  $roles
     * @param  string|null  $guard
     * @return bool
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        // Normalize: string → array
        $roleNames = is_array($roles) ? $roles : [$roles];

        // 1. Spatie Permission: model_has_roles tablosundan yüklenmiş roller
        $spatieRoleNames = $this->getRoleNames()->toArray(); // Collection → array of strings
        foreach ($roleNames as $role) {
            if (is_string($role) && in_array($role, $spatieRoleNames)) {
                return true;
            }
            // Spatie Role model instance
            if ($role instanceof \Spatie\Permission\Contracts\Role) {
                if (in_array($role->name, $spatieRoleNames)) {
                    return true;
                }
            }
        }

        // 2. Legacy: tekil role ilişkisini kontrol et (role_id FK → roles tablosu)
        if ($this->role) {
            $legacyRoleName = $this->role->name;
            foreach ($roleNames as $role) {
                $checkName = is_string($role) ? $role : ($role->name ?? null);
                if ($checkName && $legacyRoleName === $checkName) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Kullanıcının süper admin olup olmadığını kontrol eder
     */
    public function isSuperAdmin(): bool
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
     * Kullanıcının içerik editörü olup olmadığını kontrol eder
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

    /**
     * Kullanıcının admin olup olmadığını kontrol eder
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Aktif scope - Handled by HasActiveScope
     */

    /**
     * Note: Handled by HasActiveScope
     */

    /**
     * Son giriş zamanını günceller
     */
    public function updateLastLogin()
    {
        $this->last_login_at = now();
        $this->save();

        return $this;
    }

    /**
     * Son aktivite zamanını günceller
     */
    public function updateLastActivity()
    {
        $this->last_activity_at = now();
        $this->saveQuietly(); // Sessiz kayıt - updated_at değişmez

        return $this;
    }

    /**
     * Kullanıcının belirli bir süre içinde çevrimiçi olup olmadığını kontrol eder
     *
     * @param  int  $minutes  Son kaç dakika içinde aktif olduğu kontrol edilecek
     * @return bool
     */
    public function isActiveWithin($minutes = 5)
    {
        if (! $this->last_activity_at) {
            return false;
        }

        return $this->last_activity_at->diffInMinutes(now()) <= $minutes;
    }

    /**
     * Kullanıcının şu anda çevrimiçi olup olmadığını kontrol eder
     *
     * @return bool
     */
    public function isOnline()
    {
        return Cache::has('user-online-'.$this->id);
    }

    /**
     * Kullanıcının rolüne göre dashboard URL'ini döndürür
     *
     * @return string
     */
    public function getDashboardUrl()
    {
        if ($this->userHasRole(['superadmin', 'admin'])) {
            return route('admin.dashboard.index');
        }

        return route('home');
    }

    /**
     * Kullanıcının belirli bir role sahip olup olmadığını kontrol eder
     *
     * @param  string|array  $roleName
     * @return bool
     */
    public function userHasRole($roleName)
    {
        if (! $this->role) {
            return false;
        }

        if (is_array($roleName)) {
            return in_array($this->role->name, $roleName);
        }

        return $this->role->name === $roleName;
    }

    /**
     * Kullanıcının bildirimleri
     */
    public function notifications()
    {
        return $this->morphMany(\App\Models\Notification::class, 'notifiable')->latest();
    }

    /**
     * Kullanıcının okunmamış bildirimleri
     */
    public function unreadNotifications()
    {
        return $this->notifications()->unread();
    }

    /**
     * Kullanıcının okunmuş bildirimleri
     */
    public function readNotifications()
    {
        return $this->notifications()->read();
    }

    /**
     * Kullanıcının gönderdiği bildirimler
     */
    public function sentNotifications()
    {
        return $this->hasMany(\App\Models\Notification::class, 'sender_id');
    }



    /**
     * Kullanıcının takım üyesi kaydına erişim için ilişki
     */
    public function takimUyesi()
    {
        return $this->hasOne(\App\Modules\TakimYonetimi\Models\TakimUyesi::class, 'user_id');
    }

    /**
     * Kullanıcının favori ilanları
     */
    public function favoriIlanlar()
    {
        return $this->belongsToMany(Ilan::class, 'ilan_favorileri', 'user_id', 'ilan_id')
            ->withTimestamps();
    }

    /**
     * Kullanıcının kayıtlı aramaları
     */
    public function savedSearches()
    {
        return $this->hasMany(\App\Models\SavedSearch::class);
    }
}
