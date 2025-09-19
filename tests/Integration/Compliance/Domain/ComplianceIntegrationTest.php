<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Compliance\Domain\Model\ComplianceAuditLog;
use Modules\Compliance\Domain\Model\DataRetentionPolicy;
use Modules\Compliance\Domain\Model\DataSubject;
use Modules\Compliance\Domain\Model\PciComplianceLog;
use Modules\Compliance\Domain\Model\PolicyAcceptance;
use Modules\Compliance\Domain\Model\PrivacyPolicy;
use Modules\Compliance\Domain\Service\ComplianceAuditService;
use Modules\Compliance\Domain\Service\DataRetentionService;
use Modules\Compliance\Domain\Service\GdprComplianceService;
use Modules\Compliance\Domain\Service\PciComplianceService;
use Modules\Compliance\Domain\Service\PrivacyPolicyService;
use Modules\Compliance\Domain\ValueObject\AuditEventType;
use Modules\Compliance\Domain\ValueObject\ComplianceStatus;
use Modules\Compliance\Domain\ValueObject\ConsentStatus;
use Modules\Compliance\Domain\ValueObject\DataProcessingPurpose;
use Modules\Compliance\Domain\ValueObject\PciEventType;
use Modules\Compliance\Domain\ValueObject\PolicyStatus;
use Modules\Compliance\Domain\ValueObject\RetentionAction;

