<?php

namespace App\Services\Ydl\Reservation;

/**
 * FakeConflictOverrideService — Test double for ConflictOverrideService.
 *
 * PILOT-002 Wave 3 — For testing without User/is_admin dependency.
 *
 * Records all canOverride() calls and returns configurable values.
 * Use via DI: new YdlReservationOrchestrator($rs, $el, new FakeConflictOverrideService()).
 */
class FakeConflictOverrideService extends ConflictOverrideService
{
    /** Override return value for all calls (null = use real service). */
    public static ?bool $shouldOverride = null;

    /** @var list<array{userId:int,propertyId:int,ydlAuthority:string,conflictReservationId:int}> */
    public static array $calls = [];

    public function canOverride(
        int    $userId,
        int    $propertyId,
        string $ydlAuthority,
        int    $conflictReservationId,
    ): bool {
        self::$calls[] = [
            'userId'               => $userId,
            'propertyId'           => $propertyId,
            'ydlAuthority'         => $ydlAuthority,
            'conflictReservationId' => $conflictReservationId,
        ];

        if (self::$shouldOverride !== null) {
            return self::$shouldOverride;
        }

        // Safe fallback for tests: default to false (unauthorized) to avoid DB dependency.
        // Tests that need authorized behavior MUST set $shouldOverride = true explicitly.
        return false;
    }

    public static function reset(): void
    {
        self::$shouldOverride = null;
        self::$calls = [];
    }

    public static function assertCanOverrideCalled(
        int $expectedUserId,
        int $expectedPropertyId,
    ): void {
        $found = false;
        foreach (self::$calls as $call) {
            if ($call['userId'] === $expectedUserId && $call['propertyId'] === $expectedPropertyId) {
                $found = true;
                break;
            }
        }
        if (! $found) {
            throw new \AssertionError(
                "canOverride(userId={$expectedUserId}, propertyId={$expectedPropertyId}) was not called."
            );
        }
    }

    public static function lastCall(): ?array
    {
        return self::$calls[array_key_last(self::$calls)] ?? null;
    }
}
