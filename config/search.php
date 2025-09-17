<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the unified search system using Meilisearch.
    | This includes settings for caching, pagination, and search behavior.
    |
    */

    'cache' => [
        'enabled' => env('SEARCH_CACHE_ENABLED', true),
        'ttl' => env('SEARCH_CACHE_TTL', 300), // 5 minutes
        'prefix' => env('SEARCH_CACHE_PREFIX', 'acme_search'),
    ],

    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 100,
    ],

    'suggestions' => [
        'min_query_length' => 2,
        'max_suggestions' => 20,
        'default_limit' => 10,
    ],

    'facets' => [
        'max_facet_values' => 50,
        'max_facet_sample_size' => 1000,
    ],

    'models' => [
        'organizations' => [
            'enabled' => true,
            'index_name' => 'organizations',
            'searchable_attributes_weights' => [
                'name' => 3,
                'name_en' => 3,
                'description' => 2,
                'mission' => 2,
                'category' => 2,
                'city' => 1,
                'country' => 1,
            ],
            'facetable_attributes' => [
                'category',
                'country',
                'city',
                'is_verified',
                'is_active',
            ],
        ],

        'users' => [
            'enabled' => true,
            'index_name' => 'users',
            'searchable_attributes_weights' => [
                'name' => 3,
                'first_name' => 3,
                'last_name' => 3,
                'email' => 2,
                'job_title' => 2,
                'department' => 2,
                'phone' => 1,
            ],
            'facetable_attributes' => [
                'department',
                'role',
                'organization_id',
                'is_active',
                'email_verified',
            ],
        ],

        'donations' => [
            'enabled' => true,
            'index_name' => 'donations',
            'searchable_attributes_weights' => [
                'transaction_id' => 3,
                'donor_name' => 3,
                'donor_email' => 2,
                'campaign_title' => 2,
                'organization_name' => 2,
                'notes' => 1,
            ],
            'facetable_attributes' => [
                'status',
                'payment_method',
                'amount_range',
                'campaign_id',
                'anonymous',
                'recurring',
            ],
        ],

        'categories' => [
            'enabled' => true,
            'index_name' => 'categories',
            'searchable_attributes_weights' => [
                'name' => 3,
                'name_en' => 3,
                'name_fr' => 3,
                'name_de' => 3,
                'description' => 2,
                'description_en' => 2,
                'description_fr' => 2,
                'description_de' => 2,
                'slug' => 1,
            ],
            'facetable_attributes' => [
                'status',
                'color',
                'is_active',
                'has_active_campaigns',
            ],
        ],

        'pages' => [
            'enabled' => true,
            'index_name' => 'pages',
            'searchable_attributes_weights' => [
                'title' => 3,
                'title_en' => 3,
                'title_fr' => 3,
                'title_de' => 3,
                'content_plain' => 2,
                'content_plain_en' => 2,
                'content_plain_fr' => 2,
                'content_plain_de' => 2,
                'slug' => 1,
            ],
            'facetable_attributes' => [
                'status',
                'is_published',
                'is_draft',
            ],
        ],
    ],

    'public_access' => [
        'allowed_models' => ['organizations', 'categories', 'pages'],
        'rate_limit' => '60,1', // 60 requests per minute
        'filters' => [
            'organizations' => ['is_verified' => true, 'is_active' => true],
            'categories' => ['is_active' => true],
            'pages' => ['is_published' => true],
        ],
    ],

    'search_analytics' => [
        'enabled' => env('SEARCH_ANALYTICS_ENABLED', true),
        'log_queries' => env('SEARCH_LOG_QUERIES', true),
        'log_no_results' => env('SEARCH_LOG_NO_RESULTS', true),
    ],

    'performance' => [
        'async_indexing' => env('SEARCH_ASYNC_INDEXING', true),
        'bulk_index_size' => env('SEARCH_BULK_INDEX_SIZE', 1000),
        'query_timeout' => env('SEARCH_QUERY_TIMEOUT', 5000), // milliseconds
    ],
];
