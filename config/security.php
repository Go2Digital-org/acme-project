<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enterprise Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security configuration for enterprise-grade
    | protection including encryption, audit logging, and compliance.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Encryption Settings
    |--------------------------------------------------------------------------
    */
    'encryption' => [
        'algorithm' => 'AES-256-GCM',
        'key_rotation_days' => 90,
        'at_rest' => [
            'enabled' => env('DB_ENCRYPTION_ENABLED', true),
            'columns' => [
                'users' => ['email', 'name', 'phone', 'address'],
                'donations' => ['donor_details', 'payment_reference'],
                'campaigns' => ['description', 'contact_info'],
                'organizations' => ['contact_details', 'bank_details'],
                'security_audit_logs' => ['old_values', 'new_values', 'metadata'],
            ],
        ],
        'in_transit' => [
            'min_tls_version' => '1.3',
            'cipher_suites' => [
                'TLS_AES_256_GCM_SHA384',
                'TLS_CHACHA20_POLY1305_SHA256',
                'TLS_AES_128_GCM_SHA256',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging Configuration
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => env('SECURITY_AUDIT_ENABLED', true),
        'retention_days' => [
            'low' => 90,      // Low severity events
            'medium' => 365,  // Medium severity events
            'high' => 2555,   // High severity events (7 years)
            'critical' => 2555, // Critical events (7 years)
        ],
        'real_time_alerts' => [
            'enabled' => env('SECURITY_ALERTS_ENABLED', true),
            'channels' => ['slack', 'email', 'sms'],
            'thresholds' => [
                'failed_logins' => 5,
                'fraud_score' => 80,
                'rate_limit_violations' => 10,
            ],
        ],
        'integrity_checking' => [
            'enabled' => true,
            'algorithm' => 'sha256',
            'chain_validation' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Security
    |--------------------------------------------------------------------------
    */
    'authentication' => [
        'password_policy' => [
            'min_length' => 12,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
            'prevent_reuse' => 12, // Last 12 passwords
            'expiry_days' => 90,
        ],
        'account_lockout' => [
            'max_attempts' => 5,
            'lockout_duration' => 30, // minutes
            'progressive_delays' => true,
        ],
        'multi_factor' => [
            'required_for_admins' => true,
            'required_for_high_value' => true,
            'backup_codes' => 10,
            'totp_window' => 30, // seconds
        ],
        'session_security' => [
            'timeout_minutes' => 480, // 8 hours
            'concurrent_sessions' => 3,
            'secure_cookies' => true,
            'same_site' => 'strict',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Security (PCI DSS)
    |--------------------------------------------------------------------------
    */
    'payment' => [
        'pci_dss' => [
            'version' => '4.0',
            'compliance_level' => 1,
            'tokenization_required' => true,
            'card_data_storage' => false, // NEVER store card data
        ],
        'fraud_detection' => [
            'enabled' => true,
            'velocity_limits' => [
                'per_minute' => 5,
                'per_hour' => 50,
                'per_day' => 200,
                'amount_per_hour' => 50000, // $50,000
            ],
            'risk_thresholds' => [
                'low' => 25,
                'medium' => 50,
                'high' => 75,
                'critical' => 90,
            ],
        ],
        'webhook_security' => [
            'signature_validation' => true,
            'ip_whitelisting' => env('WEBHOOK_IP_WHITELISTING', true),
            'timeout_seconds' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | GDPR Compliance
    |--------------------------------------------------------------------------
    */
    'gdpr' => [
        'enabled' => true,
        'data_retention' => [
            'default_days' => 2555, // 7 years
            'inactive_user_days' => 1095, // 3 years
            'cookie_consent_days' => 365, // 1 year
        ],
        'data_subject_rights' => [
            'access_request_days' => 30,
            'portability_formats' => ['json', 'csv', 'xml'],
            'erasure_exceptions' => ['financial_records', 'audit_logs'],
            'automated_responses' => true,
        ],
        'breach_notification' => [
            'authority_hours' => 72,
            'data_subject_days' => 3,
            'risk_assessment_required' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'enabled' => true,
        'profiles' => [
            'auth' => [
                'attempts' => 5,
                'decay_minutes' => 15,
            ],
            'api' => [
                'attempts' => 60,
                'decay_minutes' => 1,
            ],
            'payment' => [
                'attempts' => 5,
                'decay_minutes' => 1,
            ],
            'admin' => [
                'attempts' => 100,
                'decay_minutes' => 1,
            ],
            'export' => [
                'attempts' => 2,
                'decay_minutes' => 1,
            ],
        ],
        'ip_blocking' => [
            'enabled' => true,
            'violation_threshold' => 50,
            'block_duration_hours' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'hsts' => [
            'enabled' => true,
            'max_age' => 31536000, // 1 year
            'include_subdomains' => true,
            'preload' => true,
        ],
        'csp' => [
            'enabled' => true,
            'report_only' => env('CSP_REPORT_ONLY', false),
            'report_uri' => '/api/v1/security/csp-report',
        ],
        'additional' => [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Alerting
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'real_time' => true,
        'anomaly_detection' => [
            'enabled' => true,
            'ml_threshold' => 0.8,
            'baseline_days' => 30,
        ],
        'metrics' => [
            'collection_interval' => 60, // seconds
            'retention_days' => 90,
            'dashboards' => ['security', 'compliance', 'fraud'],
        ],
        'alerts' => [
            'channels' => [
                'slack' => env('SLACK_SECURITY_WEBHOOK'),
                'email' => explode(',', (string) env('SECURITY_TEAM_EMAILS', '')),
                'sms' => explode(',', (string) env('EMERGENCY_CONTACTS', '')),
            ],
            'escalation' => [
                'critical_to_management' => true,
                'high_severity_threshold' => 5,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance Frameworks
    |--------------------------------------------------------------------------
    */
    'compliance' => [
        'frameworks' => ['gdpr', 'pci_dss', 'soc2', 'iso27001'],
        'reporting' => [
            'automated' => true,
            'schedule' => 'monthly',
            'recipients' => explode(',', (string) env('COMPLIANCE_TEAM_EMAILS', '')),
        ],
        'evidence_collection' => [
            'enabled' => true,
            'retention_years' => 7,
            'storage_encrypted' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Incident Response
    |--------------------------------------------------------------------------
    */
    'incident_response' => [
        'automated_response' => [
            'account_lockout' => true,
            'ip_blocking' => true,
            'session_termination' => true,
        ],
        'notification_matrix' => [
            'critical' => ['security_team', 'management', 'compliance'],
            'high' => ['security_team', 'compliance'],
            'medium' => ['security_team'],
            'low' => ['security_team'],
        ],
        'sla' => [
            'critical_response_minutes' => 15,
            'high_response_minutes' => 60,
            'medium_response_hours' => 4,
            'low_response_hours' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Classification
    |--------------------------------------------------------------------------
    */
    'data_classification' => [
        'levels' => [
            'public' => ['retention_days' => 1095, 'encryption' => false],
            'internal' => ['retention_days' => 1825, 'encryption' => true],
            'confidential' => ['retention_days' => 2555, 'encryption' => true],
            'restricted' => ['retention_days' => 2555, 'encryption' => true],
        ],
        'personal_data' => [
            'categories' => [
                'identity' => ['name', 'email', 'phone', 'address'],
                'financial' => ['payment_info', 'bank_details', 'tax_id'],
                'behavioral' => ['login_history', 'activity_logs', 'preferences'],
                'sensitive' => ['health_data', 'political_opinions', 'religious_beliefs'],
            ],
            'processing_lawful_basis' => [
                'consent',
                'contract',
                'legal_obligation',
                'vital_interests',
                'public_task',
                'legitimate_interests',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Testing
    |--------------------------------------------------------------------------
    */
    'testing' => [
        'vulnerability_scanning' => [
            'schedule' => 'weekly',
            'tools' => ['owasp_zap', 'nikto', 'nmap'],
            'scope' => ['web', 'api', 'infrastructure'],
        ],
        'penetration_testing' => [
            'schedule' => 'quarterly',
            'external_provider' => true,
            'scope' => ['web_app', 'network', 'social_engineering'],
        ],
        'security_assessments' => [
            'code_review' => 'per_release',
            'architecture_review' => 'annually',
            'threat_modeling' => 'per_major_feature',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Emergency Contacts
    |--------------------------------------------------------------------------
    */
    'emergency_contacts' => [
        'security_team' => explode(',', (string) env('SECURITY_TEAM_EMAILS', 'security@acme-corp.com')),
        'compliance_team' => explode(',', (string) env('COMPLIANCE_TEAM_EMAILS', 'compliance@acme-corp.com')),
        'management' => explode(',', (string) env('MANAGEMENT_EMAILS', 'cto@acme-corp.com')),
        'external_ir' => env('EXTERNAL_IR_CONTACT', ''),
        'legal_team' => explode(',', (string) env('LEGAL_TEAM_EMAILS', 'legal@acme-corp.com')),
    ],

];
