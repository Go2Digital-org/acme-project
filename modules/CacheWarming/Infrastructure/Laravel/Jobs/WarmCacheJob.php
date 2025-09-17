<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Infrastructure\Laravel\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\CacheWarming\Application\Command\StartCacheWarmingCommand;
use Modules\CacheWarming\Application\Command\StartCacheWarmingCommandHandler;
use Modules\CacheWarming\Domain\ValueObject\CacheKey;

final class WarmCacheJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const PROGRESS_CACHE_PREFIX = 'cache_warming_progress:';

    private const PROGRESS_TTL = 300; // 5 minutes

    public int $tries = 3;

    public int $timeout = 900; // 15 minutes

    /**
     * @param  array<string>  $specificKeys
     */
    public function __construct(
        private readonly string $strategyType,
        private readonly array $specificKeys = [],
        private readonly bool $force = false
    ) {
        $this->onQueue('cache-warming');
    }

    /**
     * Alternative constructor for simple cache key warming.
     */
    public static function forKey(string $cacheKey): self
    {
        return new self('single', [$cacheKey], false);
    }

    public function handle(StartCacheWarmingCommandHandler $handler): void
    {
        $jobId = $this->getJobId();

        try {
            Log::info('Starting cache warming job', [
                'job_id' => $jobId,
                'strategy' => $this->strategyType,
                'specific_keys' => $this->specificKeys,
                'force' => $this->force,
            ]);

            $this->updateProgress($jobId, 'starting', 0, 'Initializing cache warming...');

            $specificKeys = $this->createSpecificKeys();
            $strategy = $this->strategyType === 'widgets' ? 'widget' : $this->strategyType;

            $command = new StartCacheWarmingCommand(
                strategy: $strategy,
                specificKeys: $specificKeys,
                forceRegenerate: $this->force
            );

            $this->updateProgress($jobId, 'warming', 10, 'Starting cache warming process...');

            $result = $handler->handle($command);

            Log::info('Cache warming result', [
                'job_id' => $jobId,
                'status' => $result->status->value,
                'total_items' => $result->totalItems,
                'current_item' => $result->currentItem,
                'percentage' => $result->getPercentageComplete(),
                'is_complete' => $result->isComplete(),
                'is_failed' => $result->status->isFailed(),
            ]);

            if ($result->isComplete()) {
                $successMessage = "Successfully warmed {$result->currentItem} of {$result->totalItems} cache keys";
                $this->updateProgress($jobId, 'completed', 100, $successMessage);
                Log::info('Cache warming job completed successfully', [
                    'job_id' => $jobId,
                    'total_items' => $result->totalItems,
                    'completed_items' => $result->currentItem,
                    'percentage' => $result->getPercentageComplete(),
                    'status' => $result->status->value,
                ]);

                return;
            }

            if ($result->status->isFailed()) {
                $partialSuccess = $result->currentItem > 0;

                if (! $partialSuccess) {
                    $errorMessage = 'Cache warming failed completely - no keys were successfully warmed';
                    $this->updateProgress($jobId, 'failed', 0, $errorMessage);
                    Log::error('Cache warming job failed completely', [
                        'job_id' => $jobId,
                        'status' => $result->status->value,
                        'total_items' => $result->totalItems,
                        'current_item' => $result->currentItem,
                    ]);
                    $this->fail(new Exception($errorMessage));

                    return;
                }

                $successRate = round(($result->currentItem / $result->totalItems) * 100, 2);
                $errorMessage = "Cache warming partially completed: {$result->currentItem}/{$result->totalItems} keys ({$successRate}% success rate)";
                $this->updateProgress($jobId, 'partial', (int) $successRate, $errorMessage);
                Log::warning('Cache warming job partially completed', [
                    'job_id' => $jobId,
                    'status' => $result->status->value,
                    'total_items' => $result->totalItems,
                    'successful_items' => $result->currentItem,
                    'success_rate' => $successRate . '%',
                ]);

                return;
            }

            $percentage = (int) $result->getPercentageComplete();
            $this->updateProgress($jobId, 'warming', $percentage,
                "Warming cache... {$result->currentItem}/{$result->totalItems}"
            );

        } catch (Exception $e) {
            $this->updateProgress($jobId, 'failed', 0, $e->getMessage());

            Log::error('Cache warming job exception', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->fail($e);
        }
    }

    public function failed(?Exception $exception = null): void
    {
        $jobId = $this->getJobId();
        $errorMessage = $exception?->getMessage() ?? 'Unknown error';

        $this->updateProgress($jobId, 'failed', 0, $errorMessage);

        Log::error('Cache warming job failed permanently', [
            'job_id' => $jobId,
            'error' => $errorMessage,
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getProgress(string $jobId): ?array
    {
        $cacheKey = self::PROGRESS_CACHE_PREFIX . $jobId;
        $progress = Cache::get($cacheKey);

        if ($progress === null) {
            return null;
        }

        return is_array($progress) ? $progress : json_decode((string) $progress, true);
    }

    /**
     * @param  array<string>  $specificKeys
     */
    public static function dispatch(string $strategyType, array $specificKeys = [], bool $force = false): self
    {
        return new self($strategyType, $specificKeys, $force);
    }

    /**
     * Set delay for the job.
     */
    public function delay(int $seconds): self
    {
        $this->delay = $seconds;

        return $this;
    }

    /**
     * @return array<string>
     */
    private function createSpecificKeys(): array
    {
        if ($this->specificKeys === []) {
            return [];
        }

        $keys = [];
        foreach ($this->specificKeys as $keyString) {
            try {
                new CacheKey($keyString); // Validate the key
                $keys[] = $keyString;
            } catch (Exception $e) {
                Log::warning("Invalid cache key in job: {$keyString}", ['error' => $e->getMessage()]);
            }
        }

        return $keys;
    }

    private function getJobId(): string
    {
        return (string) $this->job?->getJobId() ?: uniqid('cache_warming_', true);
    }

    private function updateProgress(string $jobId, string $status, int $percentage, string $message): void
    {
        try {
            $progress = [
                'job_id' => $jobId,
                'status' => $status,
                'percentage' => max(0, min(100, $percentage)),
                'message' => $message,
                'updated_at' => now()->toISOString(),
                'strategy' => $this->strategyType,
                'specific_keys' => $this->specificKeys,
            ];

            $cacheKey = self::PROGRESS_CACHE_PREFIX . $jobId;
            Cache::put($cacheKey, $progress, self::PROGRESS_TTL);

        } catch (Exception $e) {
            Log::error('Failed to update cache warming progress', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
