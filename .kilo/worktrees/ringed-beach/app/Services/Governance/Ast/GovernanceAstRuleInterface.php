<?php

namespace App\Services\Governance\Ast;

use PhpParser\Node;

interface GovernanceAstRuleInterface
{
    /**
     * The unique identifier for this rule.
     */
    public function getRuleId(): string;

    /**
     * The severity of the rule (LOW, MEDIUM, HIGH, CRITICAL).
     */
    public function getSeverity(): string;

    /**
     * Whether this rule is active. Disabled rules are skipped by the registry.
     */
    public function isEnabled(): bool;

    /**
     * Path fragments to exclude from scanning (e.g. 'database/seeders', 'tests/').
     * AstScannerService will skip files whose path contains any of these strings.
     *
     * @return string[]
     */
    public function getExcludedPaths(): array;

    /**
     * Analyze a node. If a violation is found, return an array describing it.
     * Return null if no violation.
     *
     * @return array{message: string}|null
     */
    public function analyze(Node $node): ?array;
}

