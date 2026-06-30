<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OwnerLoginToken
 *
 * Mülk sahibi portalı için OTP / magic-link token.
 * 15 dakika geçerli, tek kullanımlık.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property string $token_hash
 * @property string $giris_kanali  'email'|'sms'
 * @property \Carbon\Carbon|null $gecerlilik_bitis
 * @property bool $kullanildi
 * @property string|null $kullanilan_ip
 */
class OwnerLoginToken extends Model
{
    protected $table = 'owner_login_tokens';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'token_hash',
        'giris_kanali',
        'gecerlilik_bitis',
        'kullanildi',
        'kullanilan_ip',
    ];

    protected $casts = [
        'gecerlilik_bitis' => 'datetime',
        'kullanildi'       => 'boolean',
    ];

    // -------------------------------------------------------
    // Relations
    // -------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    public function scopeGecerli($query): mixed
    {
        return $query
            ->where('kullanildi', false)
            ->where('gecerlilik_bitis', '>', now());
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /**
     * Token süresi dolmuş mu?
     */
    public function suresiDolduMu(): bool
    {
        return $this->gecerlilik_bitis?->isPast() ?? true;
    }

    /**
     * Token kullanılabilir mi?
     */
    public function kullanilabilirMi(): bool
    {
        return ! $this->kullanildi && ! $this->suresiDolduMu();
    }
}
