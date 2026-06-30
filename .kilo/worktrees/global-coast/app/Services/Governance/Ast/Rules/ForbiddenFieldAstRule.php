<?php

namespace App\Services\Governance\Ast\Rules;

use App\Services\Governance\Ast\GovernanceAstRuleInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;

/**
 * ForbiddenFieldAstRule — Wave 2 (Architecture & Standards)
 *
 * Detects usage of forbidden fields (status, order, type, etc.) in the 'app/' directory.
 * Rationale: Standardize naming to Context7 (durum, sira, tip, vb.).
 *
 * This rule replaces the legacy grep-based check with a precise AST implementation.
 */
class ForbiddenFieldAstRule implements GovernanceAstRuleInterface
{
    private array $forbidden = [
        // context7-ignore
        'status' => 'aktiflik_durumu',
        'active' => 'aktiflik_durumu',
        'location_id' => 'il_id',
        'priority' => 'one_cikan',
        'order' => 'sira / display_order',
        // context7-ignore
        'type' => 'tip / tur / kategori',
        'is_active' => 'aktiflik_durumu',
        'is_deleted' => 'silinme_durumu',
    ];

    public function getRuleId(): string
    {
        return 'ForbiddenFieldAST';
    }

    public function getSeverity(): string
    {
        return 'MEDIUM';
    }

    public function getDescription(): string
    {
        return 'Forbidden field name detected. Use Context7 canonical naming from authority.json.';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function getExcludedPaths(): array
    {
        return [
            'vendor/',
            'tests/',
            'database/migrations/',
            'app/Services/AI/CodeReviewService.php',
            'app/Services/Governance/Ast/Rules/ForbiddenFieldAstRule.php',
            // Wizard iç protokol şeması: 'type' => 'field_autofill' gibi DTO/internal array key'leri
            // DB kolonu değil — Context7 naming scope dışındadır.
            'app/Services/Wizard/',
        ];
    }

    public function analyze(Node $node): ?array
    {
        // 🛡️ ENFORCEMENT: Architectural naming rules apply to the entire app/ directory.
        // Previously restricted to app/Support/Governance, now global as per SAB §1.8.
        
        if ($this->shouldIgnore($node)) {
            return null;
        }

        // 1. Property access: $obj->status
        if ($node instanceof PropertyFetch && $node->name instanceof Identifier) {
            $fieldName = $node->name->toString();
            if (isset($this->forbidden[$fieldName])) {
                return $this->createFinding($node, $fieldName);
            }
        }

        // 2. Array keys: ['status' => ...]
        if ($node instanceof ArrayItem && $node->key instanceof String_) {
            $fieldName = $node->key->value;
            if (isset($this->forbidden[$fieldName])) {
                return $this->createFinding($node, $fieldName);
            }
        }

        // 3. Method calls: $obj->status()
        if ($node instanceof MethodCall && $node->name instanceof Identifier) {
            $fieldName = $node->name->toString();
            if (isset($this->forbidden[$fieldName])) {
                // Special whitelist for common Laravel/PHP methods
                if (in_array($fieldName, ['status', 'type'])) {
                    // Check if it's a response object (pseudo-check)
                } else {
                    return $this->createFinding($node, $fieldName);
                }
            }
        }

        return null;
    }

    private function shouldIgnore(Node $node): bool
    {
        $comments = $node->getComments();
        if (empty($comments)) {
            // Check parent comments if node is a property/item
            $parent = $node->getAttribute('parent');
            if ($parent instanceof Node) {
                $comments = $parent->getComments();
            }
        }

        foreach ($comments as $comment) {
            if (str_contains($comment->getText(), 'context7-ignore')) {
                return true;
            }
        }

        return false;
    }

    private function createFinding(Node $node, string $fieldName): array
    {
        $suggestion = $this->forbidden[$fieldName];
        return [
            'line' => $node->getLine(),
            'message' => "Forbidden field '{$fieldName}' detected. Suggested canonical: {$suggestion}.",
            'severity' => $this->getSeverity(),
        ];
    }
}
