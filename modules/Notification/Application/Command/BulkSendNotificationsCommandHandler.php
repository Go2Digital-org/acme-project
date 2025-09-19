<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Application\Event\NotificationsSentEvent;
use Modules\Notification\Application\Service\NotificationDeliveryService;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;

final readonly class BulkSendNotificationsCommandHandler
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private NotificationDeliveryService $deliveryService
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function handle(BulkSendNotificationsCommand $command): array
    {
        if ($command->notificationIds === []) {
            return [
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
                'results' => [],
            ];
        }

        $results = [];
        $sentCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        try {
            // Process in chunks to avoid memory issues
            $chunks = array_chunk($command->notificationIds, 50);

            foreach ($chunks as $chunk) {
                /** @var array<string, mixed> $chunkIds */
                $chunkIds = array_map('strval', $chunk);
                $notifications = $this->notificationRepository->findByIds($chunkIds);

                foreach ($notifications as $notification) {
                    /** @var Notification $notification */
                    try {
                        // Skip already sent notifications unless forced
                        if (! $command->force && $notification->sent_at) {
                            $results[$notification->id] = [
                                'status' => 'skipped',
                                'reason' => 'Already sent',
                            ];
                            $skippedCount++;

                            continue;
                        }

                        // Skip scheduled notifications that aren't due yet unless forced
                        if (! $command->force && $notification->scheduled_for &&
                            $notification->scheduled_for->isFuture()) {
                            $results[$notification->id] = [
                                'status' => 'skipped',
                                'reason' => 'Scheduled for future',
                            ];
                            $skippedCount++;

                            continue;
                        }

                        // Determine channels to use
                        $channels = $command->channels ?? $this->getNotificationChannels($notification);

                        // Send notification through each channel
                        $channelResults = [];
                        $allChannelsSuccessful = true;

                        foreach ($channels as $channel) {
                            try {
                                $channelResult = $this->deliveryService->send($notification, ['channel' => $channel]);
                                $channelResults[$channel] = $channelResult;

                                if (! $channelResult->isSuccessful()) {
                                    $allChannelsSuccessful = false;
                                }
                            } catch (Exception $e) {
                                $channelResults[$channel] = [
                                    'success' => false,
                                    'error' => $e->getMessage(),
                                ];
                                $allChannelsSuccessful = false;

                                Log::error('Failed to send notification through channel', [
                                    'notification_id' => $notification->id,
                                    'channel' => $channel,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                        // Update notification status
                        DB::transaction(function () use ($notification, $allChannelsSuccessful): void {
                            $updateData = [
                                'updated_at' => now(),
                            ];

                            if ($allChannelsSuccessful) {
                                $updateData['sent_at'] = now();
                                $updateData['delivery_status'] = 'delivered';
                            }

                            if (! $allChannelsSuccessful) {
                                $updateData['delivery_status'] = 'failed';
                            }

                            $this->notificationRepository->updateById($notification->id, $updateData);
                        });

                        $results[$notification->id] = [
                            'status' => $allChannelsSuccessful ? 'sent' : 'failed',
                            'channels' => $channelResults,
                        ];

                        if ($allChannelsSuccessful) {
                            $sentCount++;
                        }

                        if (! $allChannelsSuccessful) {
                            $failedCount++;
                        }

                    } catch (Exception $e) {
                        $results[$notification->id] = [
                            'status' => 'failed',
                            'error' => $e->getMessage(),
                        ];
                        $failedCount++;

                        Log::error('Failed to send notification', [
                            'notification_id' => $notification->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Dispatch bulk sent event
            if ($sentCount > 0) {
                $sentNotificationIds = array_keys(array_filter($results, fn (array $r): bool => $r['status'] === 'sent'));
                /** @var array<string, mixed> $eventNotificationIds */
                $eventNotificationIds = array_map('strval', $sentNotificationIds);
                Event::dispatch(new NotificationsSentEvent(
                    notificationIds: $eventNotificationIds,
                    channels: $command->channels ?? [],
                    metadata: ['sent_count' => $sentCount, 'results' => $results]
                ));
            }

        } catch (Exception $e) {
            Log::error('Bulk send notifications failed', [
                'notification_ids' => $command->notificationIds,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return [
            'sent' => $sentCount,
            'failed' => $failedCount,
            'skipped' => $skippedCount,
            'results' => $results,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getNotificationChannels(Notification $notification): array
    {
        // Default channels based on notification type and user preferences
        $channels = [];

        // Always include database channel
        $channels[] = 'database';

        // Add email for important notifications
        if (in_array($notification->priority, ['high', 'critical'])) {
            $channels[] = 'email';
        }

        // Add push for real-time notifications
        if (in_array($notification->type, [
            'campaign.approved',
            'donation.received',
            'campaign.completed',
            'system.alert',
        ])) {
            $channels[] = 'push';
        }

        return $channels;
    }
}
