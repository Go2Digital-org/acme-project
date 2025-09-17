<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\ValueObject;

enum OrganizationCategory: string
{
    case TECHNOLOGY = 'technology';
    case HEALTHCARE = 'healthcare';
    case FINANCE = 'finance';
    case EDUCATION = 'education';
    case RETAIL = 'retail';
    case MANUFACTURING = 'manufacturing';
    case CONSULTING = 'consulting';
    case NON_PROFIT = 'non_profit';
    case GOVERNMENT = 'government';
    case MEDIA = 'media';
    case REAL_ESTATE = 'real_estate';
    case TRANSPORTATION = 'transportation';
    case ENERGY = 'energy';
    case TELECOMMUNICATIONS = 'telecommunications';
    case HOSPITALITY = 'hospitality';
    case AGRICULTURE = 'agriculture';
    case CONSTRUCTION = 'construction';
    case LEGAL = 'legal';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::TECHNOLOGY => 'Technology',
            self::HEALTHCARE => 'Healthcare',
            self::FINANCE => 'Finance',
            self::EDUCATION => 'Education',
            self::RETAIL => 'Retail',
            self::MANUFACTURING => 'Manufacturing',
            self::CONSULTING => 'Consulting',
            self::NON_PROFIT => 'Non-Profit',
            self::GOVERNMENT => 'Government',
            self::MEDIA => 'Media',
            self::REAL_ESTATE => 'Real Estate',
            self::TRANSPORTATION => 'Transportation',
            self::ENERGY => 'Energy',
            self::TELECOMMUNICATIONS => 'Telecommunications',
            self::HOSPITALITY => 'Hospitality',
            self::AGRICULTURE => 'Agriculture',
            self::CONSTRUCTION => 'Construction',
            self::LEGAL => 'Legal',
            self::OTHER => 'Other',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::TECHNOLOGY => 'blue',
            self::HEALTHCARE => 'green',
            self::FINANCE => 'purple',
            self::EDUCATION => 'indigo',
            self::RETAIL => 'orange',
            self::MANUFACTURING => 'gray',
            self::CONSULTING => 'cyan',
            self::NON_PROFIT => 'pink',
            self::GOVERNMENT => 'red',
            self::MEDIA => 'yellow',
            self::REAL_ESTATE => 'lime',
            self::TRANSPORTATION => 'teal',
            self::ENERGY => 'amber',
            self::TELECOMMUNICATIONS => 'violet',
            self::HOSPITALITY => 'rose',
            self::AGRICULTURE => 'emerald',
            self::CONSTRUCTION => 'stone',
            self::LEGAL => 'slate',
            self::OTHER => 'neutral',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::TECHNOLOGY => 'heroicon-o-computer-desktop',
            self::HEALTHCARE => 'heroicon-o-heart',
            self::FINANCE => 'heroicon-o-banknotes',
            self::EDUCATION => 'heroicon-o-academic-cap',
            self::RETAIL => 'heroicon-o-shopping-bag',
            self::MANUFACTURING => 'heroicon-o-cog-6-tooth',
            self::CONSULTING => 'heroicon-o-light-bulb',
            self::NON_PROFIT => 'heroicon-o-hand-raised',
            self::GOVERNMENT => 'heroicon-o-building-library',
            self::MEDIA => 'heroicon-o-tv',
            self::REAL_ESTATE => 'heroicon-o-home',
            self::TRANSPORTATION => 'heroicon-o-truck',
            self::ENERGY => 'heroicon-o-bolt',
            self::TELECOMMUNICATIONS => 'heroicon-o-phone',
            self::HOSPITALITY => 'heroicon-o-building-office',
            self::AGRICULTURE => 'heroicon-o-sun',
            self::CONSTRUCTION => 'heroicon-o-wrench-screwdriver',
            self::LEGAL => 'heroicon-o-scale',
            self::OTHER => 'heroicon-o-building-office-2',
        };
    }

    /** @return array<array-key, mixed> */
    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $category): array => [$category->value => $category->getLabel()])
            ->toArray();
    }
}
