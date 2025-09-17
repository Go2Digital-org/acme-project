<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Filament Admin Panel Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used in the Filament admin panel.
    |
    */

    // Navigation Groups
    'navigation_groups' => [
        'csr_management' => 'CSR Management',
        'content_management' => 'Content Management',
        'system' => 'System',
        'reports' => 'Reports',
    ],

    // Resources
    'resources' => [
        'campaign' => [
            'label' => 'Campaign',
            'plural' => 'Campaigns',
            'navigation_label' => 'Campaigns',
            'sections' => [
                'campaign_information' => 'Campaign Information',
                'campaign_information_desc' => 'Basic campaign details and content',
                'financial_details' => 'Financial Details',
                'financial_details_desc' => 'Goal amount and corporate matching settings',
                'timeline_organization' => 'Timeline & Organization',
                'timeline_organization_desc' => 'Campaign dates and organizational details',
                'media_assets' => 'Media & Assets',
                'media_assets_desc' => 'Campaign images and media files',
            ],
            'fields' => [
                'title' => 'Title',
                'description' => 'Description',
                'slug' => 'Slug',
                'category' => 'Category',
                'visibility' => 'Visibility',
                'goal_amount' => 'Goal Amount',
                'current_amount' => 'Current Amount',
                'has_corporate_matching' => 'Enable Corporate Matching',
                'corporate_matching_rate' => 'Matching Rate',
                'max_corporate_matching' => 'Max Corporate Matching',
                'start_date' => 'Start Date',
                'end_date' => 'End Date',
                'organization' => 'Organization',
                'employee' => 'Campaign Manager',
                'status' => 'Status',
                'featured_image' => 'Featured Image',
            ],
        ],
        'organization' => [
            'label' => 'Organization',
            'plural' => 'Organizations',
            'navigation_label' => 'Organizations',
            'sections' => [
                'organization_information' => 'Organization Information',
                'organization_information_desc' => 'Basic organization details and contact information',
                'legal_registration' => 'Legal & Registration',
                'legal_registration_desc' => 'Legal information and registration details',
                'contact_information' => 'Contact Information',
                'contact_information_desc' => 'Contact details and physical location',
            ],
            'fields' => [
                'name' => 'Name',
                'description' => 'Description',
                'mission' => 'Mission',
                'category' => 'Category',
                'registration_number' => 'Registration Number',
                'tax_id' => 'Tax ID',
                'is_verified' => 'Verified Organization',
                'verification_date' => 'Verification Date',
                'is_active' => 'Active Status',
                'website' => 'Website',
                'email' => 'Email',
                'phone' => 'Phone',
                'address' => 'Address',
                'city' => 'City',
                'postal_code' => 'Postal Code',
                'country' => 'Country',
                'logo_url' => 'Logo',
            ],
        ],
        'donation' => [
            'label' => 'Donation',
            'plural' => 'Donations',
            'navigation_label' => 'Donations',
            'sections' => [
                'donation_information' => 'Donation Information',
                'donation_information_desc' => 'Basic donation details and transaction info',
                'payment_details' => 'Payment Details',
                'payment_details_desc' => 'Payment method and gateway information',
                'additional_settings' => 'Additional Settings',
                'additional_settings_desc' => 'Privacy and recurring donation settings',
                'timestamps' => 'Timestamps',
                'timestamps_desc' => 'Important dates and processing timeline',
            ],
            'fields' => [
                'campaign' => 'Campaign',
                'employee' => 'Donor',
                'amount' => 'Amount',
                'currency' => 'Currency',
                'payment_method' => 'Payment Method',
                'payment_gateway' => 'Payment Gateway',
                'transaction_id' => 'Transaction ID',
                'status' => 'Status',
                'anonymous' => 'Anonymous Donation',
                'recurring' => 'Recurring Donation',
                'recurring_frequency' => 'Recurring Frequency',
                'notes' => 'Notes',
                'donated_at' => 'Donation Date',
                'processed_at' => 'Processing Date',
                'completed_at' => 'Completion Date',
            ],
        ],
        'page' => [
            'label' => 'Page',
            'plural' => 'Pages',
            'navigation_label' => 'Pages',
            'sections' => [
                'page_information' => 'Page Information',
                'page_information_desc' => 'Basic page details and settings',
                'page_content' => 'Page Content',
                'page_content_desc' => 'Page content in multiple languages',
                'seo_meta' => 'SEO & Meta',
                'seo_meta_desc' => 'Search engine optimization and metadata',
                'seo_preview' => 'SEO Preview',
                'seo_preview_desc' => 'How your page will appear in search results',
            ],
            'fields' => [
                'title' => 'Page Title',
                'slug' => 'Slug',
                'status' => 'Status',
                'template' => 'Template',
                'order' => 'Order',
                'content' => 'Content',
                'meta_description' => 'Meta Description',
                'meta_keywords' => 'Meta Keywords',
            ],
        ],
        'user' => [
            'label' => 'User',
            'plural' => 'Users',
            'navigation_label' => 'Users',
        ],
        'role' => [
            'label' => 'Role',
            'plural' => 'Roles',
            'navigation_label' => 'Roles',
        ],
    ],

    // Common Actions
    'actions' => [
        'create' => 'Create',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'view' => 'View',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'filter' => 'Filter',
        'search' => 'Search',
        'export' => 'Export',
        'import' => 'Import',
        'refresh' => 'Refresh',
        'bulk_delete' => 'Delete Selected',
    ],

    // Common Messages
    'messages' => [
        'created' => 'Record created successfully',
        'updated' => 'Record updated successfully',
        'deleted' => 'Record deleted successfully',
        'saved' => 'Changes saved successfully',
        'error' => 'An error occurred',
        'confirm_delete' => 'Are you sure you want to delete this record?',
        'no_records' => 'No records found',
    ],

    // Status Labels
    'statuses' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'paused' => 'Paused',
    ],

    // Categories
    'categories' => [
        'education' => 'Education',
        'health' => 'Health & Medical',
        'environment' => 'Environment',
        'community' => 'Community Development',
        'disaster_relief' => 'Disaster Relief',
        'poverty' => 'Poverty Alleviation',
        'animal_welfare' => 'Animal Welfare',
        'human_rights' => 'Human Rights',
        'arts_culture' => 'Arts & Culture',
        'sports' => 'Sports & Recreation',
        'charity' => 'Charity',
        'non_profit' => 'Non-Profit',
        'ngo' => 'NGO',
        'foundation' => 'Foundation',
        'other' => 'Other',
    ],

    // Visibility Options
    'visibility' => [
        'public' => 'Public - Visible to all',
        'internal' => 'Internal - Company employees only',
        'private' => 'Private - Invited users only',
    ],

    // Payment Methods
    'payment_methods' => [
        'credit_card' => 'Credit Card',
        'bank_transfer' => 'Bank Transfer',
        'paypal' => 'PayPal',
        'stripe' => 'Stripe',
        'mollie' => 'Mollie',
    ],

    // Recurring Frequencies
    'recurring_frequencies' => [
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
    ],

    // Helper Texts
    'helpers' => [
        'slug_auto_generated' => 'Will be auto-generated if left empty',
        'current_amount_readonly' => 'Updated automatically as donations are received',
        'corporate_matching_help' => 'Company will match employee donations',
        'matching_rate_help' => 'Percentage of employee donation to match (100% = 1:1 matching)',
        'max_matching_help' => 'Maximum total corporate matching amount (leave empty for unlimited)',
        'anonymous_help' => 'Hide donor name from public displays',
        'recurring_help' => 'Set up automatic recurring donations',
        'featured_image_help' => 'Recommended size: 1200x675px (16:9 aspect ratio)',
        'meta_description_help' => 'Brief description for search engines (160 characters max)',
        'keywords_help' => 'Keywords separated by commas',
    ],

    // Payment Gateway Configuration
    'payment_gateway_configuration_guide' => 'Payment Gateway Configuration Guide',
    'stripe_configuration' => 'Stripe Configuration',
    'mollie_configuration' => 'Mollie Configuration',
    'security_notes' => 'Security Notes',
    'api_key' => 'API Key',
    'webhook_secret' => 'Webhook Secret',
    'webhook_url' => 'Webhook URL',
    'publishable_key' => 'Publishable Key',
    'your_secret_api_key_stripe' => 'Your secret API key from Stripe dashboard',
    'endpoint_signing_secret' => 'Endpoint signing secret for webhook validation',
    'your_application_webhook_endpoint' => 'Your application webhook endpoint',
    'publishable_key_client_side' => 'Your publishable key for client-side integration',
    'your_live_test_api_key_mollie' => 'Your live or test API key from Mollie dashboard',
    'secret_for_webhook_validation' => 'Secret for webhook validation',
    'all_sensitive_data_encrypted' => 'All sensitive data is automatically encrypted in the database',
    'test_configuration_before_activating' => 'Test your configuration before activating the gateway',
    'use_test_mode_during_development' => 'Use test mode during development and testing',
    'keep_api_keys_secure' => 'Keep your API keys secure and rotate them regularly',
];
