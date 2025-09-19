<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Http\Response;
use Modules\Shared\Infrastructure\ApiPlatform\Filter\MultiSearchFilter;
use Modules\User\Infrastructure\ApiPlatform\Handler\Processor\DeleteUserProcessor;
use Modules\User\Infrastructure\ApiPlatform\Handler\Processor\UpdateUserProcessor;
use Modules\User\Infrastructure\ApiPlatform\Handler\Provider\UserCollectionProvider;
use Modules\User\Infrastructure\ApiPlatform\Handler\Provider\UserItemProvider;
use Modules\User\Infrastructure\Laravel\Models\User;

#[ApiResource(
    shortName: 'User',
    operations: [
        new GetCollection(
            uriTemplate: '/users',
            paginationEnabled: true,
            paginationItemsPerPage: 20,
            paginationMaximumItemsPerPage: 100,
            paginationClientItemsPerPage: true,
            security: "is_granted('ROLE_USER')",
            provider: UserCollectionProvider::class,
            parameters: [
                'id' => new QueryParameter(key: 'id', filter: EqualsFilter::class),
                'name' => new QueryParameter(key: 'name', filter: PartialSearchFilter::class),
                'email' => new QueryParameter(key: 'email', filter: PartialSearchFilter::class),
                'job_title' => new QueryParameter(key: 'job_title', filter: PartialSearchFilter::class),
                'search' => new QueryParameter(
                    key: 'search',
                    filter: MultiSearchFilter::class,
                    extraProperties: ['fields' => ['name', 'email', 'job_title']],
                ),
                'sort[:property]' => new QueryParameter(key: 'sort[:property]', filter: OrderFilter::class),
            ],
        ),
        new Get(
            uriTemplate: '/users/{id}',
            security: "is_granted('ROLE_USER') or object.id == user.id",
            provider: UserItemProvider::class,
        ),
        new Put(
            uriTemplate: '/users/{id}',
            status: Response::HTTP_OK,
            security: "is_granted('ROLE_USER') or object.id == user.id",
            processor: UpdateUserProcessor::class,
        ),
        new Delete(
            uriTemplate: '/users/{id}',
            status: Response::HTTP_NO_CONTENT,
            security: "is_granted('ROLE_USER')",
            processor: DeleteUserProcessor::class,
        ),
    ],
    middleware: ['auth:sanctum', 'api.locale'],
)]
class UserResource
{
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $department = null,
        public ?string $jobTitle = null,
        public ?string $userId = null,
        public ?string $job_title = null,
        public ?string $manager_email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $hire_date = null,
        public ?string $preferred_language = null,
        public ?string $timezone = null,
        public ?string $profile_photo_url = null,
        /** @var array<int, string>|null */
        public ?array $roles = null,
        public ?bool $emailVerified = null,
        public ?bool $email_verified = null,
        public ?string $last_login_at = null,
        public ?bool $account_locked = null,
        public ?string $createdAt = null,
        public ?string $created_at = null,
        public ?string $updatedAt = null,
        public ?string $updated_at = null,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            department: $user->department ?? null,
            jobTitle: $user->job_title,
            userId: $user->user_id ?? 'USR001',
            job_title: $user->job_title,
            manager_email: $user->manager_email,
            phone: $user->phone,
            address: $user->address,
            hire_date: $user->hire_date,
            preferred_language: $user->preferred_language,
            timezone: $user->timezone,
            profile_photo_url: $user->profile_photo_url,
            roles: array_values($user->getRoleNames()->map(fn ($role): string => (string) $role)->toArray()),
            emailVerified: $user->email_verified_at !== null,
            email_verified: $user->email_verified_at !== null,
            last_login_at: $user->last_login_at?->toDateTimeString(),
            account_locked: (bool) $user->account_locked,
            createdAt: $user->created_at?->toDateTimeString(),
            created_at: $user->created_at?->toDateTimeString(),
            updatedAt: $user->updated_at?->toDateTimeString(),
            updated_at: $user->updated_at?->toDateTimeString(),
        );
    }
}
