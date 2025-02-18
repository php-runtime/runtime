<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'fully_qualified_strict_types' => false,
    ])
    ->setFinder($finder)
;
