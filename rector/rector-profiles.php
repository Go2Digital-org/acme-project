<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

require_once __DIR__ . '/rector-rules.php';

/**
 * Rector configuration profiles for different modernization levels
 */
class RectorProfiles
{
    /**
     * Conservative profile - safe refactoring with minimal impact
     */
    public static function conservative()
    {
        return RectorConfig::configure()
            ->withPaths([
                __DIR__ . '/../app',
                __DIR__ . '/../modules',
                __DIR__ . '/../config',
                __DIR__ . '/../routes',
            ])
            ->withSkip([
                __DIR__ . '/../vendor',
                __DIR__ . '/../storage',
                __DIR__ . '/../bootstrap/cache',
                __DIR__ . '/../node_modules',
                __DIR__ . '/../public',
                __DIR__ . '/../resources/views',
                __DIR__ . '/../tests',
                __DIR__ . '/../modules/*/Infrastructure/Laravel/Migration',
                __DIR__ . '/../modules/*/Infrastructure/Laravel/Seeder',
                __DIR__ . '/../modules/*/Infrastructure/Laravel/Factory',
            ])
            ->withPhpSets(php84: true)
            ->withPreparedSets(
                deadCode: true,
                codeQuality: true,
                earlyReturn: true,
            )
            ->withRules(RectorRuleSets::conservativeRules())
            ->withImportNames()
            ->withParallel();
    }

    /**
     * Moderate profile - includes type improvements and PHP 8.x features
     */
    public static function moderate()
    {
        return RectorConfig::configure()
            ->withPaths([
                __DIR__ . '/../app',
                __DIR__ . '/../modules',
                __DIR__ . '/../config',
                __DIR__ . '/../routes',
            ])
            ->withSkip([
                __DIR__ . '/../vendor',
                __DIR__ . '/../storage',
                __DIR__ . '/../bootstrap/cache',
                __DIR__ . '/../node_modules',
                __DIR__ . '/../public',
                __DIR__ . '/../resources/views',
                __DIR__ . '/../tests',
                __DIR__ . '/../modules/*/Infrastructure/Laravel/Migration',
                __DIR__ . '/../modules/*/Infrastructure/Laravel/Seeder',
                __DIR__ . '/../modules/*/Infrastructure/Laravel/Factory',
                // Skip readonly on domain models for mocking compatibility
                \Rector\Php82\Rector\Class_\ReadOnlyClassRector::class => [
                    __DIR__ . '/../modules/*/Domain/Model',
                ],
                \Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class => [
                    __DIR__ . '/../modules/*/Domain/Model',
                ],
            ])
            ->withPhpSets(php84: true)
            ->withSets([
                \RectorLaravel\Set\LaravelSetList::LARAVEL_120,
            ])
            ->withPreparedSets(
                deadCode: true,
                codeQuality: true,
                typeDeclarations: true,
                earlyReturn: true,
            )
            ->withRules(RectorRuleSets::basicRecommendedRules())
            ->withImportNames()
            ->withParallel();
    }

    /**
     * Aggressive profile - full modernization including PHP 8.4 features
     */
    public static function aggressive()
    {
        return RectorConfig::configure()
            ->withPaths([
                __DIR__ . '/../app',
                __DIR__ . '/../modules',
                __DIR__ . '/../config',
                __DIR__ . '/../routes',
            ])
            ->withSkip([
                __DIR__ . '/../vendor',
                __DIR__ . '/../storage',
                __DIR__ . '/../bootstrap/cache',
                __DIR__ . '/../node_modules',
                __DIR__ . '/../public',
                __DIR__ . '/../resources/views',
                __DIR__ . '/../tests',
                __DIR__ . '/../modules/*/Infrastructure/Laravel/Migration',
                __DIR__ . '/../modules/*/Infrastructure/Laravel/Seeder',
                __DIR__ . '/../modules/*/Infrastructure/Laravel/Factory',
                // Skip Campaign model to avoid architectural constraint violations
                __DIR__ . '/../modules/Campaign/Domain/Model/Campaign.php',
                // Skip readonly on specific patterns for mocking compatibility
                \Rector\Php82\Rector\Class_\ReadOnlyClassRector::class => [
                    __DIR__ . '/../modules/*/Domain/Model',
                    __DIR__ . '/../modules/*/Application/Command/*Handler.php',
                    __DIR__ . '/../modules/*/Application/Query/*Handler.php',
                ],
                \Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class => [
                    __DIR__ . '/../modules/*/Domain/Model',
                ],
                \Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector::class => [
                    __DIR__ . '/../modules/*/Domain/Model',
                ],
            ])
            ->withPhpSets(php84: true)
            ->withSets([
                \RectorLaravel\Set\LaravelSetList::LARAVEL_120,
            ])
            ->withPreparedSets(
                deadCode: true,
                codeQuality: true,
                typeDeclarations: true,
                earlyReturn: true,
                privatization: false, // Skip to avoid breaking DI
                naming: false, // Skip to avoid architectural conflicts
                instanceOf: true,
                strictBooleans: false, // Skip to avoid issues with Laravel
            )
            ->withRules(RectorRuleSets::basicRecommendedRules())
            ->withImportNames()
            ->withParallel();
    }

    /**
     * Domain-focused profile - safe rules for domain layer only
     */
    public static function domainOnly()
    {
        return RectorConfig::configure()
            ->withPaths([
                __DIR__ . '/../modules/*/Domain',
            ])
            ->withSkip([
                __DIR__ . '/../modules/*/Domain/Model', // Skip models entirely for safety
            ])
            ->withPhpSets(php84: true)
            ->withPreparedSets(
                deadCode: true,
                codeQuality: true,
                typeDeclarations: true,
                earlyReturn: true,
            )
            ->withRules(RectorRuleSets::basicRecommendedRules())
            ->withImportNames()
            ->withParallel();
    }

    /**
     * Infrastructure-focused profile - Laravel-specific modernizations
     */
    public static function infrastructureOnly()
    {
        return RectorConfig::configure()
            ->withPaths([
                __DIR__ . '/../modules/*/Infrastructure',
                __DIR__ . '/../app',
                __DIR__ . '/../config',
                __DIR__ . '/../routes',
            ])
            ->withSkip([
                __DIR__ . '/../modules/*/Infrastructure/Laravel/Migration',
                __DIR__ . '/../modules/*/Infrastructure/Laravel/Seeder',
                __DIR__ . '/../modules/*/Infrastructure/Laravel/Factory',
            ])
            ->withPhpSets(php84: true)
            ->withSets([
                \RectorLaravel\Set\LaravelSetList::LARAVEL_120,
            ])
            ->withPreparedSets(
                deadCode: true,
                codeQuality: true,
                typeDeclarations: true,
                earlyReturn: true,
            )
            ->withRules(RectorRuleSets::basicRecommendedRules())
            ->withImportNames()
            ->withParallel();
    }
}
