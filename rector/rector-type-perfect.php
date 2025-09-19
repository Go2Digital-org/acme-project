<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNewArrayRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictScalarReturnExprRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;

/**
 * Type-Perfect Rector Configuration for PHPStan Array Type Fixes
 *
 * This configuration uses safer, well-tested rules that work with Rector 2.x
 * to add array type declarations and fix PHPStan level 8 errors.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/../app',
        __DIR__ . '/../modules',
        __DIR__ . '/../config',
        __DIR__ . '/../routes',
    ])
    ->withSkip([
        // Skip vendor and generated files
        __DIR__ . '/../vendor',
        __DIR__ . '/../storage',
        __DIR__ . '/../bootstrap/cache',
        __DIR__ . '/../tests',

        // Skip database files
        __DIR__ . '/../database/migrations',
        __DIR__ . '/../database/seeders',
        __DIR__ . '/../database/factories',
        __DIR__ . '/../modules/*/Infrastructure/Laravel/Migration',
        __DIR__ . '/../modules/*/Infrastructure/Laravel/Seeder',
        __DIR__ . '/../modules/*/Infrastructure/Laravel/Factory',
    ])

    // Use PHP 8.4 features
    ->withPhpSets(php84: true)

    // Enable basic type declarations
    ->withPreparedSets(
        typeDeclarations: true
    )

    // Add specific working rules
    ->withRules([
        // Add return type for arrays
        ReturnTypeFromStrictNewArrayRector::class,

        // Add return type from return new statements
        ReturnTypeFromReturnNewRector::class,

        // Add void return types
        AddVoidReturnTypeWhereNoReturnRector::class,

        // Add return types from scalar returns
        ReturnTypeFromStrictScalarReturnExprRector::class,

        // Add return types from native calls
        ReturnTypeFromStrictNativeCallRector::class,

        // Type properties from assignments
        TypedPropertyFromAssignsRector::class,

        // Arrow function return types
        AddArrowFunctionReturnTypeRector::class,
    ])

    // Optimize imports
    ->withImportNames()

    // Enable parallel processing
    ->withParallel()

    // Set memory limit
    ->withMemoryLimit('4G');
