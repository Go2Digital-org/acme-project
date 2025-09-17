<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Search\Application\Command\IndexEntityCommand;
use Modules\Search\Application\Command\IndexEntityCommandHandler;

class IndexEntityJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $entityType,
        public string $entityId,
        public array $data,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(IndexEntityCommandHandler $handler): void
    {
        $command = new IndexEntityCommand(
            entityType: $this->entityType,
            entityId: $this->entityId,
            data: $this->data,
            shouldQueue: false, // Already in queue, don't re-queue
        );

        $handler->handle($command);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return ['search', 'indexing', $this->entityType];
    }
}
