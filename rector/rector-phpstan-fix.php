<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeForArrayMapRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeForArrayReduceRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeFromArgRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeFromIterableMethodCallRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeFromObjectRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use RectorLaravel\Set\LaravelSetList;

/**
 * Rector Configuration for PHPStan Array Type Fixes
 *
 * This configuration is specifically designed to automatically fix array type hints
 * and improve type declarations throughout the codebase to reduce PHPStan errors.
 *
 * Focus areas:
 * - Array parameter and return type documentation
 * - Closure return types
 * - Strict typed property inference
 * - Laravel-specific type improvements
 *
 * Run with: vendor/bin/rector process --config=rector/rector-phpstan-fix.php
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/../app',
        __DIR__ . '/../modules',
        __DIR__ . '/../config',
        __DIR__ . '/../routes',
        __DIR__ . '/../bootstrap',
    ])
    ->withSkip([
        // Skip vendor and generated files
        __DIR__ . '/../vendor',
        __DIR__ . '/../storage',
        __DIR__ . '/../bootstrap/cache',
        __DIR__ . '/../node_modules',
        __DIR__ . '/../public',
        __DIR__ . '/../resources/views',
        __DIR__ . '/../tests',

        // Skip database-related files to avoid breaking migrations
        __DIR__ . '/../modules/*/Infrastructure/Laravel/Migration',
        __DIR__ . '/../modules/*/Infrastructure/Laravel/Seeder',
        __DIR__ . '/../database/migrations',
        __DIR__ . '/../database/seeders',

        // Skip factories to maintain test compatibility
        __DIR__ . '/../modules/*/Infrastructure/Laravel/Factory',
        __DIR__ . '/../database/factories',

        // Skip Campaign model to avoid architectural constraint violations
        __DIR__ . '/../modules/Campaign/Domain/Model/Campaign.php',

        // Skip specific rules on domain models to maintain mockability
        TypedPropertyFromStrictConstructorRector::class => [
            __DIR__ . '/../modules/*/Domain/Model',
        ],

        // Skip readonly transformations on critical domain classes
        \Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class => [
            __DIR__ . '/../modules/*/Domain/Model',
            __DIR__ . '/../modules/*/Application/Command/*Handler.php',
            __DIR__ . '/../modules/*/Application/Query/*Handler.php',
        ],

        // Skip constructor promotion on domain models
        \Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/../modules/*/Domain/Model',
        ],
    ])

    // Use PHP 8.4 features
    ->withPhpSets(php84: true)

    // Include Laravel-specific improvements
    ->withSets([
        LaravelSetList::LARAVEL_120,
    ])

    // Enable core improvements with focus on type declarations
    ->withPreparedSets(
        deadCode: false,
        codeQuality: false,
        typeDeclarations: true,  // This includes array type improvements
        privatization: false,
        naming: false,
        instanceOf: false,
        earlyReturn: false,
        strictBooleans: false,
    )

    // Additional specific type improvement rules not included in prepared sets
    ->withRules([
        // Advanced closure type improvements for array functions
        AddClosureParamTypeForArrayMapRector::class,
        AddClosureParamTypeForArrayReduceRector::class,
        AddClosureParamTypeFromArgRector::class,
        AddClosureParamTypeFromIterableMethodCallRector::class,
        AddClosureParamTypeFromObjectRector::class,
    ])

    // Optimize imports
    ->withImportNames()

    // Enable parallel processing for speed
    ->withParallel()

    // Enable memory optimizations
    ->withMemoryLimit('2G');
