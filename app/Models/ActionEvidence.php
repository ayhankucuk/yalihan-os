<?php

namespace App\Models;

use App\Modules\TakimYonetimi\Models\Gorev;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ActionEvidence — Completion evidence for Action Center Gorev records.
 *
 * @property int $id
 * @property int $gorev_id
 * @property string $evidence_type  note | photo | system_log | screenshot
 * @property array|null $evidence_data
 * @property int|null $recorded_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ActionEvidence extends Model
{
    protected $table = 'action_evidence';

    protected $fillable = [
        'gorev_id',
        'evidence_type',
        'evidence_data',
        'recorded_by',
    ];

    protected $casts = [
        'evidence_data' => 'array',
        'gorev_id' => 'integer',
        'recorded_by' => 'integer',
    ];

    /**
     * Valid evidence type values.
     */
    public const TYPES = [
        'note',
        'photo',
        'system_log',
        'screenshot',
    ];

    /**
     * Evidence that counts as "completion" for IN_PROGRESS → DONE transition.
     * A Gorev can only be completed if it has at least one evidence entry.
     */
    public const COMPLETION_TYPES = [
        'note',
        'photo',
        'screenshot',
    ];

    // ─────────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────────

    public function gorev(): BelongsTo
    {
        return $this->belongsTo(Gorev::class, 'gorev_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ─────────────────────────────────────────────────────────────────
    // Query scopes
    // ─────────────────────────────────────────────────────────────────

    public function scopeOfType($query, string $type)
    {
        return $query->where('evidence_type', $type);
    }

    public function scopeCompletionEvidence($query)
    {
        return $query->whereIn('evidence_type', self::COMPLETION_TYPES);
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    /**
     * Check if this evidence counts as completion proof.
     */
    public function isCompletionProof(): bool
    {
        return in_array($this->evidence_type, self::COMPLETION_TYPES, true);
    }

    /**
     * Shortcut: create a system_log entry.
     *
     * @param Gorev|int $gorev
     * @param string $actor "system" | user_id
     * @param string $message Human-readable log message
     */
    public static function logSystem(
        Gorev|int $gorev,
        string $actor,
        string $message,
        array $extra = [],
    ): self {
        return static::create([
            'gorev_id' => $gorev instanceof Gorev ? $gorev->id : $gorev,
            'evidence_type' => 'system_log',
            'evidence_data' => [
                'actor' => $actor,
                'message' => $message,
                ...$extra,
            ],
            'recorded_by' => $actor !== 'system' && is_numeric($actor) ? (int) $actor : null,
        ]);
    }

    /**
     * Shortcut: create a note entry.
     *
     * @param Gorev|int $gorev
     * @param string $text
     * @param int|null $authorId
     */
    public static function note(Gorev|int $gorev, string $text, ?int $authorId = null): self
    {
        return static::create([
            'gorev_id' => $gorev instanceof Gorev ? $gorev->id : $gorev,
            'evidence_type' => 'note',
            'evidence_data' => ['text' => $text, 'mentions' => []],
            'recorded_by' => $authorId,
        ]);
    }

    /**
     * Shortcut: create a photo evidence entry.
     *
     * @param Gorev|int $gorev
     * @param string $path Relative storage path
     * @param string $hash SHA256 hash of the file
     * @param string $mime
     * @param int|null $uploaderId
     */
    public static function photo(
        Gorev|int $gorev,
        string $path,
        string $hash,
        string $mime = 'image/jpeg',
        ?int $uploaderId = null,
    ): self {
        return static::create([
            'gorev_id' => $gorev instanceof Gorev ? $gorev->id : $gorev,
            'evidence_type' => 'photo',
            'evidence_data' => [
                'path' => $path,
                'hash' => $hash,
                'mime' => $mime,
            ],
            'recorded_by' => $uploaderId,
        ]);
    }
}
