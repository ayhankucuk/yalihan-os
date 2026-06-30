<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$config = new PhpCsFixer\Config();

return $config
    ->setRules([
        '@PSR12' => true,
        '@PHP82Migration' => true,

        // Array
        'array_syntax' => ['syntax' => 'short'],
        'no_whitespace_before_comma_in_array' => true,
        'whitespace_after_comma_in_array' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],

        // Braces
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => ['statements' => ['return', 'try', 'throw']],

        // Casing
        'constant_case' => ['case' => 'lower'],
        'lowercase_keywords' => true,
        'lowercase_static_reference' => true,

        // Cast
        'cast_spaces' => ['space' => 'single'],
        'lowercase_cast' => true,

        // Class
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
                'trait_import' => 'none',
            ],
        ],
        'ordered_class_elements' => [
            'or' . 'der' => [
                'use_trait',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],

        // Comments
        'single_line_comment_style' => ['comment_types' => ['hash']],
        'multiline_comment_opening_closing' => true,

        // Control Structure
        'no_alternative_syntax' => ['fix_non_monolithic_code' => false],
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,

        // Function
        'function_typehint_space' => true,
        'no_spaces_after_function_name' => true,

        // Import
        'fully_qualified_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],

        // Operator
        'binary_operator_spaces' => ['default' => 'single_space'],
        'concat_space' => ['spacing' => 'one'],
        'unary_operator_spaces' => true,

        // PHPDoc
        'no_empty_phpdoc' => true,
        'phpdoc_indent' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => true,
        'phpdoc_scalar' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_var_without_name' => true,

        // Return
        'no_useless_return' => true,
        'return_type_declaration' => ['space_before' => 'none'],

        // Semicolon
        'no_singleline_whitespace_before_semicolons' => true,
        'space_after_semicolon' => ['remove_in_empty_for_expressions' => true],

        // String
        'single_quote' => ['strings_containing_single_quote_chars' => false],

        // Whitespace
        'no_extra_blank_lines' => [
            'tokens' => [
                'extra',
                'throw',
                'use',
                'use_trait',
            ],
        ],
        'no_trailing_whitespace' => true,
        'no_whitespace_in_blank_line' => true,
        'single_blank_line_at_eof' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
