<?php

namespace Tests\Unit\Services\Calendar;

use App\Services\Calendar\CancellationPolicyService;
use Carbon\Carbon;
use Tests\TestCase;

class CancellationPolicyServiceTest extends TestCase
{
    public function test_moderate_policy_full_refund_before_14_days()
    {
        $service = new CancellationPolicyService();
        $result = $service->calculateRefund([
            'policy' => 'moderate',
            'check_in' => Carbon::parse('2026-07-10 14:00:00'),
            'cancel_at' => Carbon::parse('2026-06-20 10:00:00'),
            'total_price' => 1000,
            'cleaning_fee' => 100,
            'service_fee' => 50,
            'security_deposit' => 200,
            'paid_amount' => 1000,
        ]);

        $this->assertTrue($result['refund_amount'] > 0);
        $this->assertEquals(1.0, $result['breakdown']['refund_rate']);
    }

    public function test_strict_policy_half_refund_before_30_days()
    {
        $service = new CancellationPolicyService();
        $result = $service->calculateRefund([
            'policy' => 'strict',
            'check_in' => Carbon::parse('2026-08-01 14:00:00'),
            'cancel_at' => Carbon::parse('2026-07-01 10:00:00'),
            'total_price' => 1000,
            'cleaning_fee' => 100,
            'service_fee' => 50,
            'security_deposit' => 0,
            'paid_amount' => 1000,
        ]);

        $this->assertEquals(0.5, $result['breakdown']['refund_rate']);
    }
}
