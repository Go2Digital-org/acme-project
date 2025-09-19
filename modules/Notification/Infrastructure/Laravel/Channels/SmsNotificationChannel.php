<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Channels;

use Exception;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

/**
 * SMS notification channel
 *
 * Sends SMS notifications via Twilio with fallback support
 * and delivery tracking
 */
final readonly class SmsNotificationChannel
{
    public function __construct(
        private ?string $twilioSid = null,
        private ?string $twilioToken = null,
        private ?string $twilioFromNumber = null,
        private ?string $fallbackApiKey = null,
    ) {}

    /**
     * Send the given notification
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Get SMS message
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);

        if (empty($message)) {
            return;
        }

        // Get phone number
        $phoneNumber = $this->getPhoneNumber($notifiable);

        if ($phoneNumber === null || $phoneNumber === '' || $phoneNumber === '0') {
            Log::warning('No phone number found for SMS notification', [
                'user_id' => $notifiable->id ?? 'anonymous',
            ]);

            return;
        }

        // Validate and format phone number
        $formattedNumber = $this->formatPhoneNumber($phoneNumber);

        if (! $formattedNumber) {
            Log::warning('Invalid phone number for SMS notification', [
                'phone' => $phoneNumber,
                'user_id' => $notifiable->id ?? 'anonymous',
            ]);

            return;
        }

        // Send SMS
        $this->sendSms($formattedNumber, $message, $notifiable);
    }

    /**
     * Send SMS message
     */
    private function sendSms(string $phoneNumber, string $message, object $notifiable): void
    {
        try {
            // Try Twilio first
            if ($this->isTwilioConfigured()) {
                $this->sendViaTwilio($phoneNumber, $message);

                return;
            }

            // Fallback to alternative SMS service
            if ($this->fallbackApiKey) {
                $this->sendViaFallback($phoneNumber, $message);

                return;
            }

            throw new Exception('No SMS service configured');
        } catch (Exception $e) {
            Log::error('SMS notification failed', [
                'phone' => $phoneNumber,
                'message_length' => strlen($message),
                'user_id' => $notifiable->id ?? 'anonymous',
                'error' => $e->getMessage(),
            ]);

            // In production, you might want to retry or use a different service
            throw $e;
        }
    }

    /**
     * Send SMS via Twilio
     */
    private function sendViaTwilio(string $phoneNumber, string $message): void
    {
        // Check if Twilio SDK is available
        if (! class_exists('Twilio\Rest\Client')) {
            throw new Exception('Twilio SDK not installed. Please install twilio/sdk package.');
        }

        $client = new Client($this->twilioSid, $this->twilioToken);

        $twilioMessage = $client->messages->create(
            $phoneNumber,
            [
                'from' => $this->twilioFromNumber,
                'body' => $this->truncateMessage($message),
                'statusCallback' => route('api.sms.status-callback'),
            ]
        );

        Log::info('SMS sent via Twilio', [
            'phone' => $phoneNumber,
            'message_sid' => $twilioMessage->sid,
            'status' => $twilioMessage->status,
        ]);
    }

    /**
     * Send SMS via fallback service
     */
    private function sendViaFallback(string $phoneNumber, string $message): void
    {
        // Example using a generic SMS API
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->fallbackApiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.sms-service.com/v1/messages', [
            'to' => $phoneNumber,
            'from' => 'ACME CSR',
            'text' => $this->truncateMessage($message),
            'webhook_url' => route('api.sms.webhook'),
        ]);

        if (! $response->successful()) {
            throw new Exception('Fallback SMS service failed: ' . $response->body());
        }

        $result = $response->json();

        Log::info('SMS sent via fallback service', [
            'phone' => $phoneNumber,
            'message_id' => $result['id'] ?? 'unknown',
            'status' => $result['status'] ?? 'unknown',
        ]);
    }

    /**
     * Get phone number from notifiable
     */
    private function getPhoneNumber(object $notifiable): ?string
    {
        // Try different phone number fields
        return $notifiable->phone
            ?? $notifiable->mobile
            ?? $notifiable->phone_number
            ?? $notifiable->mobile_number
            ?? null;
    }

    /**
     * Format and validate phone number
     */
    private function formatPhoneNumber(string $phoneNumber): ?string
    {
        // Remove all non-digit characters
        $cleaned = preg_replace('/[^\d]/', '', $phoneNumber);

        // Validate length (assuming US/international format)
        if (strlen((string) $cleaned) < 10 || strlen((string) $cleaned) > 15) {
            return null;
        }

        // Add country code if missing (assuming US)
        if (strlen((string) $cleaned) === 10) {
            $cleaned = '1' . $cleaned;
        }

        // Format with + prefix
        return '+' . $cleaned;
    }

    /**
     * Truncate message to SMS limits
     */
    private function truncateMessage(string $message): string
    {
        // SMS limit is 160 characters for single message
        // 70 characters for Unicode (emoji, special chars)
        $limit = mb_strlen($message) === strlen($message) ? 160 : 70;

        if (mb_strlen($message) <= $limit) {
            return $message;
        }

        // Truncate and add ellipsis
        return mb_substr($message, 0, $limit - 3) . '...';
    }

    /**
     * Check if Twilio is properly configured
     */
    private function isTwilioConfigured(): bool
    {
        return $this->twilioSid !== null && $this->twilioSid !== '' && $this->twilioSid !== '0'
            && ($this->twilioToken !== null && $this->twilioToken !== '' && $this->twilioToken !== '0')
            && ($this->twilioFromNumber !== null && $this->twilioFromNumber !== '' && $this->twilioFromNumber !== '0');
    }

    /**
     * Handle SMS delivery status callback
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function handleStatusCallback(array $data): void
    {
        Log::info('SMS status callback received', $data);

        // Update delivery status in database
        // Track metrics for analytics
        // Handle failed deliveries
    }

    /**
     * Get SMS delivery statistics
     */
    /**
     * @return array<string, mixed>
     */
    public function getDeliveryStats(): array
    {
        // In a real implementation, fetch from analytics service
        return [
            'total_sent' => 1250,
            'delivered' => 1180,
            'failed' => 45,
            'pending' => 25,
            'delivery_rate' => 94.4,
            'average_cost' => 0.0075, // USD per message
        ];
    }

    /**
     * Estimate SMS cost
     */
    public function estimateCost(string $message, string $phoneNumber): float
    {
        $messageCount = ceil(mb_strlen($message) / 160);
        $baseRate = $this->isInternational($phoneNumber) ? 0.05 : 0.0075;

        return $messageCount * $baseRate;
    }

    /**
     * Check if phone number is international
     */
    private function isInternational(string $phoneNumber): bool
    {
        $cleaned = preg_replace('/[^\d]/', '', $phoneNumber);

        // US numbers start with 1 and have 11 digits
        return ! (strlen((string) $cleaned) === 11 && str_starts_with((string) $cleaned, '1'));
    }
}