beforeEach(function () {
    // Create test tables for integration testing
    createComplianceTables();

    // Create test subject model
    $this->testSubject = new class extends Model
    {
        protected $table = 'test_subjects';

        protected $fillable = ['email', 'name'];

        public $timestamps = false;

        /**
         * @return array<string, mixed>
         */
        public function getGdprExportData(): array
        {
            return [
                'id' => $this->id,
                'email' => $this->email,
                'name' => $this->name,
            ];
        }

        public function anonymizeGdprData(): void
        {
            $this->email = 'anonymized@example.com';
            $this->name = 'Anonymized User';
            $this->save();
        }
    };

    // Save test subject to database
    DB::table('test_subjects')->insert([
        'id' => 1,
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    $this->testSubject = $this->testSubject->find(1);

    // Initialize services
    $this->auditService = app(ComplianceAuditService::class);
    $this->gdprService = app(GdprComplianceService::class);
    $this->pciService = app(PciComplianceService::class);
    $this->retentionService = new DataRetentionService($this->auditService);
    $this->privacyService = new PrivacyPolicyService($this->auditService);
});

test('gdpr compliance full workflow integration', function () {
    // Record consent
    $purposes = [
        DataProcessingPurpose::DONATION_PROCESSING,
        DataProcessingPurpose::USER_AUTHENTICATION,
    ];

    $dataSubject = $this->gdprService->recordConsent(
        $this->testSubject,
        $purposes,
        'website_form',
        '192.168.1.1',
        'Mozilla/5.0',
        ['checkbox_clicked' => true]
    );

    expect($dataSubject)->toBeInstanceOf(DataSubject::class);
    expect($dataSubject->consent_status)->toBe(ConsentStatus::GIVEN);
    expect($dataSubject->consented_purposes)->toHaveCount(2);

    // Check consent
    $hasConsent = $this->gdprService->hasValidConsent(
        $this->testSubject,
        DataProcessingPurpose::DONATION_PROCESSING
    );
    expect($hasConsent)->toBeTrue();

    // Export data
    $exportData = $this->gdprService->exportPersonalData($this->testSubject);
    expect($exportData)->toHaveKey('subject_information');
    expect($exportData)->toHaveKey('consent_records');
    expect($exportData)->toHaveKey('model_data');

    // Withdraw consent
    $this->gdprService->withdrawConsent($this->testSubject, null, 'User request');

    $dataSubject->refresh();
    expect($dataSubject->consent_status)->toBe(ConsentStatus::WITHDRAWN);

    // Delete data
    $deleted = $this->gdprService->deletePersonalData($this->testSubject);
    expect($deleted)->toBeTrue();
});

test('pci compliance workflow integration', function () {
    // Log payment processing
    $log = $this->pciService->logPciEvent(
        PciEventType::PAYMENT_PROCESSING,
        'txn_123456789',
        '4111111111111111',
        'merchant_001'
    );

    expect($log)->toBeInstanceOf(PciComplianceLog::class);
    expect($log->masked_card_number)->toBe('************1111');
    expect($log->is_cardholder_data_present)->toBeTrue();

    // Validate card data security
    $validation = $this->pciService->validateCardDataSecurity('4111111111111111');
    expect($validation['is_compliant'])->toBeFalse();
    expect($validation['violations'])->toContain('Card number stored in plain text');

    // Perform vulnerability scan
    $scan = $this->pciService->performVulnerabilityScan();
    expect($scan)->toHaveKey('scan_id');
    expect($scan['overall_risk'])->toBe('low'); // No issues in test environment

    // Generate compliance report
    $report = $this->pciService->generateComplianceReport();
    expect($report)->toHaveKey('summary');
    expect($report)->toHaveKey('compliance_metrics');
});

test('audit trail workflow integration', function () {
    // Log data access
    $log = $this->auditService->logDataAccess(
        $this->testSubject,
        null,
        ['email', 'name']
    );

    expect($log)->toBeInstanceOf(ComplianceAuditLog::class);
    expect($log->event_type)->toBe(AuditEventType::DATA_ACCESS);

    // Log data modification
    $changes = [
        'email' => ['old' => 'old@example.com', 'new' => 'new@example.com'],
    ];

    $modLog = $this->auditService->logDataModification(
        $this->testSubject,
        $changes
    );

    expect($modLog->event_type)->toBe(AuditEventType::DATA_MODIFICATION);

    // Log security incident
    $incidentLog = $this->auditService->logSecurityIncident(
        'unauthorized_access',
        'Attempted unauthorized access to user data',
        ['ip_address' => '192.168.1.100']
    );

    expect($incidentLog->event_type)->toBe(AuditEventType::SECURITY_INCIDENT);
    expect($incidentLog->compliance_status)->toBe(ComplianceStatus::NON_COMPLIANT);

    // Generate audit trail
    $trail = $this->auditService->generateAuditTrail($this->testSubject);
    expect($trail)->toHaveKey('summary');
    expect($trail['summary']['total_events'])->toBeGreaterThan(0);

    // Mark incident as remediated
    $remediated = $this->auditService->markRemediated(
        $incidentLog->id,
        'Blocked IP address and updated security measures'
    );

    expect($remediated)->toBeTrue();
    $incidentLog->refresh();
    expect($incidentLog->compliance_status)->toBe(ComplianceStatus::REMEDIATED);
});

test('data retention workflow integration', function () {
    // Create retention policy
    $policy = $this->retentionService->createRetentionPolicy(
        'Test User Data Retention',
        'user_data',
        DataProcessingPurpose::USER_AUTHENTICATION,
        '+1 year',
        RetentionAction::ANONYMIZE,
        'GDPR compliance'
    );

    expect($policy)->toBeInstanceOf(DataRetentionPolicy::class);
    expect($policy->is_active)->toBeFalse();

    // Activate policy
    $activated = $this->retentionService->activatePolicy($policy->id, 1);
    expect($activated)->toBeTrue();

    $policy->refresh();
    expect($policy->is_active)->toBeTrue();

    // Assess data for retention
    $assessment = $this->retentionService->assessDataForRetention($this->testSubject);
    expect($assessment)->toBeArray();

    // Apply retention policies
    $results = $this->retentionService->applyRetentionPolicies();
    expect($results)->toBeArray();

    // Generate retention report
    $report = $this->retentionService->getRetentionReport();
    expect($report)->toHaveKey('summary');
    expect($report)->toHaveKey('policies');
    expect($report)->toHaveKey('recommendations');
});

test('privacy policy workflow integration', function () {
    // Create privacy policy with comprehensive compliance data
    $policy = $this->privacyService->createPolicy(
        '1.0.0',
        'Test Privacy Policy',
        'This is our comprehensive test privacy policy with full GDPR compliance details.',
        [
            'personal_data' => 'Name, email address, phone number',
            'technical_data' => 'IP address, browser information',
            'usage_data' => 'Platform interactions and preferences'
        ],
        [
            'service_provision' => 'Providing our donation platform services',
            'user_authentication' => 'User account management',
            'communication' => 'Service communications'
        ],
        [
            'consent' => 'User explicit consent for processing',
            'contract' => 'Performance of service contract',
            'legal_obligation' => 'Compliance with legal requirements'
        ]
    );

    expect($policy)->toBeInstanceOf(PrivacyPolicy::class);
    expect($policy->status)->toBe(PolicyStatus::DRAFT);

    // Activate policy
    $activated = $this->privacyService->activatePolicy($policy->id, 1);
    expect($activated)->toBeTrue();

    $policy->refresh();
    expect($policy->status)->toBe(PolicyStatus::ACTIVE);

    // Record user acceptance
    $acceptance = $this->privacyService->recordAcceptance(
        $this->testSubject,
        $policy->id,
        'website_checkbox'
    );

    expect($acceptance)->toBeInstanceOf(PolicyAcceptance::class);

    // Check if user has accepted
    $hasAccepted = $this->privacyService->hasUserAccepted($this->testSubject);
    expect($hasAccepted)->toBeTrue();

    // Get user privacy dashboard
    $dashboard = $this->privacyService->getUserPrivacyDashboard($this->testSubject);
    expect($dashboard)->toHaveKey('current_policy');
    expect($dashboard)->toHaveKey('user_acceptance');
    expect($dashboard['requires_acceptance'])->toBeFalse();

    // Generate compliance report
    $report = $this->privacyService->getComplianceReport();
    expect($report)->toHaveKey('status');
    expect($report)->toHaveKey('compliance_score');
    expect($report['status'])->toBe('compliant');
});

test('cross-service integration workflow', function () {
    // Create and activate privacy policy
    $policy = $this->privacyService->createPolicy(
        '1.0.0',
        'Integration Test Policy',
        'Policy content...',
        ['personal_data' => 'Name, email'],
        ['service_provision' => 'Services'],
        ['consent' => 'Consent']
    );

    $this->privacyService->activatePolicy($policy->id);

    // Record privacy policy acceptance and GDPR consent
    $this->privacyService->recordAcceptance($this->testSubject, $policy->id, 'checkbox');

    $dataSubject = $this->gdprService->recordConsent(
        $this->testSubject,
        [DataProcessingPurpose::USER_AUTHENTICATION],
        'privacy_policy_acceptance',
        '192.168.1.1',
        'Browser'
    );

    // Process payment (PCI logging)
    $pciLog = $this->pciService->logPciEvent(
        PciEventType::PAYMENT_PROCESSING,
        'txn_integration_test',
        '4111111111111111'
    );

    // Audit the entire workflow
    $this->auditService->logGdprRequest(
        'consent_given',
        $dataSubject,
        $this->testSubject
    );

    // Create retention policy for this data
    $retentionPolicy = $this->retentionService->createRetentionPolicy(
        'Integration Test Retention',
        'user_data',
        DataProcessingPurpose::USER_AUTHENTICATION,
        '+2 years',
        RetentionAction::ANONYMIZE,
        'Legal requirement'
    );

    $this->retentionService->activatePolicy($retentionPolicy->id);

    // Verify all components work together
    expect($dataSubject->hasValidConsent())->toBeTrue();
    expect($pciLog->isCompliant())->toBeTrue();
    expect($this->privacyService->hasUserAccepted($this->testSubject))->toBeTrue();

    // Generate comprehensive compliance report
    $auditReport = $this->auditService->getComplianceReport();
    $privacyReport = $this->privacyService->getComplianceReport();
    $pciReport = $this->pciService->generateComplianceReport();
    $retentionReport = $this->retentionService->getRetentionReport();

    expect($auditReport['metrics']['compliance_rate'])->toBeGreaterThan(80);
    expect($privacyReport['status'])->toBe('compliant');
    expect($pciReport['compliance_metrics']['overall_compliance_rate'])->toBeGreaterThan(80);
    expect($retentionReport['summary']['active_policies'])->toBeGreaterThan(0);
});

// Helper method to create test database tables
function createComplianceTables(): void
{
    // Create test subjects table
    if (! Schema::hasTable('test_subjects')) {
        Schema::create('test_subjects', function ($table) {
            $table->id();
            $table->string('email');
            $table->string('name');
        });
    }

    // Create compliance tables (simplified for testing)
    if (! Schema::hasTable('compliance_data_subjects')) {
        Schema::create('compliance_data_subjects', function ($table) {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('email');
            $table->string('consent_status');
            $table->json('consented_purposes')->nullable();
            $table->timestamp('consent_given_at')->nullable();
            $table->timestamp('consent_withdrawn_at')->nullable();
            $table->timestamp('data_export_requested_at')->nullable();
            $table->timestamp('data_export_completed_at')->nullable();
            $table->timestamp('deletion_requested_at')->nullable();
            $table->timestamp('deletion_completed_at')->nullable();
            $table->string('legal_basis')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('compliance_consent_records')) {
        Schema::create('compliance_consent_records', function ($table) {
            $table->id();
            $table->foreignId('data_subject_id');
            $table->string('purpose');
            $table->string('status');
            $table->string('consent_method')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('consent_data')->nullable();
            $table->string('withdrawal_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('compliance_audit_logs')) {
        Schema::create('compliance_audit_logs', function ($table) {
            $table->id();
            $table->string('event_type');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type')->nullable();
            $table->string('compliance_status');
            $table->json('event_data');
            $table->json('risk_assessment')->nullable();
            $table->string('compliance_officer')->nullable();
            $table->text('remediation_action')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('remediated_at')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('compliance_pci_logs')) {
        Schema::create('compliance_pci_logs', function ($table) {
            $table->id();
            $table->string('event_type');
            $table->string('transaction_id');
            $table->string('masked_card_number')->nullable();
            $table->string('merchant_id')->nullable();
            $table->string('compliance_level');
            $table->json('security_measures');
            $table->string('encryption_method')->nullable();
            $table->string('tokenization_method')->nullable();
            $table->boolean('is_cardholder_data_present');
            $table->string('vulnerability_scan_id')->nullable();
            $table->json('security_assessment')->nullable();
            $table->string('processor_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('processed_at');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('compliance_data_retention_policies')) {
        Schema::create('compliance_data_retention_policies', function ($table) {
            $table->id();
            $table->string('policy_name');
            $table->string('data_category');
            $table->string('purpose');
            $table->string('retention_period');
            $table->string('retention_action');
            $table->string('legal_basis');
            $table->json('deletion_criteria')->nullable();
            $table->json('anonymization_rules')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('compliance_privacy_policies')) {
        Schema::create('compliance_privacy_policies', function ($table) {
            $table->id();
            $table->string('version');
            $table->string('title');
            $table->longText('content');
            $table->string('status');
            $table->json('data_categories')->nullable();
            $table->json('processing_purposes')->nullable();
            $table->json('legal_bases')->nullable();
            $table->json('third_parties')->nullable();
            $table->json('retention_periods')->nullable();
            $table->json('user_rights')->nullable();
            $table->text('contact_information')->nullable();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('compliance_policy_acceptances')) {
        Schema::create('compliance_policy_acceptances', function ($table) {
            $table->id();
            $table->foreignId('privacy_policy_id');
            $table->string('user_type');
            $table->unsignedBigInteger('user_id');
            $table->string('acceptance_method');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('acceptance_data')->nullable();
            $table->timestamp('accepted_at');
            $table->timestamps();
        });
    }

    // Create security_incidents table for incident logging
    if (! Schema::hasTable('security_incidents')) {
        Schema::create('security_incidents', function ($table) {
            $table->id();
            $table->string('incident_type');
            $table->text('description');
            $table->json('context')->nullable();
            $table->string('severity')->default('low');
            $table->timestamp('occurred_at');
            $table->timestamps();
        });
    }
}
