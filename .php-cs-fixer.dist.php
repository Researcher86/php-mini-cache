<?php

declare(strict_types=1);

/*
 * PSR-12, strict types in every file, ordered imports, and native functions
 * called by their plain names. php-memory-lab, php-worker-pool and
 * php-systems-platform use this same rule set; php-job-queue,
 * php-mini-http-server and php-mini-database are on PER-CS 2.0 with
 * imported global classes instead, so the projects in php-systems-lab do
 * not all share one style.
 *
 * Two rules are switched off deliberately. native_function_invocation would
 * prefix every internal call with a backslash, and single_line_empty_body
 * would collapse an empty constructor onto one line - neither is how this
 * project was written by hand, and the formatter is here to keep the style
 * that exists rather than to impose a different one.
 *
 * examples/ and benchmarks/ are included: they are read as much as src/ is,
 * and a snippet that formats differently from the code it demonstrates is a
 * snippet that teaches the wrong habit.
 */
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/benchmarks')
    ->in(__DIR__ . '/bin')
    ->in(__DIR__ . '/examples')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP84Migration' => true,
        '@PSR12' => true,
        'declare_strict_types' => true,
        'native_function_invocation' => false,
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'single_line_empty_body' => false,
        'strict_param' => true,
    ])
    ->setFinder($finder);
