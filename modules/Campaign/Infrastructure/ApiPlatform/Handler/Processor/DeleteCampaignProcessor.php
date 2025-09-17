<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Contracts\Auth\Guard;
use Modules\Campaign\Application\Command\DeleteCampaignCommand;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Shared\Application\Command\CommandBusInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProcessorInterface<object, null>
 */
final readonly class DeleteCampaignProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private Guard $auth,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): null {
        $user = $this->auth->user();

        if (! $user) {
            throw new UnauthorizedHttpException('', 'Authentication required');
        }

        $campaignId = (int) $uriVariables['id'];

        $command = new DeleteCampaignCommand(
            campaignId: $campaignId,
            employeeId: $user->id,
        );

        try {
            $this->commandBus->dispatch($command);
        } catch (CampaignException $e) {
            // Check if it's a "not found" error or authorization error
            if (str_contains($e->getMessage(), 'not found')) {
                throw new NotFoundHttpException($e->getMessage());
            }

            // Check if it's an authorization error
            if (str_contains($e->getMessage(), 'Unauthorized access')) {
                throw new AccessDeniedHttpException($e->getMessage());
            }

            // Re-throw as a generic bad request
            throw new AccessDeniedHttpException($e->getMessage());
        }

        return null;
    }
}
