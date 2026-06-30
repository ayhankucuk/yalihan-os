<?php

namespace Tests\Unit\Governance;

use Tests\TestCase;
use App\Services\Governance\Diff\PayloadDiffCalculator;

class PayloadDiffCalculatorTest extends TestCase
{
    public function test_identical_payload_returns_empty_diff()
    {
        $calculator = new PayloadDiffCalculator();
        $payload = ['a' => 1, 'b' => ['c' => 2]];
        
        $this->assertEmpty($calculator->calculate($payload, $payload));
    }

    public function test_single_field_change()
    {
        $calculator = new PayloadDiffCalculator();
        $old = ['name' => 'John', 'age' => 30];
        $new = ['name' => 'John', 'age' => 31];

        $diff = $calculator->calculate($old, $new);

        $this->assertCount(1, $diff);
        $this->assertArrayHasKey('age', $diff);
        $this->assertEquals('changed', $diff['age']['type']);
        $this->assertEquals(30, $diff['age']['old']);
        $this->assertEquals(31, $diff['age']['new']);
    }

    public function test_added_and_removed_fields()
    {
        $calculator = new PayloadDiffCalculator();
        $old = ['id' => 1, 'title' => 'Old Title'];
        $new = ['id' => 1, 'description' => 'New Description'];

        $diff = $calculator->calculate($old, $new);

        $this->assertArrayHasKey('title', $diff);
        $this->assertEquals('removed', $diff['title']['type']);

        $this->assertArrayHasKey('description', $diff);
        $this->assertEquals('added', $diff['description']['type']);
    }

    public function test_nested_json_diff()
    {
        $calculator = new PayloadDiffCalculator();
        $old = ['settings' => ['notifications' => true, 'theme' => 'light']];
        $new = ['settings' => ['notifications' => false, 'theme' => 'light', 'timezone' => 'UTC']];

        $diff = $calculator->calculate($old, $new);

        $this->assertCount(2, $diff);
        
        $this->assertArrayHasKey('settings.notifications', $diff);
        $this->assertEquals('changed', $diff['settings.notifications']['type']);
        
        $this->assertArrayHasKey('settings.timezone', $diff);
        $this->assertEquals('added', $diff['settings.timezone']['type']);
    }

    public function test_field_order_does_not_affect_identical_check()
    {
        $calculator = new PayloadDiffCalculator();
        $old = ['a' => 1, 'b' => 2];
        $new = ['b' => 2, 'a' => 1];

        // Should be empty since keys are the same
        $this->assertEmpty($calculator->calculate($old, $new));
    }
}
