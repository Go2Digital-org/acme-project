<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Http\Response;
use Modules\Organization\Application\Request\CreateOrganizationRequest;
use Modules\Organization\Application\Request\UpdateOrganizationRequest;
use Modules\Organization\Application\Request\VerifyOrganizationRequest;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Infrastructure\ApiPlatform\Handler\Processor\CreateOrganizationProcessor;
use Modules\Organization\Infrastructure\ApiPlatform\Handler\Processor\UpdateOrganizationProcessor;
use Modules\Organization\Infrastructure\ApiPlatform\Handler\Processor\VerifyOrganizationProcessor;
use Modules\Organization\Infrastructure\ApiPlatform\Handler\Provider\OrganizationCollectionProvider;
use Modules\Organization\Infrastructure\ApiPlatform\Handler\Provider\OrganizationItemProvider;
use Modules\Shared\Infrastructure\ApiPlatform\Filter\MultiSearchFilter;

#[ApiResource(
    shortName: 'Organization',
    operations: [
        new GetCollection(
            uriTemplate: '/organizations',
            paginationEnabled: true,
            paginationItemsPerPage: 20,
            paginationMaximumItemsPerPage: 100,
            paginationClientItemsPerPage: true,
            provider: OrganizationCollectionProvider::class,
            parameters: [
                'id' => new QueryParameter(key: 'id', filter: EqualsFilter::class),
                'name' => new QueryParameter(key: 'name', filter: PartialSearchFilter::class),
                'category' => new QueryParameter(key: 'category', filter: EqualsFilter::class),
                'verified' => new QueryParameter(key: 'verified', filter: EqualsFilter::class),
                'active' => new QueryParameter(key: 'active', filter: EqualsFilter::class),
                'country' => new QueryParameter(key: 'country', filter: PartialSearchFilter::class),
                'city' => new QueryParameter(key: 'city', filter: PartialSearchFilter::class),
                'search' => new QueryParameter(
                    key: 'search',
                    filter: MultiSearchFilter::class,
                    extraProperties: ['fields' => ['name', 'category', 'city', 'country']],
                ),
                'sort[:property]' => new QueryParameter(key: 'sort[:property]', filter: OrderFilter::class),
                'locale' => new QueryParameter(key: 'locale', filter: EqualsFilter::class),
            ],
        ),
        new Get(
            uriTemplate: '/organizations/{id}',
            provider: OrganizationItemProvider::class,
        ),
        new Post(
            uriTemplate: '/organizations',
            status: Response::HTTP_CREATED,
            processor: CreateOrganizationProcessor::class,
            rules: CreateOrganizationRequest::class,
        ),
        new Put(
            uriTemplate: '/organizations/{id}',
            status: Response::HTTP_OK,
            processor: UpdateOrganizationProcessor::class,
            rules: UpdateOrganizationRequest::class,
        ),
        new Patch(
            uriTemplate: '/organizations/{id}/verify',
            status: Response::HTTP_OK,
            processor: VerifyOrganizationProcessor::class,
            rules: VerifyOrganizationRequest::class,
        ),
    ],
    middleware: ['auth:sanctum', 'api.locale'],
)]
class OrganizationResource
{
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?string $registration_number = null,
        public ?string $tax_id = null,
        public ?string $category = null,
        public ?string $website = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $country = null,
        public ?bool $verified = null,
        public ?bool $active = null,
        public ?string $verified_at = null,
        public ?bool $can_create_campaigns = null,
        public ?string $status = null,
        public ?string $status_color = null,
        public ?string $status_label = null,
        public ?bool $is_eligible_for_verification = null,
        public ?string $created_at = null,
        public ?string $updated_at = null,
    ) {}

    public static function fromModel(Organization $organization): self
    {
        return new self(
            id: $organization->id,
            name: $organization->getName(),
            registration_number: $organization->registration_number,
            tax_id: $organization->tax_id,
            category: $organization->category,
            website: $organization->website,
            email: $organization->email,
            phone: $organization->phone,
            address: $organization->address,
            city: $organization->city,
            country: $organization->country,
            verified: $organization->is_verified,
            active: $organization->status === 'active',
            verified_at: $organization->verification_date?->toDateTimeString(),
            can_create_campaigns: $organization->canCreateCampaigns(),
            status: $organization->status,
            status_color: $organization->getStatusColor(),
            status_label: $organization->getStatusLabel(),
            is_eligible_for_verification: $organization->isEligibleForVerification(),
            created_at: $organization->created_at?->toDateTimeString(),
            updated_at: $organization->updated_at?->toDateTimeString(),
        );
    }
}
