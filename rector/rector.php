<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/../app',
        __DIR__ . '/../modules',
        __DIR__ . '/../routes',
        __DIR__ . '/../config',
        __DIR__ . '/../lang',
        // Note: config, routes, and lang excluded for conservative refactoring
        // Can be added later after careful review
    ])
    ->withSkip([
        __DIR__ . '/../vendor',
        __DIR__ . '/../storage',
        __DIR__ . '/../bootstrap/cache',
        __DIR__ . '/../node_modules',
        __DIR__ . '/../public',
        __DIR__ . '/../resources/views',
        __DIR__ . '/../tests',
        // Skip migrations and seeders - they have specific Laravel patterns
        __DIR__ . '/../modules/*/Infrastructure/Laravel/Migration',
        __DIR__ . '/../modules/*/Infrastructure/Laravel/Seeder',
        __DIR__ . '/../modules/*/Infrastructure/Laravel/Factory',
        // Skip Campaign model to avoid architectural constraint violations
        __DIR__ . '/../modules/Campaign/Domain/Model/Campaign.php',
        // Skip specific classes that might have mocking issues
        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/../modules/*/Domain/Model',
            __DIR__ . '/../modules/*/Application/Command',
            __DIR__ . '/../modules/*/Application/Query',
        ],
        ReadOnlyClassRector::class => [
            // Skip domain models and handlers for mocking compatibility
            __DIR__ . '/../modules/*/Domain/Model',
            __DIR__ . '/../modules/*/Application/Command/*Handler.php',
            __DIR__ . '/../modules/*/Application/Query/*Handler.php',
        ],
        ReadOnlyPropertyRector::class => [
            // Skip domain models for mocking compatibility
            __DIR__ . '/../modules/*/Domain/Model',
        ],
        // Skip Blade component renaming that breaks Laravel 11+
        \Rector\Renaming\Rector\MethodCall\RenameMethodRector::class => [
            __DIR__ . '/../modules/*/Provider',
            __DIR__ . '/../modules/*/Infrastructure/Laravel/Provider',
            __DIR__ . '/../app/Providers',
        ],
    ])
    ->withPhpSets(php84: true)
    ->withSets([
        LaravelSetList::LARAVEL_120,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: false,
        naming: false,
        instanceOf: true,
        earlyReturn: true,
        strictBooleans: false,
    )
    ->withRules([
        // PHP 8.3 - Add Override attribute (not included in default sets)
        AddOverrideAttributeToOverriddenMethodsRector::class,
    ])
    ->withImportNames()
    ->withParallel();
