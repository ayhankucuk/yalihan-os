<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\PropertyHub;

use App\Domain\PropertyHub\Resolution\DTOs\ResolutionContext;
use App\Domain\PropertyHub\Resolution\Engine\TemplateResolutionEngine;
use App\Domain\PropertyHub\Rules\Evaluators\ConditionEvaluator;
use App\Domain\PropertyHub\Rules\Registry\DatabaseRuleRegistry;
use App\Models\PropertyConfigVersion;
use App\Models\RuleDefinition;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/**
 * Critical Unit Tests for V3 Template Resolution Engine
 *
 * These 3 tests MUST pass for Sprint 2 to be considered complete.
 * @group skip-until-migration-complete
 */
class TemplateResolutionEngineTest extends TestCase
{

    private TemplateResolutionEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = new DatabaseRuleRegistry();
        $evaluator = new ConditionEvaluator();
        $this->engine = new TemplateResolutionEngine($registry, $evaluator);
    }

    /**
     * Test 1: Global Rule Application
     *
     * Given: 1 global rule (no conditions)
     * When: resolve()
     * Then: template_id = X
     */
    public function test_global_rule_application(): void
    {
        // Arrange: Create ACTIVE version
        $version = PropertyConfigVersion::create([
            'version_hash' => hash('sha256', 'test_v1'),
            'governance_state' => 'ACTIVE',
            'description' => 'Test Version 1',
        ]);

        // Create a global rule (no conditions)
        RuleDefinition::create([
            'name' => 'Global Default Template',
            'rule_type' => 'TEMPLATE_ASSIGNMENT',
            'rule_config' => [
                'conditions' => [], // Global = no conditions
                'actions' => [
                    'assign_template' => 100,
                ],
            ],
            'priority' => 100,
            'version_id' => $version->id,
            'aktif' => true,
        ]);

        // Act: Resolve
        $context = ResolutionContext::create(
            categoryId: 1,
            publicationTypeId: 1
        );

        $result = $this->engine->resolve($context);

        // Assert
        $this->assertEquals(100, $result->templateId);
        $this->assertTrue($result->isValid());
    }

    /**
     * Test 2: Category Override
     *
     * Given: global rule + category-specific rule
     * When: resolve(category=3)
     * Then: category rule wins
     */
    public function test_category_override(): void
    {
        // Arrange
        $version = PropertyConfigVersion::create([
            'version_hash' => hash('sha256', 'test_v2'),
            'governance_state' => 'ACTIVE',
            'description' => 'Test Version 2',
        ]);

        // Global rule (priority 100)
        RuleDefinition::create([
            'name' => 'Global Default',
            'rule_type' => 'TEMPLATE_ASSIGNMENT',
            'rule_config' => [
                'conditions' => [],
                'actions' => ['assign_template' => 100],
            ],
            'priority' => 100,
            'version_id' => $version->id,
            'aktif' => true,
        ]);

        // Category-specific rule (priority 50 = higher priority)
        RuleDefinition::create([
            'name' => 'Category 3 Override',
            'rule_type' => 'TEMPLATE_ASSIGNMENT',
            'rule_config' => [
                'conditions' => [
                    'all' => [
                        ['field' => 'category_id', 'operator' => '=', 'value' => 3],
                    ],
                ],
                'actions' => ['assign_template' => 200],
            ],
            'priority' => 50, // Higher priority
            'version_id' => $version->id,
            'aktif' => true,
        ]);

        // Act: Resolve for category 3
        $context = ResolutionContext::create(
            categoryId: 3,
            publicationTypeId: 1
        );

        $result = $this->engine->resolve($context);

        // Assert: Category rule should win
        $this->assertEquals(200, $result->templateId);
        $this->assertTrue($result->isValid());
    }

    /**
     * Test 3: Priority Conflict Resolution
     *
     * Given: 2 matching rules with different priorities
     * When: resolve()
     * Then: highest priority (lowest number) wins
     */
    public function test_priority_conflict_resolution(): void
    {
        // Arrange
        $version = PropertyConfigVersion::create([
            'version_hash' => hash('sha256', 'test_v3'),
            'governance_state' => 'ACTIVE',
            'description' => 'Test Version 3',
        ]);

        // Rule 1: Priority 100 (lower priority)
        RuleDefinition::create([
            'name' => 'Low Priority Rule',
            'rule_type' => 'TEMPLATE_ASSIGNMENT',
            'rule_config' => [
                'conditions' => [
                    'all' => [
                        ['field' => 'category_id', 'operator' => '=', 'value' => 5],
                    ],
                ],
                'actions' => ['assign_template' => 300],
            ],
            'priority' => 100,
            'version_id' => $version->id,
            'aktif' => true,
        ]);

        // Rule 2: Priority 10 (higher priority)
        RuleDefinition::create([
            'name' => 'High Priority Rule',
            'rule_type' => 'TEMPLATE_ASSIGNMENT',
            'rule_config' => [
                'conditions' => [
                    'all' => [
                        ['field' => 'category_id', 'operator' => '=', 'value' => 5],
                    ],
                ],
                'actions' => ['assign_template' => 400],
            ],
            'priority' => 10, // Higher priority
            'version_id' => $version->id,
            'aktif' => true,
        ]);

        // Act
        $context = ResolutionContext::create(
            categoryId: 5,
            publicationTypeId: 1
        );

        $result = $this->engine->resolve($context);

        // Assert: Last rule (highest priority) should win
        $this->assertEquals(400, $result->templateId);
        $this->assertTrue($result->isValid());
    }

    /**
     * Determinism Test: 1000 Iterations
     *
     * Same context should produce same signature every time.
     */
    public function test_determinism_verification(): void
    {
        // Arrange
        $version = PropertyConfigVersion::create([
            'version_hash' => hash('sha256', 'test_determinism'),
            'governance_state' => 'ACTIVE',
            'description' => 'Determinism Test',
        ]);

        RuleDefinition::create([
            'name' => 'Determinism Rule',
            'rule_type' => 'TEMPLATE_ASSIGNMENT',
            'rule_config' => [
                'conditions' => [],
                'actions' => ['assign_template' => 999],
            ],
            'priority' => 100,
            'version_id' => $version->id,
            'aktif' => true,
        ]);

        $context = ResolutionContext::create(
            categoryId: 10,
            publicationTypeId: 2
        );

        // Act: Resolve 1000 times
        $signatures = [];
        for ($i = 0; $i < 1000; $i++) {
            $result = $this->engine->resolve($context);
            $signatures[] = $result->signature;
        }

        // Assert: All signatures must be identical
        $uniqueSignatures = array_unique($signatures);
        $this->assertCount(1, $uniqueSignatures, 'Engine is NOT deterministic! Multiple signatures detected.');
    }
}
