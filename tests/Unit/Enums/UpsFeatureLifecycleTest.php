<?php

namespace Tests\Unit\Enums;

use App\Enums\UpsFeatureLifecycle;
use PHPUnit\Framework\TestCase;

class UpsFeatureLifecycleTest extends TestCase
{
    public function test_all_lifecycle_values_are_valid(): void
    {
        $values = UpsFeatureLifecycle::values();

        $this->assertContains('draft', $values);
        $this->assertContains('active', $values);
        $this->assertContains('stable', $values);
        $this->assertContains('deprecated', $values);
        $this->assertContains('archived', $values);
    }

    public function test_stable_lifecycle_properties(): void
    {
        $stable = UpsFeatureLifecycle::from('stable');

        $this->assertSame(UpsFeatureLifecycle::STABLE, $stable);
        $this->assertTrue($stable->isAssignable());
        $this->assertSame('green', $stable->badgeColor());
        $this->assertTrue($stable->canTransitionTo(UpsFeatureLifecycle::DEPRECATED));
        $this->assertTrue($stable->canTransitionTo(UpsFeatureLifecycle::ARCHIVED));
    }
}
