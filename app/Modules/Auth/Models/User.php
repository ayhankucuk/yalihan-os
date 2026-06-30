<?php

namespace App\Modules\Auth\Models;

use App\Models\ExpertiseArea;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable; // SoftDeletes trait'ini import ediyoruz
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * App\Modules\Auth\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property int $role_id
 * @property bool $aktiflik_durumu
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon|null $last_login_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \App\Modules\Auth\Models\Role $role
 *
 * @method bool isAdmin() Kullanıcının Admin veya SuperAdmin rolüne sahip olup olmadığını kontrol eder
 * @method bool isSuperAdmin() Kullanıcının SuperAdmin rolüne sahip olup olmadığını kontrol eder
 * @method bool isDanisman() Kullanıcının Danışman rolüne sahip olup olmadığını kontrol eder
 * @method bool hasRole(string $roleName) Kullanıcının belirli bir role sahip olup olmadığını kontrol eder
 *
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property string|null $profile_photo_path
 * @property string|null $phone_number
 * @property string|null $title
 * @property string|null $bio
 * @property string|null $office_address
 * @property string|null $whatsapp_number
 * @property string|null $instagram_profile
 * @property string|null $linkedin_profile
 * @property string|null $website
 * @property bool $is_verified
 * @property string|null $expertise_summary
 * @property string|null $certificates_info
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Role> $roles
 * @property-read int|null $roles_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCertificatesInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereExpertiseSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereInstagramProfile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastActivityAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastLoginAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLinkedinProfile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereOfficeAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProfilePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereWhatsappNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Context7 Mühürlü $fillable
     * Sadece veritabanındaki kolonlar
     * @sealed 2025-12-31
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        // Context7: canonical active flag
        'last_activity_at',
        'telegram_chat_id',
        
    ];

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
        'telegram_paired_at' => 'datetime',
        'aktiflik_durumu' => 'boolean',
        'is_verified' => 'boolean',
        'uzmanlik_alanlari' => 'array',
        'bolge_uzmanliklari' => 'array',
        'diller' => 'array',
        'calisma_saatleri' => 'array',
        'iletisim_tercihleri' => 'array',
        'deneyim_yili' => 'integer',
    ];

    /**
     * Kullanıcının rolünü al
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Kullanıcının uzmanlık alanlarını döndüren ilişki
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function expertiseAreas()
    {
        return $this->belongsToMany(ExpertiseArea::class, 'user_expertise_area')
            ->withPivot('experience_years', 'notes')
            ->withTimestamps();
    }

    /**
     * Geriye uyumluluk için role_id'ye göre rol kontrolü
     *
     * @param  string  $roleName
     * @return bool
     */
    public function hasRoleById($roleName)
    {
        return $this->role && $this->role->name === $roleName;
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
     * Kullanıcının izinlerini döndüren ilişki
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->morphToMany(
            \Spatie\Permission\Models\Permission::class,
            'model',
            'model_has_permissions',
            'model_id',
            'permission_id'
        );
    }

    /**
     * Kullanıcının admin olup olmadığını kontrol et
     *
     * @return bool
     */
    public function isAdmin()
    {
        return in_array($this->role_id, [1, 2]); // 1: SuperAdmin, 2: Admin
    }

    /**
     * Kullanıcının danışman olup olmadığını kontrol et
     *
     * @return bool
     */
    public function isDanisman()
    {
        return $this->role && $this->role->name === 'danisman';
    }

    /**
     * Kullanıcının süper admin olup olmadığını kontrol eder
     * Bu metot isAdmin() ile aynı işlevi görüyor, tutarlılık için korundu.
     *
     * @return bool
     */
    public function isSuperAdmin()
    {
        return $this->role && $this->role->name === 'superadmin';
    }

    /**
     * Kullanıcının belirli bir role sahip olup olmadığını kontrol eder
     * Öncelikle tekil role ilişkisini kontrol eder
     *
     * @param  string|array  $roles  Kontrol edilecek rol veya roller
     * @return bool
     */
    public function hasRole($roles)
    {
        // Önce tekil role ilişkisini kontrol edelim
        if ($this->role) {
            if (is_array($roles)) {
                if (in_array($this->role->name, $roles)) {
                    return true;
                }
            } elseif ($this->role->name === $roles) {
                return true;
            }
        }

        // Eğer tekil role ilişkisinde bulunamadıysa ve roles ilişkisi varsa onu kontrol edelim
        if (method_exists($this, 'roles') && $this->roles && $this->roles->count() > 0) {
            if (is_array($roles)) {
                foreach ($roles as $role) {
                    if ($this->roles->contains('name', $role)) {
                        return true;
                    }
                }

                return false;
            }

            return $this->roles->contains('name', $roles);
        }

        return false;
    }

    /**
     * Kullanıcının editör rolüne sahip olup olmadığını kontrol eder
     *
     * @return bool
     */
    public function isEditor()
    {
        return $this->hasRole('editor');
    }

    /**
     * Son giriş zamanını güncelle
     *
     * @return void
     */
    public function updateLastLogin()
    {
        $this->last_login_at = now();
        $this->save();
    }

    /**
     * Son aktivite zamanını güncelle
     *
     * @return void
     */
    public function updateLastActivity()
    {
        $this->last_activity_at = now();
        $this->save();
    }
}
