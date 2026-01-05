<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$config = new PhpCsFixer\Config();
$config
    ->setUnsupportedPhpVersionAllowed(true)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setRules([
        // Base ruleset - PSR-12 compliance
        '@PSR12' => true,

        // Strict types declaration
        'declare_strict_types' => true,

        // Array syntax
        'array_syntax' => ['syntax' => 'short'],

        // Import statements
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],

        // Spacing
        'concat_space' => ['spacing' => 'one'],
        'binary_operator_spaces' => ['default' => 'single_space'],

        // Trailing commas for multiline
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],

        // Whitespace
        'no_extra_blank_lines' => ['tokens' => ['extra', 'throw', 'use']],
        'no_whitespace_in_blank_line' => true,

        // Modern PHP features
        'modernize_types_casting' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'remove_inheritdoc' => false,
        ],

        // Code quality
        'strict_comparison' => true,
        'strict_param' => true,
        'native_function_invocation' => [
            'include' => ['@all'],
            'scope' => 'namespaced',
        ],

        // Security
        'no_php4_constructor' => true,
        'no_alias_functions' => true,

        // PHPDoc improvements
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'phpdoc_separation' => true,
        'phpdoc_trim' => true,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
    ])
    ->setFinder($finder);

return $config;
