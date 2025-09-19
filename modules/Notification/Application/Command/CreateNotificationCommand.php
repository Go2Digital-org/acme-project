<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use DateTime;
use InvalidArgumentException;
use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command to create a new notification.
 *
 * Encapsulates all data needed to create a notification following CQRS patterns.
 */
final readonly class CreateNotificationCommand implements CommandInterface
{
    /**
     * @param  array<string, mixed>  $data  Additional notification data
     * @param  array<string, mixed>  $metadata  Notification metadata
     */
    public function __construct(
        public string $recipientId,
        public string $title,
        public string $message,
        public string $type,
        public string $channel = 'database',
        public string $priority = 'normal',
        public ?string $senderId = null,
        /** @var array<string, mixed> */
        public array $data = [],
        /** @var array<string, mixed> */
        public array $metadata = [],
        public ?DateTime $scheduledFor = null,
    ) {}

    /**
     * Create command from array data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Validate and cast required string fields
        $recipientId = $data['notifiable_id'] ?? null;
        if (! is_string($recipientId)) {
            throw new InvalidArgumentException('notifiable_id must be a string');
        }

        $title = $data['title'] ?? null;
        if (! is_string($title)) {
            throw new InvalidArgumentException('title must be a string');
        }

        $message = $data['message'] ?? null;
        if (! is_string($message)) {
            throw new InvalidArgumentException('message must be a string');
        }

        $type = $data['type'] ?? null;
        if (! is_string($type)) {
            throw new InvalidArgumentException('type must be a string');
        }

        // Validate optional fields
        $channel = $data['channel'] ?? 'database';
        if (! is_string($channel)) {
            throw new InvalidArgumentException('channel must be a string');
        }

        $priority = $data['priority'] ?? 'normal';
        if (! is_string($priority)) {
            throw new InvalidArgumentException('priority must be a string');
        }

        $senderId = $data['sender_id'] ?? null;
        if ($senderId !== null && ! is_string($senderId)) {
            throw new InvalidArgumentException('sender_id must be a string or null');
        }

        $dataArray = $data['data'] ?? [];
        if (! is_array($dataArray)) {
            throw new InvalidArgumentException('data must be an array');
        }

        $metadataArray = $data['metadata'] ?? [];
        if (! is_array($metadataArray)) {
            throw new InvalidArgumentException('metadata must be an array');
        }

        $scheduledFor = null;
        if (isset($data['scheduled_for'])) {
            $scheduledValue = $data['scheduled_for'];
            if (! is_string($scheduledValue)) {
                throw new InvalidArgumentException('scheduled_for must be a string');
            }
            $scheduledFor = new DateTime($scheduledValue);
        }

        return new self(
            recipientId: $recipientId,
            title: $title,
            message: $message,
            type: $type,
            channel: $channel,
            priority: $priority,
            senderId: $senderId,
            data: $dataArray,
            metadata: $metadataArray,
            scheduledFor: $scheduledFor,
        );
    }

    /**
     * Convert command to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'notifiable_id' => $this->recipientId,
            'sender_id' => $this->senderId,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'channel' => $this->channel,
            'priority' => $this->priority,
            'data' => $this->data,
            'metadata' => $this->metadata,
            'scheduled_for' => $this->scheduledFor?->format('Y-m-d H:i:s'),
        ];
    }
}
