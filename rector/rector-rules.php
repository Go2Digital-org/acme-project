<?php

declare(strict_types=1);

use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

/**
 * Enhanced rule sets for hexagonal architecture and Laravel 12 modernization
 */
class RectorRuleSets
{
    /**
     * Basic type declaration rules (safe and verified)
     * Note: Some rules are provided by prepared sets, so we only add unique ones
     */
    public static function basicTypeRules(): array
    {
        return [
            TypedPropertyFromAssignsRector::class,
            // Note: TypedPropertyFromStrictConstructorRector and AddVoidReturnTypeWhereNoReturnRector
            // are included in the typeDeclarations prepared set
        ];
    }

    /**
     * PHP 8.4 modernization rules
     */
    public static function php84Rules(): array
    {
        return [
            // Only include if available in current Rector version
        ];
    }

    /**
     * PHP 8.3 modernization rules
     */
    public static function php83Rules(): array
    {
        return [
            // Only include verified rules
        ];
    }

    /**
     * PHP 8.2 modernization rules
     */
    public static function php82Rules(): array
    {
        return [
            // Will be populated with verified rules
        ];
    }

    /**
     * PHP 8.1 modernization rules
     */
    public static function php81Rules(): array
    {
        return [
            // Will be populated with verified rules
        ];
    }

    /**
     * PHP 8.0 modernization rules
     */
    public static function php80Rules(): array
    {
        return [
            // Will be populated with verified rules
        ];
    }

    /**
     * Safe code quality rules
     */
    public static function safeQualityRules(): array
    {
        return [
            // Will be populated with verified rules
        ];
    }

    /**
     * All basic recommended rules for the project
     */
    public static function basicRecommendedRules(): array
    {
        return self::basicTypeRules();
    }

    /**
     * Conservative rules - only safe transformations
     */
    public static function conservativeRules(): array
    {
        return self::basicTypeRules();
    }

    /**
     * All recommended rules for the project (placeholder)
     */
    public static function allRecommendedRules(): array
    {
        return self::basicRecommendedRules();
    }
}
