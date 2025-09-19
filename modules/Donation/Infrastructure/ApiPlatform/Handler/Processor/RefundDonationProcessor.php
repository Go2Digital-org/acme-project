<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\Donation\Application\Command\RefundDonationCommand;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Infrastructure\ApiPlatform\Resource\DonationResource;
use Modules\Shared\Application\Command\CommandBusInterface;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @implements ProcessorInterface<object, DonationResource>
 */
final readonly class RefundDonationProcessor implements ProcessorInterface
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

        $donationId = (int) $uriVariables['id'];

        // Cast data as stdClass with proper defaults from validation rules
        $requestData = (object) (array) $data;

        $command = new RefundDonationCommand(
            donationId: $donationId,
            refundReason: (string) ($requestData->reason ?? ''),
            processedByEmployeeId: $user->id,
        );

        $donation = $this->commandBus->handle($command);

        if (! $donation instanceof Donation) {
            throw new InvalidArgumentException('Command handler did not return a Donation instance');
        }

        return DonationResource::fromModel($donation);
    }
}
