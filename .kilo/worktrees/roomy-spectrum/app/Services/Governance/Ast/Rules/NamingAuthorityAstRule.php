<?php

namespace App\Services\Governance\Ast\Rules;

use App\Services\Governance\Ast\GovernanceAstRuleInterface;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;

class NamingAuthorityAstRule implements GovernanceAstRuleInterface
{
    private const FORBIDDEN_ENGLISH = [
        'is_active' => 'aktiflik_durumu',
        'is_enabled' => 'aktiflik_durumu',
        'is_deleted' => 'silinme_durumu',
        'is_published' => 'yayin_durumu',
        'is_verified' => 'dogrulama_durumu',
        'status' => 'durum',
        'type' => 'tip',
        'category' => 'kategori',
        'description' => 'aciklama',
        'title' => 'baslik',
        'address' => 'adres',
        'phone' => 'telefon',
        'notes' => 'notlar',
    ];

    private const FORBIDDEN_TURKISH_FRAMEWORK = [
        'olusturma_tarihi' => 'created_at',
        'guncelleme_tarihi' => 'updated_at',
        'silme_tarihi' => 'deleted_at',
        'hatirla_token' => 'remember_token',
        'dogrulama_tarihi' => 'email_verified_at',
    ];

    public function getRuleId(): string
    {
        return 'NamingAuthorityAST';
    }

    public function getSeverity(): string
    {
        return 'WARNING';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function getExcludedPaths(): array
    {
        return ['vendor', 'tests'];
    }

    public function analyze(Node $node): ?array
    {
        // We look for string literals that represent column names in migrations or $fillable in models
        if ($node instanceof String_) {
            $value = $node->value;

            // 1. Check for Forbidden English in Domain Context
            if (isset(self::FORBIDDEN_ENGLISH[$value])) {
                return [
                    'message' => sprintf(
                        "Naming Authority Violation: Found forbidden English field '%s'. Use Turkish '%s' for domain concepts.",
                        $value,
                        self::FORBIDDEN_ENGLISH[$value]
                    ),
                ];
            }

            // 2. Check for Forbidden Turkish in Framework Context
            if (isset(self::FORBIDDEN_TURKISH_FRAMEWORK[$value])) {
                return [
                    'message' => sprintf(
                        "Naming Authority Violation: Found forbidden Turkish framework field '%s'. Use Laravel convention '%s'.",
                        $value,
                        self::FORBIDDEN_TURKISH_FRAMEWORK[$value]
                    ),
                ];
            }

            // 3. Check for camelCase in DB strings (should be snake_case)
            if (preg_match('/[a-z][A-Z]/', $value)) {
                // Heuristic: only flag if it looks like a column name (lowercase start, no spaces)
                if (preg_match('/^[a-z]+[A-Z][a-zA-Z]*$/', $value)) {
                    return [
                        'message' => sprintf(
                            "Naming Authority Violation: Field '%s' uses camelCase. Database columns must use snake_case.",
                            $value
                        ),
                    ];
                }
            }
        }

        return null;
    }
}
