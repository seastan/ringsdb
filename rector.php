<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withSets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION_DOCBLOCKS,
        PHPUnitSetList::PHPUNIT_NARROW_ASSERTS,
    ])
    ->withSkip([
        Rector\CodeQuality\Rector\For_\ForRepeatedCountToOwnVariableRector::class,
    ])
;
