<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/resources',
        __DIR__ . '/routes',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // ->withPhpSets(php84: true, php85: true, php83: true)
    ->withTypeCoverageLevel(5)
    ->withDeadCodeLevel(5)
    ->withCodeQualityLevel(5);
