<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

/**
 * Notification message value object
 */
class Message implements Stringable
{
    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        public readonly ?string $htmlBody = null
    ) {
        if (trim($subject) === '') {
            throw new InvalidArgumentException('Message subject cannot be empty');
        }

        if (trim($body) === '') {
            throw new InvalidArgumentException('Message body cannot be empty');
        }

        if (strlen($subject) > 255) {
            throw new InvalidArgumentException('Message subject cannot exceed 255 characters');
        }

        if (strlen($body) > 65535) {
            throw new InvalidArgumentException('Message body cannot exceed 65535 characters');
        }

        if ($htmlBody !== null && strlen($htmlBody) > 65535) {
            throw new InvalidArgumentException('Message HTML body cannot exceed 65535 characters');
        }
    }

    public static function plainText(string $subject, string $body): self
    {
        return new self($subject, $body);
    }

    public static function withHtml(string $subject, string $body, string $htmlBody): self
    {
        return new self($subject, $body, $htmlBody);
    }

    public function hasHtmlBody(): bool
    {
        return $this->htmlBody !== null;
    }

    public function getPreview(int $length = 100): string
    {
        $preview = strip_tags($this->body);

        if (strlen($preview) <= $length) {
            return $preview;
        }

        return substr($preview, 0, $length) . '...';
    }

    public function equals(Message $other): bool
    {
        return $this->subject === $other->subject
            && $this->body === $other->body
            && $this->htmlBody === $other->htmlBody;
    }

    public function __toString(): string
    {
        return $this->subject;
    }
}
