<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Illuminate\Http\Response;
use Modules\Auth\Application\Request\LoginFormRequest;
use Modules\Auth\Application\Request\RegisterFormRequest;
use Modules\Auth\Infrastructure\ApiPlatform\Handler\Processor\LoginProcessor;
use Modules\Auth\Infrastructure\ApiPlatform\Handler\Processor\LogoutProcessor;
use Modules\Auth\Infrastructure\ApiPlatform\Handler\Processor\RegisterProcessor;
use Modules\Auth\Infrastructure\ApiPlatform\Handler\Provider\UserProfileProvider;

#[ApiResource(
    shortName: 'Authentication',
    operations: [
        new Post(
            uriTemplate: '/auth/login',
            status: Response::HTTP_OK,
            processor: LoginProcessor::class,
            rules: LoginFormRequest::class,
            middleware: ['api.locale', 'throttle:api', 'api.performance'],
        ),

        new Post(
            uriTemplate: '/auth/register',
            status: Response::HTTP_CREATED,
            processor: RegisterProcessor::class,
            rules: RegisterFormRequest::class,
            middleware: ['api.locale', 'throttle:api', 'api.performance'],
        ),

        new Post(
            uriTemplate: '/auth/logout',
            status: Response::HTTP_OK,
            processor: LogoutProcessor::class,
            middleware: ['auth:sanctum', 'api.locale', 'throttle:api', 'api.performance'],
        ),

        new Get(
            uriTemplate: '/auth/user',
            status: Response::HTTP_OK,
            provider: UserProfileProvider::class,
            middleware: ['auth:sanctum', 'api.locale', 'throttle:api', 'api.performance'],
        ),
    ],
)]
class AuthenticationResource
{
    public function __construct(
        public ?string $email = null,
        public ?string $password = null,
        public ?string $password_confirmation = null,
        public ?string $name = null,
        /** @var array<string, mixed>|null */
        public ?array $user = null,
        public ?string $token = null,
        public ?string $token_type = null,
        public ?string $message = null,
    ) {}
}
