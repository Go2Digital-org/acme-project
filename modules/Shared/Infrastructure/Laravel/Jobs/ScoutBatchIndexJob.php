<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Meilisearch\Client;
use Modules\Campaign\Domain\Model\Campaign;
use Throwable;

class ScoutBatchIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [60, 180, 600];

    /**
     * Create a new job instance.
     *
     * @param  class-string  $modelClass  The model class to index
     * @param  int  $startId  The starting ID for this batch
     * @param  int  $endId  The ending ID for this batch
     * @param  int  $chunkSize  Size of chunks to process within this batch
     */
    public function __construct(
        protected string $modelClass,
        protected int $startId,
        protected int $endId,
        protected int $chunkSize = 100
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! class_exists($this->modelClass)) {
            Log::error("ScoutBatchIndexJob: Model class {$this->modelClass} does not exist");
            $this->fail(new Exception("Model class {$this->modelClass} does not exist"));

            return;
        }

        if (! method_exists($this->modelClass, 'searchable')) {
            Log::error("ScoutBatchIndexJob: Model {$this->modelClass} is not searchable");
            $this->fail(new Exception("Model {$this->modelClass} is not searchable"));

            return;
        }

        $indexed = 0;
        $errors = 0;

        try {
            // Process in chunks using cursor to minimize memory usage
            $this->modelClass::query()
                ->whereBetween('id', [$this->startId, $this->endId])
                ->chunkById($this->chunkSize, function ($models) use (&$indexed, &$errors): void {
                    try {
                        // Filter models that should be searchable
                        $searchableModels = $models->filter(function ($model) {
                            if (is_object($model) && method_exists($model, 'shouldBeSearchable')) {
                                return $model->shouldBeSearchable();
                            }

                            return true;
                        });

                        if ($searchableModels->isNotEmpty()) {
                            // Pre-load relationships for the batch to avoid N+1
                            // Only load if model is Campaign
                            if ($this->modelClass === Campaign::class) {
                                $searchableModels->load(['organization:id,name', 'creator:id,name', 'categoryModel:id,name,slug']);
                            }

                            // Bypass Scout's searchable() method to avoid memory issues
                            // Index directly to Meilisearch
                            try {
                                $documents = [];
                                foreach ($searchableModels as $model) {
                                    if (is_object($model) && method_exists($model, 'toSearchableArray')) {
                                        $documents[] = $model->toSearchableArray();
                                    }
                                }

                                if ($documents !== []) {
                                    // Get Meilisearch client
                                    $client = new Client(
                                        config('scout.meilisearch.host'),
                                        config('scout.meilisearch.key')
                                    );

                                    // Get index name
                                    $indexName = config('scout.prefix', 'acme_') . 'campaigns';
                                    $index = $client->index($indexName);

                                    // Add documents in batch
                                    $index->addDocuments($documents);
                                    $indexed += count($documents);
                                }
                            } catch (Exception $e) {
                                Log::error('ScoutBatchIndexJob: Error indexing to Meilisearch', [
                                    'error' => $e->getMessage(),
                                ]);
                                throw $e;
                            }
                        }

                        // Free memory
                        unset($searchableModels);
                    } catch (Exception $e) {
                        $errors++;
                        Log::error('ScoutBatchIndexJob: Error indexing chunk', [
                            'model' => $this->modelClass,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        // Don't fail the entire job for a single chunk error
                        if ($errors > 5) {
                            throw $e;
                        }
                    }
                });

            Log::info('ScoutBatchIndexJob: Batch completed', [
                'model' => $this->modelClass,
                'range' => "{$this->startId}-{$this->endId}",
                'indexed' => $indexed,
                'errors' => $errors,
            ]);
        } catch (Exception $e) {
            Log::error('ScoutBatchIndexJob: Job failed', [
                'model' => $this->modelClass,
                'range' => "{$this->startId}-{$this->endId}",
                'indexed' => $indexed,
                'error' => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('ScoutBatchIndexJob: Job failed permanently', [
            'model' => $this->modelClass,
            'range' => "{$this->startId}-{$this->endId}",
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['scout', 'indexing', $this->modelClass];
    }
}
