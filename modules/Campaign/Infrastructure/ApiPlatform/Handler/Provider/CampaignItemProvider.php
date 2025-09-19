<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Log;
use Modules\Campaign\Application\Query\FindCampaignByIdQuery;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Infrastructure\ApiPlatform\Resource\CampaignResource;
use Modules\Shared\Application\Query\QueryBusInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webmozart\Assert\Assert;

/**
 * @implements ProviderInterface<CampaignResource>
 */
final readonly class CampaignItemProvider implements ProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CampaignResource {
        $id = $uriVariables['id'] ?? null;

        // Debug logging
        Log::info('CampaignItemProvider called', [
            'operation' => $operation::class,
            'uriVariables' => $uriVariables,
            'id' => $id,
        ]);

        Assert::notNull($id);
        $campaignId = (int) $id;

        try {
            $model = $this->queryBus->ask(new FindCampaignByIdQuery($campaignId));
        } catch (CampaignException $e) {
            Log::error('Campaign not found in provider', [
                'campaignId' => $campaignId,
                'error' => $e->getMessage(),
            ]);
            throw new NotFoundHttpException($e->getMessage());
        }

        return CampaignResource::fromModel($model);
    }
}
