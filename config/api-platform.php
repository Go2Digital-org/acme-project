<?php

declare(strict_types=1);

use ApiPlatform\Metadata\UrlGeneratorInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Modules\Shared\Domain\Exception\ApiException;

return [
    'title' => 'ACME Corp CSR API Platform',
    'description' => 'Enterprise API for Employee Donations and CSR Campaigns',
    'version' => '1.0.0',

    'routes' => [
        // Use Laravel's api middleware group to enable Sanctum stateful authentication
        'middleware' => ['api'],
    ],

    'resources' => [
        base_path('modules'),
    ],

    'formats' => [
        'jsonld' => ['application/ld+json'],
        'json' => ['application/json'],
    ],

    'patch_formats' => [
        'json' => ['application/merge-patch+json'],
    ],

    'docs_formats' => [
        'jsonld' => ['application/ld+json'],
        'jsonopenapi' => ['application/vnd.openapi+json'],
        'html' => ['text/html'],
    ],

    'error_formats' => [
        'jsonproblem' => ['application/problem+json'],
    ],

    'defaults' => [
        'pagination_enabled' => true,
        'pagination_partial' => false,
        'pagination_client_enabled' => true,
        'pagination_client_items_per_page' => true,
        'pagination_client_partial' => false,
        'pagination_items_per_page' => 20,
        'pagination_maximum_items_per_page' => 100,
        'route_prefix' => '/api',
        'middleware' => ['auth:sanctum', 'api.locale', 'throttle:api', 'api.performance'],
    ],

    'pagination' => [
        'page_parameter_name' => 'page',
        'enabled_parameter_name' => 'pagination',
        'items_per_page_parameter_name' => 'itemsPerPage',
        'partial_parameter_name' => 'partial',
    ],

    'graphql' => [
        'enabled' => false,
        'nesting_separator' => '__',
        'introspection' => ['enabled' => false],
    ],

    'exception_to_status' => [
        AuthenticationException::class => 401,
        AuthorizationException::class => 403,
        ApiException::class => 400,
    ],

    'swagger_ui' => [
        'enabled' => true,
        'apiKeys' => [
            'sanctum' => [
                'type' => 'Bearer',
                'name' => 'Authentication Token',
                'in' => 'header',
            ],
        ],
    ],

    'url_generation_strategy' => UrlGeneratorInterface::ABS_PATH,

    'serializer' => [
        'hydra_prefix' => true,
        'datetime_format' => DateTimeInterface::RFC3339,
    ],
];
