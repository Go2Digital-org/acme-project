<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\ValueObject;

enum ComplianceLevel: string
{
    case LEVEL_1 = 'level_1'; // 6M+ transactions annually
    case LEVEL_2 = 'level_2'; // 1M-6M transactions annually
    case LEVEL_3 = 'level_3'; // 20K-1M e-commerce transactions annually
    case LEVEL_4 = 'level_4'; // <20K e-commerce transactions annually
    case SAQ_A = 'saq_a';     // Card-not-present, fully outsourced
    case SAQ_B = 'saq_b';     // Imprint machines or standalone dial-out terminals
    case SAQ_C = 'saq_c';     // Payment application systems connected to Internet
    case SAQ_D = 'saq_d';     // All other merchants

    public function isCompliant(): bool
    {
        // All levels are considered compliant if properly implemented
        return true;
    }

    public function requiresOnSiteAssessment(): bool
    {
        return match ($this) {
            self::LEVEL_1 => true,
            self::LEVEL_2 => true,
            default => false
        };
    }

    public function requiresVulnerabilityScanning(): bool
    {
        return match ($this) {
            self::LEVEL_1 => true,
            self::LEVEL_2 => true,
            self::LEVEL_3 => true,
            self::LEVEL_4 => false,
            default => true
        };
    }

    public function penetrationTestingFrequency(): string
    {
        return match ($this) {
            self::LEVEL_1 => 'annually',
            self::LEVEL_2 => 'annually',
            default => 'as_needed'
        };
    }

    public function assessmentFrequency(): string
    {
        return match ($this) {
            self::LEVEL_1 => 'annually',
            self::LEVEL_2 => 'annually',
            self::LEVEL_3 => 'annually',
            self::LEVEL_4 => 'annually',
            default => 'annually'
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::LEVEL_1 => 'Level 1: 6M+ transactions annually',
            self::LEVEL_2 => 'Level 2: 1M-6M transactions annually',
            self::LEVEL_3 => 'Level 3: 20K-1M e-commerce transactions annually',
            self::LEVEL_4 => 'Level 4: <20K e-commerce transactions annually',
            self::SAQ_A => 'SAQ A: Card-not-present, fully outsourced',
            self::SAQ_B => 'SAQ B: Imprint or standalone dial-out terminals',
            self::SAQ_C => 'SAQ C: Payment applications connected to Internet',
            self::SAQ_D => 'SAQ D: All other merchants',
        };
    }

    /**
     * @return list<string>
     */
    public function requiredSecurityMeasures(): array
    {
        $baseMeasures = [
            'encrypted_transmission',
            'secure_storage',
            'access_control',
            'network_security',
            'regular_monitoring',
            'vulnerability_management',
        ];

        return match ($this) {
            self::LEVEL_1, self::LEVEL_2 => array_merge($baseMeasures, [
                'penetration_testing',
                'on_site_assessment',
                'quarterly_scans',
                'real_time_monitoring',
            ]),
            default => $baseMeasures
        };
    }
}
