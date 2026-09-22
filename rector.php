<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\Config\RectorConfig;
use RectorLaravel\Rector\ClassMethod\ScopeNamedClassMethodToScopeAttributedClassMethodRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets(php84: true)
    ->withSets([
        PestSetList::CODING_STYLE,
    ])
    ->withRules([
        // ModelCastsPropertyToCastsMethodRector::class,
        ScopeNamedClassMethodToScopeAttributedClassMethodRector::class,
    ]);
