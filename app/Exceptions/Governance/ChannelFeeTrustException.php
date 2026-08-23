<?php

namespace App\Exceptions\Governance;

/**
 * ChannelFeeTrustException — C4.2 Certification Recovery
 *
 * Thrown when channel fee accrual is attempted but the trust gate
 * conditions are not met (CASE C: OTA + unresolved / unverified fee).
 *
 * Unlike RuntimeException, this exception signals a domain-level
 * trust boundary — not a programming error. The caller must handle
 * this by falling back to C3 flow (no channel fee deduction).
 *
 * SAAB C4.2 Certification Recovery Policy:
 *   CASE A — Direct / zero-fee: do not throw; skip channel fee, use C3 flow
 *   CASE B — OTA + verified: do not throw; full C4.2 triple split
 *   CASE C — OTA + unresolved: throw ChannelFeeTrustException; fall back to C3
 *
 * This exception MUST NOT escape the queue worker. It must be caught
 * and handled within the job to preserve idempotent C3 completion.
 */
class ChannelFeeTrustException extends \RuntimeException
{
    public readonly string $reservationSlug;

    public readonly ?string $channelFeeBearer;

    public readonly ?string $channelFeeSource;

    public readonly string $case; // 'A' | 'B' | 'C'

    public function __construct(
        string $message,
        int $reservationId,
        ?string $channelFeeBearer,
        ?string $channelFeeSource,
        string $case,
    ) {
        parent::__construct($message);
        $this->reservationSlug = "reservation #{$reservationId}";
        $this->channelFeeBearer = $channelFeeBearer;
        $this->channelFeeSource = $channelFeeSource;
        $this->case = $case;
    }
}
