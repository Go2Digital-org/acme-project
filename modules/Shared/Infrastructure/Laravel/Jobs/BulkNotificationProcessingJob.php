<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Jobs;

use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Donation\Infrastructure\Laravel\Jobs\SendChunkedCampaignUpdateNotificationJob;
use Modules\Donation\Infrastructure\Laravel\Jobs\SendMilestoneNotificationJob;
use stdClass;

/**
 * Bulk notification processing job with batching and memory optimization
 * Handles large-scale notification operations efficiently
 */
final class BulkNotificationProcessingJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800; // 30 minutes for bulk operations

    public int $tries = 2; // Limited retries for bulk operations

    public int $maxExceptions = 1;

    public bool $deleteWhenMissingModels = true;

    /** @var array<int, int> */
    public array $backoff = [300, 900]; // 5 and 15 minute backoff

    /**
     * @param  array<string, mixed>  $operationData
     * @param  array<string, mixed>  $recipientFilters
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        private readonly string $operationType,
        /** @var array<string, mixed> */
        private readonly array $operationData,
        /** @var array<string, mixed> */
        private readonly array $recipientFilters = [],
        /** @var array<string, mixed> */
        private readonly array $options = []
    ) {
        $this->onQueue('bulk');
    }

    public function handle(): void
    {
        $batch = $this->batch();
        if ($batch && $batch->cancelled()) {
            Log::info('Bulk notification processing cancelled', [
                'operation_type' => $this->operationType,
                /** @phpstan-ignore-next-line property.notFound */
                'batch_id' => $batch->id,
            ]);

            return;
        }

        Log::info('Starting bulk notification processing', [
            'operation_type' => $this->operationType,
            'recipient_filters' => $this->recipientFilters,
            'options' => $this->options,
            'job_id' => $this->job?->getJobId(),
        ]);

        try {
            match ($this->operationType) {
                'campaign_update_broadcast' => $this->processCampaignUpdateBroadcast(),
                'organization_announcement' => $this->processOrganizationAnnouncement(),
                'system_maintenance_notice' => $this->processSystemMaintenanceNotice(),
                'newsletter_blast' => $this->processNewsletterBlast(),
                'donation_reminder' => $this->processDonationReminder(),
                'campaign_milestone_alerts' => $this->processCampaignMilestoneAlerts(),
                default => throw new Exception("Unknown bulk operation type: {$this->operationType}"),
            };

        } catch (Exception $exception) {
            Log::error('Bulk notification processing failed', [
                'operation_type' => $this->operationType,
                'error' => $exception->getMessage(),
                'recipient_filters' => $this->recipientFilters,
            ]);

            throw $exception;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Bulk notification processing job failed permanently', [
            'operation_type' => $this->operationType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'recipient_filters' => $this->recipientFilters,
        ]);

        // Notify administrators about bulk operation failure
        $this->notifyBulkOperationFailure($exception);
    }

    private function processCampaignUpdateBroadcast(): void
    {
        $campaignIds = $this->operationData['campaign_ids'] ?? [];
        $updateMessage = $this->operationData['update_message'] ?? '';
        $chunkSize = $this->options['chunk_size'] ?? 100;

        if (empty($campaignIds)) {
            throw new Exception('Campaign IDs are required for campaign update broadcast');
        }

        $totalRecipients = 0;
        $batches = [];

        foreach ($campaignIds as $campaignId) {
            $recipients = $this->getCampaignRecipients($campaignId);
            $chunks = Collection::make($recipients)->chunk($chunkSize);

            foreach ($chunks as $chunkIndex => $chunk) {
                $batches[] = new SendChunkedCampaignUpdateNotificationJob(
                    campaignId: $campaignId,
                    updateType: 'bulk_update',
                    notificationData: [
                        'update_message' => $updateMessage,
                        'bulk_operation' => true,
                        'operation_id' => $this->job?->getJobId(),
                    ],
                    recipientChunk: $chunk->toArray(),
                    chunkIndex: $chunkIndex,
                    totalChunks: $chunks->count()
                );

                $totalRecipients += $chunk->count();
            }
        }

        /** @phpstan-ignore-next-line argument.type */
        $this->dispatchBatches($batches, 'campaign-update-broadcast', $totalRecipients);
    }

    private function processOrganizationAnnouncement(): void
    {
        $organizationId = $this->operationData['organization_id'] ?? null;
        $announcement = $this->operationData['announcement'] ?? '';
        $targetAudience = $this->operationData['target_audience'] ?? 'all';
        $chunkSize = $this->options['chunk_size'] ?? 150;

        if (! $organizationId) {
            throw new Exception('Organization ID is required for organization announcement');
        }

        $recipients = $this->getOrganizationRecipients($organizationId, $targetAudience);
        $chunks = Collection::make($recipients)->chunk($chunkSize);
        $batches = [];

        foreach ($chunks as $chunk) {
            $batches[] = new SendEmailJob([
                'template' => 'organization_announcement',
                'recipients' => $chunk->toArray(),
                'data' => [
                    'organization_id' => $organizationId,
                    'announcement' => $announcement,
                    'target_audience' => $targetAudience,
                ],
                'bulk_operation' => true,
            ]);
        }

        /** @phpstan-ignore-next-line argument.type */
        $this->dispatchBatches($batches, 'organization-announcement', $recipients->count());
    }

    private function processSystemMaintenanceNotice(): void
    {
        $maintenanceDetails = $this->operationData['maintenance_details'] ?? [];
        $chunkSize = $this->options['chunk_size'] ?? 200;
        $excludeInactive = $this->options['exclude_inactive'] ?? true;

        $recipients = $this->getSystemWideRecipients($excludeInactive);
        $chunks = Collection::make($recipients)->chunk($chunkSize);
        $batches = [];

        foreach ($chunks as $chunk) {
            $batches[] = new SendEmailJob([
                'template' => 'system_maintenance_notice',
                'recipients' => $chunk->toArray(),
                'data' => [
                    'maintenance_details' => $maintenanceDetails,
                    'notification_type' => 'system_maintenance',
                ],
                'priority' => 7, // High priority for system notices
                'bulk_operation' => true,
            ]);
        }

        /** @phpstan-ignore-next-line argument.type */
        $this->dispatchBatches($batches, 'system-maintenance-notice', $recipients->count());
    }

    private function processNewsletterBlast(): void
    {
        $newsletterContent = $this->operationData['newsletter_content'] ?? [];
        $segmentFilters = $this->operationData['segment_filters'] ?? [];
        $chunkSize = $this->options['chunk_size'] ?? 250;

        $recipients = $this->getNewsletterRecipients($segmentFilters);
        $chunks = Collection::make($recipients)->chunk($chunkSize);
        $batches = [];

        foreach ($chunks as $chunk) {
            $batches[] = new SendEmailJob([
                'template' => 'newsletter',
                'recipients' => $chunk->toArray(),
                'data' => [
                    'newsletter_content' => $newsletterContent,
                    'segment_filters' => $segmentFilters,
                ],
                'priority' => 2, // Low priority for newsletters
                'bulk_operation' => true,
            ]);
        }

        /** @phpstan-ignore-next-line argument.type */
        $this->dispatchBatches($batches, 'newsletter-blast', $recipients->count());
    }

    private function processDonationReminder(): void
    {
        $reminderType = $this->operationData['reminder_type'] ?? 'general';
        $campaignIds = $this->operationData['campaign_ids'] ?? [];
        $daysInactive = $this->operationData['days_inactive'] ?? 30;
        $chunkSize = $this->options['chunk_size'] ?? 100;

        $recipients = $this->getDonationReminderRecipients($campaignIds, $daysInactive);
        $chunks = Collection::make($recipients)->chunk($chunkSize);
        $batches = [];

        foreach ($chunks as $chunk) {
            $batches[] = new SendEmailJob([
                'template' => 'donation_reminder',
                'recipients' => $chunk->toArray(),
                'data' => [
                    'reminder_type' => $reminderType,
                    'campaign_ids' => $campaignIds,
                    'days_inactive' => $daysInactive,
                ],
                'priority' => 4,
                'bulk_operation' => true,
            ]);
        }

        /** @phpstan-ignore-next-line argument.type */
        $this->dispatchBatches($batches, 'donation-reminder', $recipients->count());
    }

    private function processCampaignMilestoneAlerts(): void
    {
        $milestoneType = $this->operationData['milestone_type'] ?? 'approaching_deadline';
        $thresholdDays = $this->operationData['threshold_days'] ?? 7;
        $chunkSize = $this->options['chunk_size'] ?? 75;

        $campaigns = $this->getCampaignsForMilestoneAlert($milestoneType, $thresholdDays);
        $batches = [];
        $totalRecipients = 0;

        foreach ($campaigns as $campaign) {
            // Ensure campaign is an array with required keys
            if (! is_array($campaign)) {
                continue;
            }
            if (! isset($campaign['id'])) {
                continue;
            }
            $recipients = $this->getCampaignRecipients($campaign['id']);
            $chunks = Collection::make($recipients)->chunk($chunkSize);

            foreach ($chunks as $chunk) {
                $batches[] = new SendMilestoneNotificationJob(
                    campaignId: $campaign['id'],
                    milestonePercentage: $campaign['milestone_percentage'] ?? 100
                );

                $totalRecipients += $chunk->count();
            }
        }

        /** @phpstan-ignore-next-line argument.type */
        $this->dispatchBatches($batches, 'campaign-milestone-alerts', $totalRecipients);
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function getCampaignRecipients(int $campaignId): Collection
    {
        return DB::table('donations')
            ->join('users', 'donations.user_id', '=', 'users.id')
            ->where('donations.campaign_id', $campaignId)
            ->where('donations.status', 'completed')
            ->where('donations.anonymous', false)
            ->where('users.email_notifications_enabled', true)
            ->select('users.email', 'users.name', 'users.locale')
            ->distinct()
            ->get();
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function getOrganizationRecipients(int $organizationId, string $targetAudience): Collection
    {
        $query = DB::table('users')
            ->where('organization_id', $organizationId)
            ->where('email_notifications_enabled', true)
            ->where('status', 'active');

        if ($targetAudience === 'employees') {
            $query->where('role', 'employee');
        } elseif ($targetAudience === 'administrators') {
            $query->where('role', 'admin');
        }

        return $query->select('email', 'name', 'locale', 'role')->get();
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function getSystemWideRecipients(bool $excludeInactive): Collection
    {
        $query = DB::table('users')
            ->where('email_notifications_enabled', true)
            ->where('system_notifications_enabled', true);

        if ($excludeInactive) {
            $query->where('status', 'active')
                ->where('last_login_at', '>=', now()->subDays(90));
        }

        return $query->select('email', 'name', 'locale')->get();
    }

    /**
     * @param  array<string, mixed>  $segmentFilters
     * @return Collection<int, stdClass>
     */
    private function getNewsletterRecipients(array $segmentFilters): Collection
    {
        $query = DB::table('users')
            ->where('email_notifications_enabled', true)
            ->where('newsletter_subscribed', true)
            ->where('status', 'active');

        // Apply segment filters
        if (isset($segmentFilters['min_donations'])) {
            $query->whereExists(function ($subQuery) use ($segmentFilters): void {
                $subQuery->select(DB::raw(1))
                    ->from('donations')
                    ->whereColumn('donations.user_id', 'users.id')
                    ->where('donations.status', 'completed')
                    ->havingRaw('COUNT(*) >= ?', [$segmentFilters['min_donations']]);
            });
        }

        if (isset($segmentFilters['regions'])) {
            $query->whereIn('region', $segmentFilters['regions']);
        }

        return $query->select('email', 'name', 'locale', 'region')->get();
    }

    /**
     * @param  array<string, mixed>  $campaignIds
     * @return Collection<int, stdClass>
     */
    private function getDonationReminderRecipients(array $campaignIds, int $daysInactive): Collection
    {
        $query = DB::table('users')
            ->where('email_notifications_enabled', true)
            ->where('status', 'active')
            ->where('last_login_at', '<=', now()->subDays($daysInactive));

        if ($campaignIds !== []) {
            $query->whereExists(function ($subQuery) use ($campaignIds): void {
                $subQuery->select(DB::raw(1))
                    ->from('donations')
                    ->whereColumn('donations.user_id', 'users.id')
                    ->whereIn('donations.campaign_id', $campaignIds)
                    ->where('donations.status', 'completed');
            });
        }

        return $query->select('email', 'name', 'locale', 'last_login_at')->get();
    }

    /**
     * @return array<int|string, mixed>
     */
    private function getCampaignsForMilestoneAlert(string $milestoneType, int $thresholdDays): array
    {
        return match ($milestoneType) {
            'approaching_deadline' => $this->getCampaignsApproachingDeadline($thresholdDays),
            'low_funding' => $this->getCampaignsWithLowFunding(),
            'milestone_reached' => $this->getCampaignsReachingMilestones(),
            default => [],
        };
    }

    /**
     * @return array<int, object>
     */
    private function getCampaignsApproachingDeadline(int $thresholdDays): array
    {
        return DB::table('campaigns')
            ->where('status', 'active')
            ->where('end_date', '<=', now()->addDays($thresholdDays))
            ->where('end_date', '>=', now())
            ->select('id', 'title', 'end_date')
            ->get()
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function getCampaignsWithLowFunding(): array
    {
        return DB::table('campaigns')
            ->where('status', 'active')
            ->whereRaw('current_amount < (goal_amount * 0.25)') // Less than 25% funded
            ->where('created_at', '<=', now()->subDays(14)) // Running for at least 2 weeks
            ->select('id', 'title', 'current_amount', 'goal_amount')
            ->get()
            ->toArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getCampaignsReachingMilestones(): array
    {
        $campaigns = DB::table('campaigns')
            ->where('status', 'active')
            ->select('id', 'title', 'current_amount', 'goal_amount')
            ->get();

        $milestoneThresholds = [25, 50, 75, 100];
        $result = [];

        foreach ($campaigns as $campaign) {
            $percentage = ($campaign->current_amount / $campaign->goal_amount) * 100;

            foreach ($milestoneThresholds as $threshold) {
                if ($percentage >= $threshold && $percentage < ($threshold + 5)) {
                    // Check if we haven't already sent this milestone notification
                    $notificationSent = DB::table('campaign_milestone_notifications')
                        ->where('campaign_id', $campaign->id)
                        ->where('milestone_percentage', $threshold)
                        ->exists();

                    if (! $notificationSent) {
                        $result[] = [
                            'id' => $campaign->id,
                            'title' => $campaign->title,
                            'milestone_percentage' => $threshold,
                        ];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param  array<object>  $jobs
     */
    private function dispatchBatches(array $jobs, string $batchName, int $totalRecipients): void
    {
        if ($jobs === []) {
            Log::info('No jobs to dispatch for bulk operation', [
                'operation_type' => $this->operationType,
                'batch_name' => $batchName,
            ]);

            return;
        }

        $batch = Bus::batch($jobs)
            ->name($batchName)
            ->onQueue('bulk')
            ->allowFailures()
            ->finally(function () use ($batchName, $totalRecipients): void {
                Log::info('Bulk notification batch completed', [
                    'batch_name' => $batchName,
                    'total_recipients' => $totalRecipients,
                    'operation_type' => $this->operationType,
                ]);
            })
            ->catch(function ($batch, Exception $exception) use ($batchName): void {
                Log::error('Bulk notification batch failed', [
                    'batch_name' => $batchName,
                    'operation_type' => $this->operationType,
                    'failed_jobs' => $batch->failedJobs,
                    'error' => $exception->getMessage(),
                ]);
            });

        $batch->dispatch();

        Log::info('Bulk notification batch dispatched', [
            'batch_name' => $batchName,
            'operation_type' => $this->operationType,
            'total_jobs' => count($jobs),
            'total_recipients' => $totalRecipients,
            /** @phpstan-ignore-next-line property.notFound */
            'batch_id' => $batch->id,
        ]);
    }

    private function notifyBulkOperationFailure(Exception $exception): void
    {
        try {
            SendEmailJob::dispatch(
                emailData: [
                    'to' => config('mail.admin_email', 'admin@example.com'),
                    'subject' => 'Bulk Notification Operation Failed',
                    'view' => 'emails.admin.bulk-operation-failure',
                    'data' => [
                        'operation_type' => $this->operationType,
                        'operation_data' => $this->operationData,
                        'recipient_filters' => $this->recipientFilters,
                        'error_message' => $exception->getMessage(),
                        'job_attempts' => $this->attempts(),
                        'failed_at' => now(),
                    ],
                ],
                locale: null,
                priority: 8
            );
        } catch (Exception $notificationException) {
            Log::error('Failed to send bulk operation failure notification', [
                'original_error' => $exception->getMessage(),
                'notification_error' => $notificationException->getMessage(),
            ]);
        }
    }

    /**
     * Static factory methods for different bulk operations
     */
    /**
     * @param  array<string, mixed>  $campaignIds
     * @param  array<string, mixed>  $options
     */
    public static function campaignUpdateBroadcast(
        array $campaignIds,
        string $updateMessage,
        array $options = []
    ): self {
        return new self(
            'campaign_update_broadcast',
            [
                'campaign_ids' => $campaignIds,
                'update_message' => $updateMessage,
            ],
            [],
            $options
        );
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public static function organizationAnnouncement(
        int $organizationId,
        string $announcement,
        string $targetAudience = 'all',
        array $options = []
    ): self {
        return new self(
            'organization_announcement',
            [
                'organization_id' => $organizationId,
                'announcement' => $announcement,
                'target_audience' => $targetAudience,
            ],
            [],
            $options
        );
    }

    /**
     * @param  array<string, mixed>  $maintenanceDetails
     * @param  array<string, mixed>  $options
     */
    public static function systemMaintenanceNotice(
        array $maintenanceDetails,
        array $options = []
    ): self {
        return new self(
            'system_maintenance_notice',
            ['maintenance_details' => $maintenanceDetails],
            [],
            $options
        );
    }
}
