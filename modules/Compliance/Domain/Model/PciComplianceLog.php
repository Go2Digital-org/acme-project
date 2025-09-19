<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Modules\Compliance\Domain\ValueObject\ComplianceLevel;
use Modules\Compliance\Domain\ValueObject\PciEventType;

/**
 * PCI DSS Compliance Log Model
 *
 * @property int $id
 * @property PciEventType $event_type
 * @property string $transaction_id
 * @property string $masked_card_number
 * @property string|null $merchant_id
 * @property ComplianceLevel $compliance_level
 * @property array<string, mixed> $security_measures
 * @property string|null $encryption_method
 * @property string|null $tokenization_method
 * @property bool $is_cardholder_data_present
 * @property string|null $vulnerability_scan_id
 * @property array<string, mixed>|null $security_assessment
 * @property string|null $processor_name
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $processed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PciComplianceLog extends Model
{
    protected $table = 'compliance_pci_logs';

    protected $fillable = [
        'event_type',
        'transaction_id',
        'masked_card_number',
        'merchant_id',
        'compliance_level',
        'security_measures',
        'encryption_method',
        'tokenization_method',
        'is_cardholder_data_present',
        'vulnerability_scan_id',
        'security_assessment',
        'processor_name',
        'ip_address',
        'user_agent',
        'processed_at',
    ];

    public function isCompliant(): bool
    {
        return $this->compliance_level->isCompliant() &&
               $this->hasRequiredSecurityMeasures();
    }

    public function hasRequiredSecurityMeasures(): bool
    {
        $requiredMeasures = [
            'encrypted_transmission',
            'secure_storage',
            'access_control',
            'network_security',
        ];

        $presentMeasures = array_keys($this->security_measures ?? []);

        return array_diff($requiredMeasures, $presentMeasures) === [];
    }

    public function isCardholderDataSecure(): bool
    {
        if (! $this->is_cardholder_data_present) {
            return true;
        }

        return ! empty($this->encryption_method) || ! empty($this->tokenization_method);
    }

    public function requiresVulnerabilityAssessment(): bool
    {
        return $this->compliance_level === ComplianceLevel::LEVEL_1 ||
               $this->is_cardholder_data_present;
    }

    public function maskCardNumber(string $cardNumber): string
    {
        $length = strlen($cardNumber);
        if ($length < 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4) . substr($cardNumber, -4);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => PciEventType::class,
            'compliance_level' => ComplianceLevel::class,
            'security_measures' => 'array',
            'security_assessment' => 'array',
            'is_cardholder_data_present' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }
}
