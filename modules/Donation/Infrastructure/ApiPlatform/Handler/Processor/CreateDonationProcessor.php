<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\Donation\Application\Command\CreateDonationCommand;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Infrastructure\ApiPlatform\Resource\DonationResource;
use Modules\Shared\Application\Command\CommandBusInterface;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @implements ProcessorInterface<object, DonationResource>
 */
final readonly class CreateDonationProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {}

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): DonationResource {
        /** @var Request $request */
        $request = $context['request'] ?? throw new InvalidArgumentException('Request context is required');

        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            throw new InvalidArgumentException('User must be authenticated');
        }

        // Cast data as stdClass with proper defaults from validation rules
        $requestData = (object) (array) $data;

        $command = new CreateDonationCommand(
            campaignId: (int) ($requestData->campaign_id ?? 0),
            userId: $user->id,
            amount: (float) ($requestData->amount ?? 0.0),
            currency: (string) ($requestData->currency ?? 'USD'),
            paymentMethod: (string) ($requestData->payment_method ?? 'stripe'),
            paymentGateway: $requestData->payment_gateway ?? null,
            anonymous: (bool) ($requestData->anonymous ?? false),
            recurring: (bool) ($requestData->recurring ?? false),
            recurringFrequency: $requestData->recurring_frequency ?? null,
            notes: $requestData->notes ?? null,
        );

        $donation = $this->commandBus->handle($command);

        if (! $donation instanceof Donation) {
            throw new InvalidArgumentException('Command handler did not return a Donation instance');
        }

        return DonationResource::fromModel($donation);
    }
}
