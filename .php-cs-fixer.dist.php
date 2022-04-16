<?php
$rootDir = realpath(__DIR__);

$finder = PhpCsFixer\Finder::create()
    ->in([
        $rootDir . '/src',
        $rootDir . '/tests',
]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'psr_autoloading' => true,
        'array_syntax' => ['syntax' => 'short'],
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'yoda_style' => false, // false === $this->getAny()
        'strict_comparison' => true, // strict equals ('===' instead of '==')
        'strict_param' => true, // in_array('', [], !! TRUE !!);
        'no_superfluous_phpdoc_tags' => false, // Removes @param, @return and @var tags that don't provide any useful information.
        'phpdoc_summary' => false, // false означает, что не должно быть точки на конце строки описания в пхп-док аннотации (true - точка должна быть)
        'single_line_throw' => false,
        'visibility_required' => [
            'elements' => ['property', 'method', 'const'],
        ],
        '@DoctrineAnnotation' => true,
    ])
    ->setFinder($finder)
;
