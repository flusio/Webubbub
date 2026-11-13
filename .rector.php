<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php73\Rector\String_\SensitiveHereNowDocRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/configuration',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets()
    ->withComposerBased(
        phpunit: true,
    )
    ->withPreparedSets(
        deadCode: true,
        typeDeclarations: true,
    )
    ->withImportNames(
        importNames: false,
        removeUnusedImports: true,
    )
    ->withSkip([
        __DIR__ . '/src/views',
        ClassPropertyAssignToConstructorPromotionRector::class,
        ReadOnlyPropertyRector::class,
        SensitiveHereNowDocRector::class,
    ]);
