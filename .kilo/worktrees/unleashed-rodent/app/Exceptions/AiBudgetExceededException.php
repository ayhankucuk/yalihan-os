<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * ��️ SAB SEALED
 * AI Budget Exceeded Exception - Hard cap enforcement
 */
class AiBudgetExceededException extends Exception
{
    protected string $featureKey;
    protected int $used;
    protected int $hardCap;
    protected int $graceCap;
    protected string $resetTime;

    public function __construct(
        string $featureKey,
        int $used,
        int $hardCap,
        int $graceCap,
        string $resetTime
    ) {
        $this->featureKey = $featureKey;
        $this->used = $used;
        $this->hardCap = $hardCap;
        $this->graceCap = $graceCap;
        $this->resetTime = $resetTime;

        $message = sprintf(
            "Günlük AI kullanım limitiniz doldu (Feature: %s). Kullanılan: %s token, Limit: %s token. Sıfırlanma: %s",
            $this->featureKey,
            number_format($this->used),
            number_format($this->hardCap),
            $this->resetTime
        );

        parent::__construct($message, 429);
    }

    /**
     * Render exception as JSON response
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'AI_BUDGET_EXCEEDED',
            'message' => $this->getMessage(),
            'details' => [
                'feature' => $this->featureKey,
                'used' => $this->used,
                'hard_cap' => $this->hardCap,
                'grace_cap' => $this->graceCap,
                'reset_time' => $this->resetTime,
                'remaining' => max(0, $this->hardCap - $this->used),
            ],
        ], 429);
    }

    public function getFeatureKey(): string
    {
        return $this->featureKey;
    }

    public function getUsed(): int
    {
        return $this->used;
    }

    public function getHardCap(): int
    {
        return $this->hardCap;
    }

    public function getGraceCap(): int
    {
        return $this->graceCap;
    }

    public function getResetTime(): string
    {
        return $this->resetTime;
    }
}
