<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default search connection that gets used while
    | using Laravel Scout. This connection is used when syncing all models
    | to the search service. You should adjust this based on your needs.
    |
    | Supported: "algolia", "meilisearch", "typesense",
    |            "database", "collection", "null"
    |
    */

    'driver' => env('SCOUT_DRIVER', 'meilisearch'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | Here you may specify a prefix that will be applied to all search index
    | names used by Scout. This prefix may be useful if you have multiple
    | "tenants" or applications sharing the same search infrastructure.
    |
    */

    'prefix' => env('SCOUT_PREFIX', 'acme_'),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that sync your data
    | with your search engines are queued. When this is set to "true" then
    | all automatic data syncing will get queued for better performance.
    |
    */

    'queue' => env('SCOUT_QUEUE', true),

    /*
    |--------------------------------------------------------------------------
    | Database Transactions
    |--------------------------------------------------------------------------
    |
    | This configuration option determines if your data will only be synced
    | with your search indexes after every open database transaction has
    | been committed, thus preventing any discarded data from syncing.
    |
    */

    'after_commit' => true,

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    |
    | These options allow you to control the maximum chunk size when you are
    | mass importing data into the search engine. This allows you to fine
    | tune each of these chunk sizes based on the power of the servers.
    |
    */

    'chunk' => [
        'searchable' => 1000,
        'unsearchable' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | This option allows to control whether to keep soft deleted records in
    | the search indexes. Maintaining soft deleted records can be useful
    | if your application still needs to search for the records later.
    |
    */

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Identify User
    |--------------------------------------------------------------------------
    |
    | This option allows you to control whether to notify the search engine
    | of the user performing the search. This is sometimes useful if the
    | engine supports any analytics based on this application's users.
    |
    | Supported engines: "algolia"
    |
    */

    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Algolia Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Algolia settings. Algolia is a cloud hosted
    | search engine which works great with Scout out of the box. Just plug
    | in your application ID and admin API key to get started searching.
    |
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
        'index-settings' => [
            // 'users' => [
            //     'searchableAttributes' => ['id', 'name', 'email'],
            //     'attributesForFaceting'=> ['filterOnly(email)'],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Meilisearch settings. Meilisearch is an open
    | source search engine with minimal configuration. Below, you can state
    | the host and key information for your own Meilisearch installation.
    |
    | See: https://www.meilisearch.com/docs/learn/configuration/instance_options#all-instance-options
    |
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            'campaigns' => [
                'filterableAttributes' => [
                    'id',
                    'status',
                    'category',
                    'category_id',
                    'category_name',
                    'organization_id',
                    'user_id',
                    'employee_id',
                    'start_date',
                    'end_date',
                    'created_at',
                    'updated_at',
                    'goal_amount',
                    'current_amount',
                    'goal_percentage',
                    'visibility',
                    'is_featured',
                    'is_active',
                    'has_reached_goal',
                ],
                'sortableAttributes' => [
                    'created_at',
                    'updated_at',
                    'start_date',
                    'end_date',
                    'goal_amount',
                    'current_amount',
                    'goal_percentage',
                    'donations_count',
                    'is_featured',
                    'title',
                ],
                'searchableAttributes' => [
                    'title',
                    'description',
                    'category',
                    'category_name',
                    'organization_name',
                    'creator_name',
                ],
                'displayedAttributes' => ['*'],
                'rankingRules' => [
                    'words',
                    'typo',
                    'proximity',
                    'attribute',
                    'sort',
                    'exactness',
                    'current_amount:desc',
                ],
            ],
            'donations' => [
                'filterableAttributes' => [
                    'id',
                    'campaign_id',
                    'employee_id',
                    'status',
                    'payment_method',
                    'currency',
                    'amount',
                    'donated_at',
                    'is_anonymous',
                ],
                'sortableAttributes' => [
                    'amount',
                    'donated_at',
                    'created_at',
                    'processed_at',
                ],
                'searchableAttributes' => [
                    'transaction_id',
                    'notes',
                    'donor_name',
                    'donor_email',
                ],
            ],
            'users' => [
                'filterableAttributes' => [
                    'id',
                    'role',
                    'department',
                    'organization_id',
                    'is_active',
                    'employee_id',
                ],
                'sortableAttributes' => [
                    'name',
                    'created_at',
                    'last_login_at',
                ],
                'searchableAttributes' => [
                    'name',
                    'first_name',
                    'last_name',
                    'email',
                    'department',
                    'job_title',
                ],
            ],
            'organizations' => [
                'filterableAttributes' => [
                    'id',
                    'category',
                    'is_verified',
                    'is_active',
                    'country',
                    'city',
                ],
                'sortableAttributes' => [
                    'name',
                    'created_at',
                    'verification_date',
                    'campaigns_count',
                ],
                'searchableAttributes' => [
                    'name',
                    'description',
                    'mission',
                    'category',
                    'address',
                    'city',
                    'country',
                ],
            ],
            'employees' => [
                'filterableAttributes' => [
                    'id',
                    'status',
                    'employment_type',
                    'department',
                    'manager_id',
                    'organization_id',
                    'hire_date',
                ],
                'sortableAttributes' => [
                    'first_name',
                    'last_name',
                    'full_name',
                    'hire_date',
                    'created_at',
                    'updated_at',
                ],
                'searchableAttributes' => [
                    'first_name',
                    'last_name',
                    'full_name',
                    'email',
                    'job_title',
                    'department',
                ],
            ],
            'categories' => [
                'filterableAttributes' => [
                    'id',
                    'status',
                    'is_active',
                    'color',
                    'sort_order',
                    'has_active_campaigns',
                ],
                'sortableAttributes' => [
                    'name',
                    'sort_order',
                    'campaigns_count',
                    'created_at',
                    'updated_at',
                ],
                'searchableAttributes' => [
                    'name',
                    'name_en',
                    'name_fr',
                    'name_de',
                    'description',
                    'description_en',
                    'description_fr',
                    'description_de',
                    'slug',
                ],
                'displayedAttributes' => ['*'],
                'rankingRules' => [
                    'words',
                    'typo',
                    'proximity',
                    'attribute',
                    'sort',
                    'exactness',
                    'sort_order:asc',
                ],
            ],
            'pages' => [
                'filterableAttributes' => [
                    'id',
                    'status',
                    'is_published',
                    'is_draft',
                    'order',
                ],
                'sortableAttributes' => [
                    'title',
                    'order',
                    'created_at',
                    'updated_at',
                ],
                'searchableAttributes' => [
                    'title',
                    'title_en',
                    'title_fr',
                    'title_de',
                    'content_plain',
                    'content_plain_en',
                    'content_plain_fr',
                    'content_plain_de',
                    'slug',
                ],
                'displayedAttributes' => ['*'],
                'rankingRules' => [
                    'words',
                    'typo',
                    'proximity',
                    'attribute',
                    'sort',
                    'exactness',
                    'order:asc',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Typesense Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Typesense settings. Typesense is an open
    | source search engine using minimal configuration. Below, you will
    | state the host, key, and schema configuration for the instance.
    |
    */

    'typesense' => [
        'client-settings' => [
            'api_key' => env('TYPESENSE_API_KEY', 'xyz'),
            'nodes' => [
                [
                    'host' => env('TYPESENSE_HOST', 'localhost'),
                    'port' => env('TYPESENSE_PORT', '8108'),
                    'path' => env('TYPESENSE_PATH', ''),
                    'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
                ],
            ],
            'nearest_node' => [
                'host' => env('TYPESENSE_HOST', 'localhost'),
                'port' => env('TYPESENSE_PORT', '8108'),
                'path' => env('TYPESENSE_PATH', ''),
                'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
            ],
            'connection_timeout_seconds' => env('TYPESENSE_CONNECTION_TIMEOUT_SECONDS', 2),
            'healthcheck_interval_seconds' => env('TYPESENSE_HEALTHCHECK_INTERVAL_SECONDS', 30),
            'num_retries' => env('TYPESENSE_NUM_RETRIES', 3),
            'retry_interval_seconds' => env('TYPESENSE_RETRY_INTERVAL_SECONDS', 1),
        ],
        // 'max_total_results' => env('TYPESENSE_MAX_TOTAL_RESULTS', 1000),
        'model-settings' => [
            // User::class => [
            //     'collection-schema' => [
            //         'fields' => [
            //             [
            //                 'name' => 'id',
            //                 'type' => 'string',
            //             ],
            //             [
            //                 'name' => 'name',
            //                 'type' => 'string',
            //             ],
            //             [
            //                 'name' => 'created_at',
            //                 'type' => 'int64',
            //             ],
            //         ],
            //         'default_sorting_field' => 'created_at',
            //     ],
            //     'search-parameters' => [
            //         'query_by' => 'name'
            //     ],
            // ],
        ],
    ],

];
