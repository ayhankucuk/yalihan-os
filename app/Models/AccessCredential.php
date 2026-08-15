<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * AccessCredential — Encrypted property access credential model.
 *
 * CHECKIN_CHECKOUT Wave 2
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $ilan_id
 * @property string $credential_type   'key' | 'code' | 'lockbox' | 'smart_lock'
 * @property string $credential_value  Encrypted access code / key reference
 * @property string|null $credential_location  Encrypted location hint
 * @property bool $is_active
 * @property bool $requires_reset
 * @property \Carbon\Carbon|null $last_reset_at
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * INV-W2-S2: credential_value stored encrypted via Crypt::encryptString
 * INV-W2-S1: credential_value NEVER appears in logs — use getMaskedValue() / getMaskedLocation()
 */
class AccessCredential extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'access_credentials';

    /**
     * Columns that are encrypted at rest and must NEVER appear in logs.
     */
    private const SENSITIVE_FIELDS = ['credential_value', 'credential_location'];

    protected $fillable = [
        'tenant_id',
        'ilan_id',
        'credential_type',
        'credential_value',
        'credential_location',
        'is_active',
        'requires_reset',
        'last_reset_at',
        'expires_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'ilan_id' => 'integer',
        'is_active' => 'boolean',
        'requires_reset' => 'boolean',
        'last_reset_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Relations: parent Ilan.
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    // ─── Encryption ─────────────────────────────────────────────────────────

    /**
     * Encrypt and store credential_value.
     * Call this instead of setting credential_value directly.
     */
    public function setCredentialValue(string $plainValue): void
    {
        $this->credential_value = Crypt::encryptString($plainValue);
    }

    /**
     * Decrypt and return credential_value.
     * Returns null if the stored value cannot be decrypted.
     */
    public function getCredentialValue(): ?string
    {
        if (empty($this->credential_value)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->credential_value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            Log::error('AccessCredential: decrypt failed — value may be corrupted or from wrong key', [
                'credential_id' => $this->id,
                'ilan_id' => $this->ilan_id,
                // Intentionally NO credential_value in log
                'masked_value' => $this->getMaskedValue(),
            ]);
            return null;
        }
    }

    /**
     * Encrypt and store credential_location.
     */
    public function setCredentialLocation(string $location): void
    {
        $this->credential_location = Crypt::encryptString($location);
    }

    /**
     * Decrypt and return credential_location.
     */
    public function getCredentialLocation(): ?string
    {
        if (empty($this->credential_location)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->credential_location);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    // ─── Safety helpers ─────────────────────────────────────────────────────

    /**
     * Returns a safe string for logging that never exposes the credential.
     * Format: "xxxx-xxxx-XXXX" (last 4 chars visible for debugging)
     */
    public function getMaskedValue(): string
    {
        if (empty($this->credential_value)) {
            return '[empty]';
        }
        // Show last 4 chars of the encrypted string for debugging uniqueness
        $last4 = substr($this->credential_value, -4);
        return "xxxx-{$last4}";
    }

    /**
     * Returns a safe string for logging credential_location.
     */
    public function getMaskedLocation(): string
    {
        if (empty($this->credential_location)) {
            return '[empty]';
        }
        return '***[encrypted-location]***';
    }

    /**
     * Check if credential is expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }
        return $this->expires_at->isPast();
    }

    /**
     * Check if credential is valid for use (active, not expired, no reset needed).
     */
    public function isValid(): bool
    {
        return $this->is_active
            && !$this->isExpired()
            && !$this->requires_reset;
    }

    // ─── Logging safety ────────────────────────────────────────────────────

    /**
     * Override toArray to exclude sensitive fields.
     * Uses masked versions instead.
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['credential_value'] = $this->getMaskedValue();
        $array['credential_location'] = $this->getMaskedLocation();
        return $array;
    }
}
