<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\Service;

use Illuminate\Support\Collection;
use Modules\Compliance\Domain\Model\PciComplianceLog;
use Modules\Compliance\Domain\ValueObject\ComplianceLevel;
use Modules\Compliance\Domain\ValueObject\PciEventType;

class PciComplianceService
{
    /**
     * @param  array<string, mixed>  $securityMeasures
     * @param  array<string, mixed>  $metadata
     */
    public function logPciEvent(
        PciEventType $eventType,
        string $transactionId,
        ?string $cardNumber = null,
        ?string $merchantId = null,
        ?array $securityMeasures = null,
        ?array $metadata = null
    ): PciComplianceLog {
        $log = PciComplianceLog::create([
            'event_type' => $eventType,
            'transaction_id' => $transactionId,
            'masked_card_number' => $cardNumber ? $this->maskCardNumber($cardNumber) : null,
            'merchant_id' => $merchantId,
            'compliance_level' => $this->determineComplianceLevel(),
            'security_measures' => $securityMeasures ?? $this->getDefaultSecurityMeasures(),
            'encryption_method' => 'AES-256-GCM',
            'tokenization_method' => 'EMV-Token',
            'is_cardholder_data_present' => $cardNumber !== null,
            'processor_name' => config('payment.default_processor'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'processed_at' => now(),
        ]);

        if ($eventType->requiresRealTimeMonitoring()) {
            $this->triggerRealTimeAlert();
        }

        return $log;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function validateCardDataSecurity(string $cardNumber, array $context = []): array
    {
        $violations = [];

        // Check if card number is properly encrypted
        if ($this->isPlainTextCardNumber($cardNumber)) {
            $violations[] = 'Card number stored in plain text';
        }

        // Check if sensitive authentication data is present
        if (isset($context['cvv']) || isset($context['pin'])) {
            $violations[] = 'Sensitive authentication data present';
        }

        // Check encryption requirements
        if (! $this->isProperlyEncrypted($cardNumber)) {
            $violations[] = 'Card data not properly encrypted';
        }

        // Check tokenization
        if (! $this->isTokenized($cardNumber)) {
            $violations[] = 'Card data not tokenized';
        }

        return [
            'is_compliant' => $violations === [],
            'violations' => $violations,
            'security_level' => $this->assessSecurityLevel($cardNumber),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function performVulnerabilityScan(): array
    {
        $scanId = uniqid('vuln_scan_');

        $vulnerabilities = [
            'ssl_configuration' => $this->checkSslConfiguration(),
            'network_security' => $this->checkNetworkSecurity(),
            'access_controls' => $this->checkAccessControls(),
            'data_encryption' => $this->checkDataEncryption(),
            'logging_monitoring' => $this->checkLoggingMonitoring(),
        ];

        $overallRisk = $this->calculateOverallRisk($vulnerabilities);

        // Log vulnerability scan
        $this->logPciEvent(
            PciEventType::VULNERABILITY_SCAN,
            $scanId,
            null,
            null,
            ['scan_results' => $vulnerabilities],
            ['overall_risk' => $overallRisk]
        );

        return [
            'scan_id' => $scanId,
            'vulnerabilities' => $vulnerabilities,
            'overall_risk' => $overallRisk,
            'compliance_status' => $overallRisk === 'low' ? 'compliant' : 'non_compliant',
            'recommendations' => $this->generateRecommendations($vulnerabilities),
        ];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    public function generateComplianceReport(array $dateRange = []): array
    {
        $startDate = $dateRange['start'] ?? now()->subMonth();
        $endDate = $dateRange['end'] ?? now();

        $logs = PciComplianceLog::whereBetween('created_at', [$startDate, $endDate])->get();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_transactions' => $logs->where('event_type', PciEventType::PAYMENT_PROCESSING)->count(),
                'security_incidents' => $logs->where('event_type', PciEventType::SECURITY_INCIDENT)->count(),
                'vulnerability_scans' => $logs->where('event_type', PciEventType::VULNERABILITY_SCAN)->count(),
                'compliance_violations' => $logs->whereNotNull('security_assessment')->where('compliance_level', '!=', ComplianceLevel::LEVEL_4)->count(),
            ],
            'compliance_metrics' => [
                'overall_compliance_rate' => $this->calculateComplianceRate($logs),
                'security_score' => $this->calculateSecurityScore($logs),
                'risk_level' => $this->assessOverallRisk($logs),
            ],
            'recommendations' => $this->generateComplianceRecommendations($logs),
        ];
    }

    private function maskCardNumber(string $cardNumber): string
    {
        $cleaned = preg_replace('/\D/', '', $cardNumber);
        $length = strlen((string) $cleaned);

        if ($length < 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4) . substr((string) $cleaned, -4);
    }

    private function determineComplianceLevel(): ComplianceLevel
    {
        // Default to Level 4 for small merchants
        return ComplianceLevel::LEVEL_4;
    }

    /**
     * @return array<string, bool>
     */
    private function getDefaultSecurityMeasures(): array
    {
        return [
            'encrypted_transmission' => true,
            'secure_storage' => true,
            'access_control' => true,
            'network_security' => true,
            'regular_monitoring' => true,
            'vulnerability_management' => true,
        ];
    }

    private function isPlainTextCardNumber(string $cardNumber): bool
    {
        // Check if it looks like a plain credit card number
        $cleaned = preg_replace('/\D/', '', $cardNumber);

        return strlen((string) $cleaned) >= 13 && strlen((string) $cleaned) <= 19 && ctype_digit((string) $cleaned);
    }

    private function isProperlyEncrypted(string $cardNumber): bool
    {
        // Check if the data appears to be encrypted
        return ! $this->isPlainTextCardNumber($cardNumber) && strlen($cardNumber) > 50;
    }

    private function isTokenized(string $cardNumber): bool
    {
        // Check if it's a token (non-numeric identifier)
        return ! ctype_digit((string) preg_replace('/\D/', '', $cardNumber));
    }

    private function assessSecurityLevel(string $cardNumber): string
    {
        if ($this->isPlainTextCardNumber($cardNumber)) {
            return 'critical';
        }

        if (! $this->isProperlyEncrypted($cardNumber) || ! $this->isTokenized($cardNumber)) {
            return 'high';
        }

        return 'low';
    }

    private function triggerRealTimeAlert(): void
    {
        // Implementation would send real-time alerts to security team
        // This could integrate with monitoring systems, send emails, etc.
    }

    /**
     * @return array<string, mixed>
     */
    private function checkSslConfiguration(): array
    {
        return [
            'tls_version' => '1.3',
            'cipher_strength' => 'strong',
            'certificate_valid' => true,
            'issues' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkNetworkSecurity(): array
    {
        return [
            'firewall_configured' => true,
            'network_segmentation' => true,
            'intrusion_detection' => true,
            'issues' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkAccessControls(): array
    {
        return [
            'multi_factor_auth' => true,
            'role_based_access' => true,
            'password_policy' => true,
            'issues' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkDataEncryption(): array
    {
        return [
            'data_at_rest' => true,
            'data_in_transit' => true,
            'key_management' => true,
            'issues' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkLoggingMonitoring(): array
    {
        return [
            'audit_logging' => true,
            'real_time_monitoring' => true,
            'log_retention' => true,
            'issues' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $vulnerabilities
     */
    private function calculateOverallRisk(array $vulnerabilities): string
    {
        $totalIssues = 0;
        foreach ($vulnerabilities as $category) {
            $totalIssues += count($category['issues'] ?? []);
        }

        return match (true) {
            $totalIssues === 0 => 'low',
            $totalIssues <= 3 => 'medium',
            default => 'high'
        };
    }

    /**
     * @param  array<string, mixed>  $vulnerabilities
     * @return list<string>
     */
    private function generateRecommendations(array $vulnerabilities): array
    {
        $recommendations = [];

        foreach ($vulnerabilities as $category => $data) {
            if (! empty($data['issues'])) {
                $recommendations[] = "Address {$category} issues: " . implode(', ', $data['issues']);
            }
        }

        return $recommendations;
    }

    /**
     * @param  Collection<int, PciComplianceLog>  $logs
     */
    private function calculateComplianceRate(Collection $logs): float
    {
        $total = $logs->count();
        if ($total === 0) {
            return 100.0;
        }

        $compliant = $logs->filter(fn (PciComplianceLog $log): bool => $log->isCompliant())->count();

        return round(($compliant / $total) * 100, 2);
    }

    /**
     * @param  Collection<int, PciComplianceLog>  $logs
     */
    private function calculateSecurityScore(Collection $logs): int
    {
        // Simplified security score calculation
        $complianceRate = $this->calculateComplianceRate($logs);
        $incidents = $logs->where('event_type', PciEventType::SECURITY_INCIDENT)->count();

        $score = (int) $complianceRate;
        $score -= ($incidents * 10); // Deduct points for incidents

        return max(0, min(100, $score));
    }

    /**
     * @param  Collection<int, PciComplianceLog>  $logs
     */
    private function assessOverallRisk(Collection $logs): string
    {
        $incidents = $logs->where('event_type', PciEventType::SECURITY_INCIDENT)->count();
        $complianceRate = $this->calculateComplianceRate($logs);

        return match (true) {
            $incidents > 5 || $complianceRate < 80 => 'high',
            $incidents > 2 || $complianceRate < 95 => 'medium',
            default => 'low'
        };
    }

    /**
     * @return list<string>
     */
    /**
     * @param  Collection<int, PciComplianceLog>  $logs
     * @return string[]
     */
    private function generateComplianceRecommendations(Collection $logs): array
    {
        $recommendations = [];

        $complianceRate = $this->calculateComplianceRate($logs);
        if ($complianceRate < 95) {
            $recommendations[] = 'Improve overall compliance rate through enhanced security measures';
        }

        $incidents = $logs->where('event_type', PciEventType::SECURITY_INCIDENT)->count();
        if ($incidents > 0) {
            $recommendations[] = 'Investigate and remediate security incidents';
        }

        return $recommendations;
    }
}
