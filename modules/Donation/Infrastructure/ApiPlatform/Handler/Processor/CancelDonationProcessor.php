<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\Donation\Application\Command\CancelDonationCommand;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Donation\Infrastructure\ApiPlatform\Resource\DonationResource;
use Modules\Shared\Application\Command\CommandBusInterface;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @implements ProcessorInterface<object, DonationResource>
 */
final readonly class CancelDonationProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private DonationRepositoryInterface $donationRepository,
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
        $request = $context['request'] ?? null;

        if (! ($request instanceof Request)) {
            throw new InvalidArgumentException('Request context is required');
        }

        $user = $request->user();

        if (! ($user instanceof User)) {
            throw new InvalidArgumentException('User must be authenticated');
        }

        $donationId = (int) $uriVariables['id'];
        $requestData = (object) (array) $data;

        $command = new CancelDonationCommand(
            donationId: $donationId,
            userId: $user->id,
            reason: $requestData->reason ?? null,
        );

        $this->commandBus->dispatch($command);

        $donation = $this->donationRepository->findById($donationId);

        if (! $donation instanceof Donation) {
            throw new InvalidArgumentException('Donation not found');
        }

        return DonationResource::fromModel($donation);
    }
}
