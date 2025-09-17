<?php

declare(strict_types=1);

return [
    'title' => 'Style Guide',
    'page_title' => 'ACME Corp Design System',
    'page_subtitle' => 'A comprehensive guide to our CSR platform design system, typography, and components',

    'typography' => [
        'title' => 'Typography',
        'heading_1' => [
            'label' => 'Heading 1 - 5xl',
            'example' => 'Making a difference together',
        ],
        'heading_2' => [
            'label' => 'Heading 2 - 4xl',
            'example' => 'Corporate Social Responsibility',
        ],
        'heading_3' => [
            'label' => 'Heading 3 - 3xl',
            'example' => 'Featured Campaigns',
        ],
        'heading_4' => [
            'label' => 'Heading 4 - 2xl',
            'example' => 'Impact Areas',
        ],
        'heading_5' => [
            'label' => 'Heading 5 - xl',
            'example' => 'Education Initiative',
        ],
        'body_text' => [
            'label' => 'Body Text - base',
            'example' => 'Join our mission to create positive change in communities worldwide through strategic corporate social responsibility initiatives.',
        ],
        'small_text' => [
            'label' => 'Small Text',
            'example' => 'Supporting causes that matter to our employees and communities.',
        ],
        'extra_small_text' => [
            'label' => 'Extra Small Text',
            'example' => 'Campaign details and fine print information.',
        ],
    ],

    'colors' => [
        'title' => 'Color Palette',
        'primary' => [
            'title' => 'Primary Colors',
            'primary_blue' => [
                'name' => 'Primary Blue',
                'description' => 'Used for CTAs and links',
            ],
            'secondary' => [
                'name' => 'Secondary',
                'description' => 'Accent color',
            ],
        ],
        'status' => [
            'title' => 'Status Colors',
            'success' => [
                'name' => 'Success',
                'description' => 'Campaign completed',
            ],
            'warning' => [
                'name' => 'Warning',
                'description' => 'Campaign ending soon',
            ],
            'urgent' => [
                'name' => 'Urgent',
                'description' => 'Critical needs',
            ],
        ],
        'background' => [
            'title' => 'Background Colors',
            'page_background' => [
                'name' => 'Page Background',
                'description' => 'Main app background',
            ],
            'card_background' => [
                'name' => 'Card Background',
                'description' => 'Content cards',
            ],
            'section_background' => [
                'name' => 'Section Background',
                'description' => 'Alternate sections',
            ],
        ],
    ],

    'buttons' => [
        'title' => 'Button Components',
        'primary' => [
            'title' => 'Primary Buttons',
            'small_primary' => 'Small Primary',
            'medium_primary' => 'Medium Primary',
            'large_with_icon' => 'Large with Icon',
        ],
        'secondary' => [
            'title' => 'Secondary Buttons',
            'secondary' => 'Secondary',
            'outline' => 'Outline',
            'ghost' => 'Ghost',
        ],
        'status' => [
            'title' => 'Status & Action Buttons',
            'donate_now' => 'Donate Now',
            'urgent' => 'Urgent',
            'cancel' => 'Cancel',
        ],
    ],

    'campaigns' => [
        'title' => 'Campaign Cards',
        'featured_card' => [
            'title' => 'Featured Campaign Card',
            'category' => 'Education',
            'funding_status' => '72% Funded',
            'name' => 'Global Education Initiative',
            'description' => 'Providing quality education resources to underserved communities worldwide.',
            'raised' => '$72,000 raised',
            'goal' => '$100,000 goal',
        ],
        'impact_stats' => [
            'title' => 'Impact Statistics Cards',
            'total_raised' => [
                'label' => 'Total Raised',
                'value' => '$2.4M',
            ],
            'active_campaigns' => [
                'label' => 'Active Campaigns',
                'value' => '147',
            ],
            'employees_participating' => [
                'label' => 'Employees Participating',
                'value' => '12,847',
            ],
        ],
    ],

    'forms' => [
        'title' => 'Form Components',
        'campaign_name' => [
            'label' => 'Campaign Name',
            'placeholder' => 'Enter campaign name',
        ],
        'campaign_category' => [
            'label' => 'Campaign Category',
            'placeholder' => 'Choose category',
            'options' => [
                'education' => 'Education',
                'healthcare' => 'Healthcare',
                'environment' => 'Environment',
                'community' => 'Community',
            ],
        ],
        'campaign_description' => [
            'label' => 'Campaign Description',
            'placeholder' => 'Describe your campaign...',
        ],
        'terms_agreement' => 'I agree to the terms and conditions',
    ],

    'icons' => [
        'title' => 'CSR Platform Icons',
        'donate' => 'Donate',
        'education' => 'Education',
        'healthcare' => 'Healthcare',
        'environment' => 'Environment',
        'community' => 'Community',
        'support' => 'Support',
        'impact' => 'Impact',
        'global' => 'Global',
    ],

    'principles' => [
        'title' => 'Design Principles',
        'clean_professional' => [
            'title' => 'Clean & Professional',
            'items' => [
                'NO gradients - use solid backgrounds only',
                'Clean typography hierarchy',
                'Consistent spacing and alignment',
                'Professional color palette',
            ],
        ],
        'accessible_inclusive' => [
            'title' => 'Accessible & Inclusive',
            'items' => [
                'WCAG 2.1 AA compliance',
                'High contrast ratios',
                'Clear focus indicators',
                'Screen reader optimized',
            ],
        ],
        'corporate_standards' => [
            'title' => 'Corporate Standards',
            'items' => [
                'Consistent with ACME branding',
                'Professional appearance',
                'Trust-building design elements',
                'Clear information hierarchy',
            ],
        ],
        'user_centered' => [
            'title' => 'User-Centered',
            'items' => [
                'Clear calls-to-action',
                'Intuitive navigation',
                'Mobile-first responsive design',
                'Fast loading performance',
            ],
        ],
    ],
];
