<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@PHP8x0Migration' => true,
        'global_namespace_import' => [
            'import_classes' => false,
            'import_constants' => false,
            'import_functions' => false,
        ],
        // the groups of @Symfony, plus the Doctrine mapping annotations kept together
        'phpdoc_separation' => [
            'groups' => [
                ['Annotation', 'NamedArgumentConstructor', 'Target'],
                ...PhpCsFixer\Fixer\Phpdoc\PhpdocSeparationFixer::OPTION_GROUPS_DEFAULT,
                ['ORM\*', 'Gedmo\*'],
            ],
            'skip_unlisted_annotations' => false,
        ],
    ])
    ->setFinder($finder)
;
