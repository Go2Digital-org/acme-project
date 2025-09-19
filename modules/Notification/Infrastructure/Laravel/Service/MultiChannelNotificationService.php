<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Service;

use DateTime;
use Exception;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Notification\Domain\ValueObject\NotificationPriority;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Multi-channel notification service
 *
 * Handles sending notifications across multiple channels with
 * rate limiting, retry logic, and delivery tracking.
 */
final readonly class MultiChannelNotificationService
{
    public function __construct(
        private NotificationPreferencesService $preferencesService,
        private NotificationAnalyticsService $analyticsService,
    ) {}

    /**
     * Send notification to user with multi-channel support
     */
    public function sendToUser(User $user, Notification $notification): void
    {
        try {
            // Get user preferences
            $preferences = $this->preferencesService->getPreferences($user);

            // Track delivery attempt
            $this->analyticsService->trackDeliveryAttempt($notification, $user);

            // Send notification with preferences
            $user->notify($notification);

            // Track successful delivery
            $this->analyticsService->trackDeliverySuccess($notification, $user);

        } catch (Exception $e) {
            Log::error('Multi-channel notification failed', [
                'user_id' => $user->id,
                'notification' => $notification::class,
                'error' => $e->getMessage(),
            ]);

            $this->analyticsService->trackDeliveryFailure($notification, $user, $e->getMessage());
        }
    }

    /**
     * Send notification to multiple users
     */
    /**
     * @param  array<string, mixed>  $users
     */
    public function sendToUsers(array $users, Notification $notification): void
    {
        foreach ($users as $user) {
            $this->sendToUser($user, $notification);
        }
    }

    /**
     * Send notification to email address (for non-registered users)
     */
    public function sendToEmail(string $email, Notification $notification): void
    {
        try {
            $notifiable = new AnonymousNotifiable;
            $notifiable->route('mail', $email);

            // Rate limit email notifications
            $key = "email-notification:{$email}";
            if (RateLimiter::tooManyAttempts($key, 10)) {
                Log::warning('Email notification rate limited', ['email' => $email]);

                return;
            }

            RateLimiter::hit($key, 60); // 10 per minute

            NotificationFacade::send($notifiable, $notification);

            $this->analyticsService->trackEmailDelivery($email, $notification);

        } catch (Exception $e) {
            Log::error('Email notification failed', [
                'email' => $email,
                'notification' => $notification::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send SMS notification
     */
    public function sendSms(string $phoneNumber, string $message): void
    {
        try {
            // Rate limit SMS notifications (higher cost)
            $key = "sms-notification:{$phoneNumber}";
            if (RateLimiter::tooManyAttempts($key, 5)) {
                Log::warning('SMS notification rate limited', ['phone' => $phoneNumber]);

                return;
            }

            RateLimiter::hit($key, 60); // 5 per minute

            $notifiable = new AnonymousNotifiable;
            $notifiable->route('sms', $phoneNumber);

            // Create simple SMS notification
            $notification = new class($message) extends Notification
            {
                public function __construct(private readonly string $message) {}

                /**
                 * @return array<int, string>
                 */
                public function via(object $notifiable): array
                {
                    return ['sms'];
                }

                public function toSms(object $notifiable): string
                {
                    return $this->message;
                }
            };

            NotificationFacade::send($notifiable, $notification);

            $this->analyticsService->trackSmsDelivery($phoneNumber, $message);

        } catch (Exception $e) {
            Log::error('SMS notification failed', [
                'phone' => $phoneNumber,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send push notification to device token
     */
    /**
     * @param  array<string, mixed>  $payload
     */
    public function sendPushNotification(string $deviceToken, array $payload): void
    {
        try {
            // Rate limit push notifications
            $key = "push-notification:{$deviceToken}";
            if (RateLimiter::tooManyAttempts($key, 20)) {
                Log::warning('Push notification rate limited', ['device' => $deviceToken]);

                return;
            }

            RateLimiter::hit($key, 60); // 20 per minute

            // In a real implementation, you would use Firebase Cloud Messaging
            // or Apple Push Notification Service here
            Log::info('Push notification sent', [
                'device_token' => $deviceToken,
                'payload' => $payload,
            ]);

            $this->analyticsService->trackPushDelivery($deviceToken, $payload);

        } catch (Exception $e) {
            Log::error('Push notification failed', [
                'device_token' => $deviceToken,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send bulk notifications with batching
     */
    /**
     * @param  array<string, mixed>  $recipients
     */
    public function sendBulk(array $recipients, Notification $notification): void
    {
        $batches = array_chunk($recipients, 100); // Process in batches of 100

        foreach ($batches as $batch) {
            foreach ($batch as $recipient) {
                if ($recipient instanceof User) {
                    $this->sendToUser($recipient, $notification);
                } elseif (is_string($recipient)) {
                    $this->sendToEmail($recipient, $notification);
                }
            }

            // Small delay between batches to avoid overwhelming external services
            usleep(100000); // 100ms
        }
    }

    /**
     * Send scheduled notification
     */
    public function sendScheduled(User $user, Notification $notification, DateTime $scheduledFor): void
    {
        // In a real implementation, you would use Laravel's delayed jobs
        dispatch(function () use ($user, $notification): void {
            $this->sendToUser($user, $notification);
        })->delay($scheduledFor);
    }

    /**
     * Send notification with retry logic
     */
    public function sendWithRetry(User $user, Notification $notification, int $maxRetries = 3): void
    {
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $this->sendToUser($user, $notification);

                return; // Success, exit retry loop
            } catch (Exception $e) {
                $attempt++;
                Log::warning('Notification delivery failed, retrying', [
                    'user_id' => $user->id,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt >= $maxRetries) {
                    Log::error('Notification delivery failed after all retries', [
                        'user_id' => $user->id,
                        'notification' => $notification::class,
                        'attempts' => $attempt,
                    ]);
                    throw $e;
                }

                // Exponential backoff: wait 2^attempt seconds
                sleep(2 ** $attempt);
            }
        }
    }

    /**
     * Get notification delivery statistics
     */
    /**
     * @return array<string, mixed>
     */
    public function getDeliveryStats(): array
    {
        return $this->analyticsService->getDeliveryMetrics();
    }

    /**
     * Process notification queue
     */
    public function processNotificationQueue(): void
    {
        // Process critical notifications first
        $this->processByPriority(NotificationPriority::CRITICAL);
        $this->processByPriority(NotificationPriority::URGENT);
        $this->processByPriority(NotificationPriority::HIGH);
        $this->processByPriority(NotificationPriority::NORMAL);
        $this->processByPriority(NotificationPriority::LOW);
    }

    /**
     * Process notifications by priority
     */
    private function processByPriority(string $priority): void
    {
        // In a real implementation, this would fetch pending notifications
        // from the queue and process them based on priority
        Log::info("Processing {$priority} priority notifications");
    }
}
