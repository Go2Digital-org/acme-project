<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Laravel\Eloquent\Filter\DateFilter;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Http\Response;
use Modules\Donation\Application\Request\CancelDonationRequest;
use Modules\Donation\Application\Request\CreateDonationRequest;
use Modules\Donation\Application\Request\RefundDonationRequest;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Infrastructure\ApiPlatform\Handler\Processor\CancelDonationProcessor;
use Modules\Donation\Infrastructure\ApiPlatform\Handler\Processor\CreateDonationProcessor;
use Modules\Donation\Infrastructure\ApiPlatform\Handler\Processor\RefundDonationProcessor;
use Modules\Donation\Infrastructure\ApiPlatform\Handler\Provider\DonationCollectionProvider;
use Modules\Donation\Infrastructure\ApiPlatform\Handler\Provider\DonationItemProvider;

#[ApiResource(
    shortName: 'Donation',
    operations: [
        new GetCollection(
            uriTemplate: '/donations',
            paginationEnabled: true,
            paginationItemsPerPage: 20,
            paginationMaximumItemsPerPage: 100,
            paginationClientItemsPerPage: true,
            provider: DonationCollectionProvider::class,
            parameters: [
                'id' => new QueryParameter(key: 'id', filter: EqualsFilter::class),
                'campaign_id' => new QueryParameter(key: 'campaign_id', filter: EqualsFilter::class),
                'user_id' => new QueryParameter(key: 'user_id', filter: EqualsFilter::class),
                'status' => new QueryParameter(key: 'status', filter: EqualsFilter::class),
                'payment_method' => new QueryParameter(key: 'payment_method', filter: EqualsFilter::class),
                'anonymous' => new QueryParameter(key: 'anonymous', filter: EqualsFilter::class),
                'recurring' => new QueryParameter(key: 'recurring', filter: EqualsFilter::class),
                'sort[:property]' => new QueryParameter(key: 'sort[:property]', filter: OrderFilter::class),
                'donated_at' => new QueryParameter(key: 'donated_at', filter: DateFilter::class),
                'created_at' => new QueryParameter(key: 'created_at', filter: DateFilter::class),
                'updated_at' => new QueryParameter(key: 'updated_at', filter: DateFilter::class),
                'locale' => new QueryParameter(key: 'locale', filter: EqualsFilter::class),
            ],
        ),
        new Get(
            uriTemplate: '/donations/{id}',
            provider: DonationItemProvider::class,
        ),
        new Post(
            uriTemplate: '/donations',
            status: Response::HTTP_CREATED,
            processor: CreateDonationProcessor::class,
            rules: CreateDonationRequest::class,
        ),
        new Patch(
            uriTemplate: '/donations/{id}/cancel',
            status: Response::HTTP_OK,
            processor: CancelDonationProcessor::class,
            rules: CancelDonationRequest::class,
        ),
        new Patch(
            uriTemplate: '/donations/{id}/refund',
            status: Response::HTTP_OK,
            processor: RefundDonationProcessor::class,
            rules: RefundDonationRequest::class,
        ),
    ],
    middleware: ['auth:sanctum', 'api.locale'],
)]
class DonationResource
{
    public function __construct(
        public ?int $id = null,
        public ?int $campaign_id = null,
        public ?string $campaign_title = null,
        public ?int $user_id = null,
        public ?string $employee_name = null,
        public ?float $amount = null,
        public ?string $currency = null,
        public ?string $payment_method = null,
        public ?string $payment_gateway = null,
        public ?string $transaction_id = null,
        public ?string $gateway_response_id = null,
        public ?string $status = null,
        public ?bool $anonymous = null,
        public ?bool $recurring = null,
        public ?string $recurring_frequency = null,
        public ?string $donated_at = null,
        public ?string $processed_at = null,
        public ?string $completed_at = null,
        public ?string $cancelled_at = null,
        public ?string $refunded_at = null,
        public ?string $failure_reason = null,
        public ?string $refund_reason = null,
        public ?string $notes = null,
        /** @var array<array-key, mixed>|null */
        public ?array $metadata = null,
        public ?string $formatted_amount = null,
        public ?int $days_since_donation = null,
        public ?bool $can_be_processed = null,
        public ?bool $can_be_cancelled = null,
        public ?bool $can_be_refunded = null,
        public ?bool $is_successful = null,
        public ?bool $is_pending = null,
        public ?bool $is_failed = null,
        public ?string $created_at = null,
        public ?string $updated_at = null,
    ) {}

    public static function fromModel(Donation $donation): self
    {
        return new self(
            id: $donation->id,
            campaign_id: $donation->campaign_id,
            campaign_title: $donation->campaign !== null ? $donation->campaign->getTranslation('title', app()->getLocale()) : null,
            user_id: $donation->user_id,
            employee_name: $donation->user?->name,
            amount: $donation->amount,
            currency: $donation->currency,
            payment_method: $donation->payment_method?->value,
            payment_gateway: $donation->payment_gateway,
            transaction_id: $donation->transaction_id,
            gateway_response_id: $donation->gateway_response_id,
            status: $donation->status->value,
            anonymous: $donation->anonymous,
            recurring: $donation->recurring,
            recurring_frequency: $donation->recurring_frequency,
            donated_at: $donation->donated_at->toDateTimeString(),
            processed_at: $donation->processed_at?->toDateTimeString(),
            completed_at: $donation->completed_at?->toDateTimeString(),
            cancelled_at: $donation->cancelled_at?->toDateTimeString(),
            refunded_at: $donation->refunded_at?->toDateTimeString(),
            failure_reason: $donation->failure_reason,
            refund_reason: $donation->refund_reason,
            notes: $donation->notes,
            metadata: $donation->metadata,
            formatted_amount: $donation->formatted_amount,
            days_since_donation: $donation->days_since_donation,
            can_be_processed: $donation->canBeProcessed(),
            can_be_cancelled: $donation->canBeCancelled(),
            can_be_refunded: $donation->canBeRefunded(),
            is_successful: $donation->isSuccessful(),
            is_pending: $donation->isPending(),
            is_failed: $donation->isFailed(),
            created_at: $donation->created_at?->toDateTimeString(),
            updated_at: $donation->updated_at?->toDateTimeString(),
        );
    }
}
