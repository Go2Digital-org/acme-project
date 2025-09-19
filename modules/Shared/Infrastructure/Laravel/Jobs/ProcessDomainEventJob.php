<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Shared\Domain\Event\DomainEventInterface;
use Modules\Shared\Domain\Event\EventBusInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Job for processing domain events asynchronously
 *
 * This job ensures that domain events can be processed in the background
 * without blocking the main request thread.
 */
class ProcessDomainEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     * @var array<int, int>
     */
    public array $backoff = [5, 15, 30];

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    public function __construct(
        private readonly DomainEventInterface $event
    ) {
        $this->onQueue('domain-events');
    }

    /**
     * Execute the job
     */
    public function handle(EventBusInterface $eventBus, LoggerInterface $logger): void
    {
        try {
            $logger->info('Processing domain event asynchronously', [
                'event_name' => $this->event->getEventName(),
                'event_context' => $this->event->getContext(),
                'aggregate_id' => $this->event->getAggregateId(),
                'attempt' => $this->attempts(),
            ]);

            // Publish the event synchronously within the job context
            $eventBus->publish($this->event);

            $logger->info('Successfully processed domain event', [
                'event_name' => $this->event->getEventName(),
                'aggregate_id' => $this->event->getAggregateId(),
            ]);

        } catch (Throwable $exception) {
            $logger->error('Failed to process domain event asynchronously', [
                'event_name' => $this->event->getEventName(),
                'aggregate_id' => $this->event->getAggregateId(),
                'attempt' => $this->attempts(),
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $exception;
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(Throwable $exception): void
    {
        $logger = app(LoggerInterface::class);

        $logger->critical('Domain event processing job failed permanently', [
            'event_name' => $this->event->getEventName(),
            'aggregate_id' => $this->event->getAggregateId(),
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        // Could trigger a compensation action or alert here
    }

    /**
     * Get tags for job monitoring
     * @return array<string, mixed>
     */
    public function tags(): array
    {
        return [
            'type' => 'domain-event',
            'event' => $this->event->getEventName(),
            'context' => $this->event->getContext(),
        ];
    }
}
