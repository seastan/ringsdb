<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\For_\ForRepeatedCountToOwnVariableRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withAttributesSets(
        symfony: true,
        doctrine: true
    )
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
        privatization: true,
        phpunitNarrowAsserts: true,
    )
    ->withSkip([
        ForRepeatedCountToOwnVariableRector::class,
    ])
;
