<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Illuminate\Http\Response;
use Modules\Donation\Infrastructure\ApiPlatform\Handler\Processor\MollieWebhookProcessor;
use Modules\Donation\Infrastructure\ApiPlatform\Handler\Processor\StripeWebhookProcessor;

#[ApiResource(
    shortName: 'Webhook',
    operations: [
        new Post(
            uriTemplate: '/webhooks/stripe',
            status: Response::HTTP_OK,
            description: 'Handle Stripe webhook notifications',
            read: false,
            deserialize: false,
            validate: false,
            processor: StripeWebhookProcessor::class,
        ),
        new Post(
            uriTemplate: '/webhooks/mollie',
            status: Response::HTTP_OK,
            description: 'Handle Mollie webhook notifications',
            read: false,
            deserialize: false,
            validate: false,
            processor: MollieWebhookProcessor::class,
        ),
    ],
    // No authentication middleware - external webhooks don't use auth
    middleware: [],
)]
class WebhookResource
{
    public function __construct(
        public ?string $id = null,
        public ?string $type = null,
        /** @var array<string, mixed>|null */
        public ?array $data = null,
        public ?bool $livemode = null,
        public ?string $mode = null,
        public ?string $status = null,
        /** @var array<string, mixed>|null */
        public ?array $amount = null,
        /** @var array<string, mixed>|null */
        public ?array $metadata = null,
        public ?string $createdAt = null,
    ) {}
}
