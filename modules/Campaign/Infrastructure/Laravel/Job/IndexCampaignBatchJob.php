<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Application\Command\IndexCampaignsAsyncCommand;
use Modules\Campaign\Application\Command\IndexCampaignsAsyncCommandHandler;

final class IndexCampaignBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $offset,
        private readonly int $limit
    ) {}

    public function handle(IndexCampaignsAsyncCommandHandler $handler): void
    {
        $command = new IndexCampaignsAsyncCommand(
            chunkSize: $this->limit,
            offset: $this->offset,
            limit: $this->limit
        );

        $handler->handle($command);
    }
}
