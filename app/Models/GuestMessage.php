<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * GuestMessage — Immutable audit trail for inbound guest WhatsApp messages.
 *
 * GUEST_CONCIERGE Phase 1 — SAAB Session 134
 *
 * Design Principles:
 * - APPEND-ONLY: No update() or delete() at application level
 * - Idempotency: external_message_id prevents duplicate processing
 * - Tenant isolation: all queries must be tenant-scoped
 * - Audit: every message creates a new row
 *
 * GC-D2: This is a single message row, not a conversation aggregate.
 * A conversation = multiple GuestMessage rows over time.
 *
 * GC-D8: This model NEVER contains access credentials.
 * Door codes, lockbox codes, smart lock codes are FORBIDDEN.
 * WiFi password is permitted (public network, not an access credential).
 *
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $ilan_id
 * @property string $channel
 * @property string $sender_phone
 * @property string|null $sender_name
 * @property string|null $external_message_id
 * @property string $message_text
 * @property string $message_type
 * @property string|null $routing_decision
 * @property int|null $reservation_id
 * @property string|null $intent
 * @property float|null $confidence
 * @property array|null $required_fact_keys
 * @property string|null $response_mode
 * @property string|null $response_text
 * @property int|null $gorev_id
 * @property bool $escalated
 * @property string|null $escalation_reason
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class GuestMessage extends Model
{
    protected $table = 'guest_messages';

    // ── Routing Decision Constants ────────────────────────────────────────

    public const DECISION_GUEST_ACTIVE = 'GUEST_ACTIVE';
    public const DECISION_GUEST_FUTURE = 'GUEST_FUTURE';
    public const DECISION_GUEST_PAST = 'GUEST_PAST';
    public const DECISION_LEAD = 'LEAD';
    public const DECISION_UNKNOWN = 'UNKNOWN';

    // ── Intent Constants ──────────────────────────────────────────────────

    // P1: AUTO ANSWER intents
    public const INTENT_WIFI_INFO = 'WIFI_INFO';
    public const INTENT_CHECK_IN_TIME = 'CHECK_IN_TIME';
    public const INTENT_CHECK_OUT_TIME = 'CHECK_OUT_TIME';
    public const INTENT_PARKING_INFO = 'PARKING_INFO';
    public const INTENT_HOUSE_RULES = 'HOUSE_RULES';

    // P1: AUTO ACTION intents
    public const INTENT_TECHNICAL_ISSUE = 'TECHNICAL_ISSUE';
    public const INTENT_CLEANING_REQUEST = 'CLEANING_REQUEST';

    // ZERO AUTHORITY — must escalate
    public const INTENT_CREDENTIAL_REQUEST = 'CREDENTIAL_REQUEST';
    public const INTENT_REFUND_REQUEST = 'REFUND_REQUEST';
    public const INTENT_DAMAGE_REPORT = 'DAMAGE_REPORT';
    public const INTENT_LEGAL_QUESTION = 'LEGAL_QUESTION';

    // ESCALATE intents
    public const INTENT_EARLY_CHECKIN = 'EARLY_CHECKIN';
    public const INTENT_LATE_CHECKOUT = 'LATE_CHECKOUT';
    public const INTENT_EXTEND_STAY = 'EXTEND_STAY';
    public const INTENT_COMPENSATION_REQUEST = 'COMPENSATION_REQUEST';

    // Fallback
    public const INTENT_UNKNOWN = 'UNKNOWN';

    // ── Response Mode Constants ───────────────────────────────────────────

    public const MODE_ANSWER = 'ANSWER';
    public const MODE_ACTION = 'ACTION';
    public const MODE_ESCALATE = 'ESCALATE';

    // ── Intent Metadata: required facts ─────────────────────────────────

    public const INTENT_REQUIRED_FACTS = [
        self::INTENT_WIFI_INFO => ['wifi_ssid', 'wifi_password'],
        self::INTENT_CHECK_IN_TIME => ['check_in_time'],
        self::INTENT_CHECK_OUT_TIME => ['check_out_time'],
        self::INTENT_PARKING_INFO => ['parking_info'],
        self::INTENT_HOUSE_RULES => ['house_rules'],
        self::INTENT_TECHNICAL_ISSUE => [],
        self::INTENT_CLEANING_REQUEST => [],
        self::INTENT_CREDENTIAL_REQUEST => [],
        self::INTENT_REFUND_REQUEST => [],
        self::INTENT_DAMAGE_REPORT => [],
        self::INTENT_LEGAL_QUESTION => [],
        self::INTENT_EARLY_CHECKIN => [],
        self::INTENT_LATE_CHECKOUT => [],
        self::INTENT_EXTEND_STAY => [],
        self::INTENT_COMPENSATION_REQUEST => [],
        self::INTENT_UNKNOWN => [],
    ];

    // ── Credential Intent Check ─────────────────────────────────────────

    public const CREDENTIAL_INTENTS = [
        self::INTENT_CREDENTIAL_REQUEST,
    ];

    protected $fillable = [
        'tenant_id',
        'ilan_id',
        'channel',
        'sender_phone',
        'sender_name',
        'external_message_id',
        'message_text',
        'message_type',
        'routing_decision',
        'reservation_id',
        'intent',
        'confidence',
        'required_fact_keys',
        'response_mode',
        'response_text',
        'gorev_id',
        'escalated',
        'escalation_reason',
    ];

    protected $casts = [
        'required_fact_keys' => 'array',
        'confidence' => 'float',
        'escalated' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'channel' => 'whatsapp',
        'message_type' => 'text',
        'escalated' => false,
    ];

    // ── Relations ────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(PropertyReservation::class);
    }

    public function gorev(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\TakimYonetimi\Models\Gorev::class);
    }

    // ── Query Scopes ────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByPhone($query, string $phone)
    {
        return $query->where('sender_phone', $phone);
    }

    public function scopeForReservation($query, int $reservationId)
    {
        return $query->where('reservation_id', $reservationId);
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeEscalated($query)
    {
        return $query->where('escalated', true);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /**
     * Check if this message is a credential request.
     * GC-D8: Critical security boundary.
     */
    public function isCredentialRequest(): bool
    {
        return in_array($this->intent, self::CREDENTIAL_INTENTS, true);
    }

    /**
     * Get required fact keys for this message's intent.
     */
    public function getRequiredFactKeys(): array
    {
        return self::INTENT_REQUIRED_FACTS[$this->intent] ?? [];
    }

    /**
     * Check if this message was escalated.
     */
    public function wasEscalated(): bool
    {
        return $this->escalated === true;
    }

    /**
     * Check if this message created an action (Gorev).
     */
    public function createdAction(): bool
    {
        return $this->response_mode === self::MODE_ACTION && $this->gorev_id !== null;
    }
}
