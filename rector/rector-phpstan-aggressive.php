<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByParentCallTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictBoolReturnExprRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictConstantReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictFluentReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNewArrayRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictScalarReturnExprRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedPropertyRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeForArrayMapRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeForArrayReduceRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeFromArgRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeFromIterableMethodCallRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeFromObjectRector;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use RectorLaravel\Set\LaravelSetList;

/**
 * Aggressive Rector Configuration for PHPStan Type Improvements
 *
 * This configuration is designed to aggressively add type declarations throughout
 * the codebase while maintaining safety for the hexagonal architecture.
 *
 * Key Features:
 * - Aggressive type inference from usage patterns
 * - Includes all rules from rector-phpstan-fix.php
 * - Adds parameter, return, and property type declarations
 * - Converts generic arrays to typed arrays (array<string, mixed>)
 * - Safe for domain models (preserves mockability)
 *
 * Usage:
 * vendor/bin/rector process --config=rector/rector-phpstan-aggressive.php
 *
 * Safety Measures:
 * - Skips domain models to maintain test compatibility
 * - Excludes migrations, seeders, and factories
 * - Preserves existing architecture constraints
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

        // Skip specific aggressive rules on domain models to maintain mockability
        TypedPropertyFromStrictConstructorRector::class => [
            __DIR__ . '/../modules/*/Domain/Model',
        ],

        AddPropertyTypeDeclarationRector::class => [
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

        // Skip aggressive parameter typing on Laravel framework integrations
        AddParamTypeDeclarationRector::class => [
            __DIR__ . '/../modules/*/Infrastructure/Laravel/Controllers',
            __DIR__ . '/../app/Http/Controllers',
        ],
    ])

    // Use PHP 8.4 features
    ->withPhpSets(php84: true)

    // Include Laravel-specific improvements
    ->withSets([
        LaravelSetList::LARAVEL_120,
    ])

    // Enable aggressive type declarations with safe boundaries
    ->withPreparedSets(
        deadCode: false,        // Skip to avoid removing potentially needed code
        codeQuality: true,      // Enable safe code quality improvements
        typeDeclarations: true, // Core type declaration improvements
        privatization: false,   // Skip to avoid breaking dependency injection
        naming: false,          // Skip to avoid architectural naming conflicts
        instanceOf: true,       // Safe instanceof improvements
        earlyReturn: true,      // Enable early return patterns
        strictBooleans: false,  // Skip to avoid Laravel compatibility issues
    )

    // Aggressive type improvement rules
    ->withRules([
        // === Core Type Declaration Rules ===

        // Add parameter types based on usage analysis
        AddParamTypeDeclarationRector::class,

        // Add return types based on analysis
        AddReturnTypeDeclarationRector::class,

        // Add property types based on analysis
        AddPropertyTypeDeclarationRector::class,

        // Type properties from assignment patterns
        TypedPropertyFromAssignsRector::class,

        // Add void return types where appropriate
        AddVoidReturnTypeWhereNoReturnRector::class,

        // === Advanced Closure Type Improvements ===

        // Array function closure typing
        AddClosureParamTypeForArrayMapRector::class,
        AddClosureParamTypeForArrayReduceRector::class,
        AddClosureParamTypeFromArgRector::class,
        AddClosureParamTypeFromIterableMethodCallRector::class,
        AddClosureParamTypeFromObjectRector::class,

        // Arrow function return types
        AddArrowFunctionReturnTypeRector::class,

        // === Method Analysis Based Typing ===

        // Parameter types from method calls
        ParamTypeByMethodCallTypeRector::class,
        ParamTypeByParentCallTypeRector::class,

        // === Return Type Analysis ===

        // Return types from new expressions
        ReturnTypeFromReturnNewRector::class,

        // Return types from strict expressions
        ReturnTypeFromStrictBoolReturnExprRector::class,
        ReturnTypeFromStrictConstantReturnRector::class,
        ReturnTypeFromStrictFluentReturnRector::class,
        ReturnTypeFromStrictNativeCallRector::class,
        ReturnTypeFromStrictNewArrayRector::class,
        ReturnTypeFromStrictScalarReturnExprRector::class,
        ReturnTypeFromStrictTypedCallRector::class,
        ReturnTypeFromStrictTypedPropertyRector::class,
    ])

    // Optimize imports for cleaner code
    ->withImportNames()

    // Enable parallel processing for performance
    ->withParallel()

    // Set memory limit for large codebases
    ->withMemoryLimit('4G');

// Cache is managed separately
