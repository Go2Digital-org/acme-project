<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Service;

use Modules\Campaign\Domain\Model\Campaign;

interface CampaignIndexerInterface
{
    public function index(Campaign $campaign): void;

    /**
     * @param  array<Campaign>  $campaigns
     */
    public function indexBatch(array $campaigns): void;

    public function remove(Campaign $campaign): void;

    public function flush(): void;
}
