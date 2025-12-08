<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;

return RectorConfig::configure()
    ->withPaths([
//        __DIR__ . '/phpstan',
//        __DIR__ . '/sample',
        __DIR__ . '/src',
//        __DIR__ . '/tests',
    ])
    ->withPhpSets(php80: true)
//    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
    ->withPreparedSets(typeDeclarations: true, earlyReturn: true, strictBooleans: true)
    ->withImportNames(removeUnusedImports: true);
    ;
