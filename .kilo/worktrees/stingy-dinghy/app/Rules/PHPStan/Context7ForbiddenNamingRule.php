<?php

namespace App\Rules\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * 🛡️ Context7 Zero-Regeneration Guard — PHPStan Compile-Time Rule
 *
 * Bu kural, yasaklı alan adlarının (durum hariçi: sta-tus, re-order, or-der)
 * kod tabanına girmesini derleme zamanında engeller.
 *
 * NOT: Bu dosya SAB scanner tarafından taranmaz (meta-exclusion).
 * Nedeni: kendisi yasak kelimelerin referanslarını içerir.
 *
 * @implements Rule<Node>
 */
class Context7ForbiddenNamingRule implements Rule
{
    /**
     * Yasak alan adları
     */
    private static function getForbiddenExact(): array
    {
        return [
            'status',               // Context7: yasak — yerine 'durum', 'aktiflik_durumu'
            'is_active',            // Context7: yasak — yerine 'aktiflik_durumu'
            'is_default',           // Context7: yasak — yerine 'varsayilan_durumu'
            'sort_order',           // Context7: yasak — yerine 'display_order'
            'reorder',              // Context7: yasak — yerine 'sirala'
        ];
    }

    private static function getForbiddenSuffixes(): array
    {
        return [
            '_status',              // Context7: yasak suffix — yerine '_durumu'
            '_order',               // Context7: riskli suffix — yerine '_siralama' veya display_order
        ];
    }

    public function getNodeType(): string
    {
        return Node\Expr\Array_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof Array_) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        if (! $classReflection->isSubclassOf('Illuminate\Database\Eloquent\Model')) {
            return [];
        }

        $errors = [];
        $forbidden = self::getForbiddenExact();
        $suffixes  = self::getForbiddenSuffixes();

        foreach ($node->items as $item) {
            if ($item === null || ! $item->value instanceof String_) {
                continue;
            }

            $fieldName = $item->value->value;

            if (in_array($fieldName, $forbidden, true)) {
                $errors[] = RuleErrorBuilder::message(
                    "Context7 Violation: Forbidden field '{$fieldName}' in \$fillable. " .
                    "Use 'durum', 'aktiflik_durumu', 'display_order', or 'sirala' instead."
                )->identifier('context7.forbiddenField')->build();
                continue;
            }

            foreach ($suffixes as $suffix) {
                if (str_ends_with($fieldName, $suffix) && ! str_starts_with($fieldName, 'display_')) {
                    $errors[] = RuleErrorBuilder::message(
                        "Context7 Violation: Field '{$fieldName}' ends with forbidden suffix '{$suffix}'. " .
                        "Use '_durumu' or 'display_order' pattern instead."
                    )->identifier('context7.forbiddenSuffix')->build();
                    break;
                }
            }
        }

        return $errors;
    }
}
