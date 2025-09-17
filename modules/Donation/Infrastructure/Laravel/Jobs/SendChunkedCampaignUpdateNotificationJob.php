<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Jobs;

use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Shared\Infrastructure\Laravel\Jobs\SendEmailJob;

/**
 * Chunked notification job for large recipient lists
 * Handles memory-efficient batch processing of campaign update notifications
 */
class SendChunkedCampaignUpdateNotificationJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300; // 5 minutes for chunked processing

    public int $tries = 3;

    public int $maxExceptions = 2;

    /** @var array<int, int> */
    public array $backoff = [60, 180, 300];

    public function __construct(
        private readonly int $campaignId,
        private readonly string $updateType,
        /** @var array<string, mixed> */
        private readonly array $notificationData = [],
        /** @var array<int, array<string, mixed>> */
        private readonly array $recipientChunk = [],
        private readonly int $chunkIndex = 0,
        private readonly int $totalChunks = 1,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(CampaignRepositoryInterface $campaignRepository): void
    {
        if ($this->batch()?->cancelled()) {
            Log::info('Chunked notification job cancelled', [
                'campaign_id' => $this->campaignId,
                'chunk_index' => $this->chunkIndex,
            ]);

            return;
        }

        Log::info('Processing chunked campaign notification', [
            'campaign_id' => $this->campaignId,
            'update_type' => $this->updateType,
            'chunk_index' => $this->chunkIndex,
            'total_chunks' => $this->totalChunks,
            'chunk_size' => count($this->recipientChunk),
            'job_id' => $this->job?->getJobId(),
        ]);

        $campaign = $campaignRepository->findById($this->campaignId);

        if (! $campaign instanceof Campaign) {
            Log::error('Campaign not found for chunked notification', [
                'campaign_id' => $this->campaignId,
                'chunk_index' => $this->chunkIndex,
            ]);

            return;
        }

        if (! $this->shouldSendNotification($campaign)) {
            Log::info('Campaign notification disabled or not eligible', [
                'campaign_id' => $this->campaignId,
                'update_type' => $this->updateType,
                'chunk_index' => $this->chunkIndex,
            ]);

            return;
        }

        try {
            $this->processChunkedNotifications($campaign);

            Log::info('Chunked campaign notifications processed successfully', [
                'campaign_id' => $this->campaignId,
                'update_type' => $this->updateType,
                'chunk_index' => $this->chunkIndex,
                'recipients_processed' => count($this->recipientChunk),
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to process chunked campaign notifications', [
                'campaign_id' => $this->campaignId,
                'update_type' => $this->updateType,
                'chunk_index' => $this->chunkIndex,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Chunked campaign notification job failed permanently', [
            'campaign_id' => $this->campaignId,
            'update_type' => $this->updateType,
            'chunk_index' => $this->chunkIndex,
            'total_chunks' => $this->totalChunks,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Notify admin about chunk failure
        $this->notifyChunkFailure($exception);
    }

    /**
     * Create batched jobs for large recipient lists
     *
     * @param  array<string, mixed>  $notificationData
     * @param  array<int, array<string, mixed>>  $recipients
     */
    public static function createBatch(
        int $campaignId,
        string $updateType,
        array $notificationData,
        array $recipients,
        int $chunkSize = 50
    ): void {
        if ($recipients === []) {
            Log::info('No recipients for campaign notification batch', [
                'campaign_id' => $campaignId,
                'update_type' => $updateType,
            ]);

            return;
        }

        $chunks = Collection::make($recipients)->chunk($chunkSize);
        $totalChunks = $chunks->count();

        if ($totalChunks === 1) {
            // Single chunk, dispatch directly without batching overhead
            self::dispatch($campaignId, $updateType, $notificationData, $recipients, 0, 1);

            return;
        }

        $jobs = $chunks->map(fn (Collection $chunk, int $index) => new self(
            $campaignId,
            $updateType,
            $notificationData,
            $chunk->toArray(),
            $index,
            $totalChunks
        ));

        $batch = Bus::batch($jobs->toArray())
            ->name("campaign-notifications-{$campaignId}-{$updateType}")
            ->onQueue('notifications')
            ->allowFailures()
            ->finally(function () use ($campaignId, $updateType, $totalChunks): void {
                Log::info('Campaign notification batch completed', [
                    'campaign_id' => $campaignId,
                    'update_type' => $updateType,
                    'total_chunks' => $totalChunks,
                ]);
            })
            ->catch(function ($batch, Exception $exception) use ($campaignId, $updateType): void {
                Log::error('Campaign notification batch failed', [
                    'campaign_id' => $campaignId,
                    'update_type' => $updateType,
                    'failed_jobs' => $batch->failedJobs,
                    'error' => $exception->getMessage(),
                ]);
            });

        $dispatchedBatch = $batch->dispatch();

        Log::info('Campaign notification batch dispatched', [
            'campaign_id' => $campaignId,
            'update_type' => $updateType,
            'total_recipients' => count($recipients),
            'total_chunks' => $totalChunks,
            'batch_id' => $dispatchedBatch->id,
        ]);
    }

    private function shouldSendNotification(Campaign $campaign): bool
    {
        // Don't send notifications for inactive campaigns
        if (! $campaign->isActive() && $this->updateType !== 'campaign_completed') {
            return false;
        }

        return true;
    }

    private function processChunkedNotifications(Campaign $campaign): void
    {
        foreach ($this->recipientChunk as $recipient) {
            if ($this->batch()?->cancelled()) {
                Log::info('Batch cancelled, stopping chunk processing', [
                    'campaign_id' => $this->campaignId,
                    'chunk_index' => $this->chunkIndex,
                ]);
                break;
            }

            try {
                $this->sendNotificationToRecipient($campaign, $recipient);
            } catch (Exception $exception) {
                Log::error('Failed to send notification to individual recipient', [
                    'campaign_id' => $this->campaignId,
                    'recipient_email' => $recipient['email'] ?? 'unknown',
                    'chunk_index' => $this->chunkIndex,
                    'error' => $exception->getMessage(),
                ]);

                // Continue processing other recipients in chunk
                continue;
            }

            // Small delay to prevent overwhelming email service
            usleep(50000); // 50ms between emails
        }
    }

    /**
     * @param  array<string, mixed>  $recipient
     */
    private function sendNotificationToRecipient(Campaign $campaign, array $recipient): void
    {
        $emailData = $this->buildEmailData($campaign, $recipient);

        // Dispatch individual email job with retry logic
        SendEmailJob::dispatch(
            emailData: [
                'to' => $recipient['email'],
                'subject' => $emailData['subject'],
                'view' => $emailData['template'],
                'data' => $emailData['data'],
            ],
            locale: $recipient['locale'] ?? 'en',
            priority: 6
        )->onQueue('notifications');

        Log::debug('Notification queued for recipient', [
            'campaign_id' => $this->campaignId,
            'recipient_email' => $recipient['email'],
            'template' => $emailData['template'],
            'chunk_index' => $this->chunkIndex,
        ]);
    }

    /**
     * @param  array<string, mixed>  $recipient
     * @return array<string, mixed>
     */
    private function buildEmailData(Campaign $campaign, array $recipient): array
    {
        $baseData = [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'description' => $campaign->description,
                'current_amount' => $campaign->current_amount,
                'goal_amount' => $campaign->goal_amount,
                'progress_percentage' => $campaign->getProgressPercentage(),
            ],
            'recipient' => $recipient,
            'notification_data' => $this->notificationData,
        ];

        return match ($this->updateType) {
            'new_donation' => [
                'subject' => 'New donation received for your campaign',
                'template' => 'emails.campaign.new-donation',
                'data' => array_merge($baseData, [
                    'donation' => $this->notificationData['donation'] ?? null,
                ]),
            ],
            'status_changed' => [
                'subject' => 'Campaign status update',
                'template' => 'emails.campaign.status-changed',
                'data' => array_merge($baseData, [
                    'old_status' => $this->notificationData['old_status'] ?? null,
                    'new_status' => $this->notificationData['new_status'] ?? null,
                ]),
            ],
            'campaign_updated' => [
                'subject' => 'Campaign update',
                'template' => 'emails.campaign.updated',
                'data' => array_merge($baseData, [
                    'update_details' => $this->notificationData['update_details'] ?? '',
                ]),
            ],
            'campaign_completed' => [
                'subject' => 'Campaign completed - Goal reached!',
                'template' => 'emails.campaign.completed',
                'data' => array_merge($baseData, [
                    'final_amount' => $campaign->current_amount,
                    'goal_reached' => $campaign->hasReachedGoal(),
                ]),
            ],
            default => [
                'subject' => 'Campaign notification',
                'template' => 'emails.campaign.generic-update',
                'data' => $baseData,
            ],
        };
    }

    private function notifyChunkFailure(Exception $exception): void
    {
        try {
            // Dispatch admin notification about chunk failure
            SendEmailJob::dispatch(
                emailData: [
                    'to' => config('mail.admin_email', 'admin@example.com'),
                    'subject' => 'Campaign Notification Chunk Failed',
                    'view' => 'emails.admin.chunk-failure',
                    'data' => [
                        'campaign_id' => $this->campaignId,
                        'update_type' => $this->updateType,
                        'chunk_index' => $this->chunkIndex,
                        'total_chunks' => $this->totalChunks,
                        'recipients_affected' => count($this->recipientChunk),
                        'error_message' => $exception->getMessage(),
                        'job_attempts' => $this->attempts(),
                    ],
                ],
                locale: null,
                priority: 8
            )->onQueue('notifications');
        } catch (Exception $notificationException) {
            Log::error('Failed to send chunk failure notification', [
                'original_error' => $exception->getMessage(),
                'notification_error' => $notificationException->getMessage(),
            ]);
        }
    }
}
