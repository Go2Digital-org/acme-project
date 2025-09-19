<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Model;

use Illuminate\Database\Eloquent\Model;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationPriority;
use Modules\Notification\Domain\Enum\NotificationType;
use Modules\Notification\Domain\ValueObject\Message;

/**
 * NotificationTemplate Domain Model
 *
 * @property string $name
 * @property NotificationType $type
 * @property NotificationChannel $channel
 * @property string $subject_template
 * @property string $body_template
 * @property string|null $html_body_template
 * @property NotificationPriority $priority
 * @property bool $is_active
 * @property array<string, mixed> $metadata
 * @property int|null $organization_id
 */
class NotificationTemplate extends Model
{
    protected $fillable = [
        'name',
        'type',
        'channel',
        'subject_template',
        'body_template',
        'html_body_template',
        'priority',
        'is_active',
        'metadata',
        'organization_id',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'channel' => NotificationChannel::class,
        'priority' => NotificationPriority::class,
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Render the template with provided variables
     */
    /**
     * @param  array<string, mixed>  $variables
     */
    public function render(array $variables = []): Message
    {
        $subject = $this->renderTemplate($this->subject_template, $variables);
        $body = $this->renderTemplate($this->body_template, $variables);
        $htmlBody = $this->html_body_template
            ? $this->renderTemplate($this->html_body_template, $variables)
            : null;

        return new Message($subject, $body, $htmlBody);
    }

    /**
     * Check if template supports given notification type
     */
    public function supportsType(NotificationType $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Check if template supports given channel
     */
    public function supportsChannel(NotificationChannel $channel): bool
    {
        return $this->channel === $channel;
    }

    /**
     * Check if template is active and can be used
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Get template priority for sorting/processing
     */
    public function getPriority(): NotificationPriority
    {
        return $this->priority;
    }

    /**
     * Simple template rendering with variable substitution
     */
    /**
     * @param  array<string, mixed>  $variables
     */
    private function renderTemplate(string $template, array $variables): string
    {
        $rendered = $template;

        foreach ($variables as $key => $value) {
            // Handle nested array access with dot notation
            if (is_array($value)) {
                $rendered = $this->renderNestedVariables($rendered, $key, $value);

                continue;
            }

            $placeholder = '{{' . $key . '}}';
            $rendered = str_replace($placeholder, (string) $value, $rendered);
        }

        return $rendered;
    }

    /**
     * Handle nested variable rendering
     */
    /**
     * @param  array<string, mixed>  $values
     */
    private function renderNestedVariables(string $template, string $prefix, array $values): string
    {
        foreach ($values as $key => $value) {
            $placeholder = '{{' . $prefix . '.' . $key . '}}';

            if (is_array($value)) {
                $template = $this->renderNestedVariables($template, $prefix . '.' . $key, $value);

                continue;
            }

            $template = str_replace($placeholder, (string) $value, $template);
        }

        return $template;
    }

    /**
     * Validate template syntax
     */
    public function validateTemplate(): bool
    {
        // Check for balanced braces
        $subjectBraces = $this->countBraces($this->subject_template);
        $bodyBraces = $this->countBraces($this->body_template);

        if ($subjectBraces !== 0 || $bodyBraces !== 0) {
            return false;
        }

        // Check HTML template if exists
        if ($this->html_body_template) {
            $htmlBraces = $this->countBraces($this->html_body_template);
            if ($htmlBraces !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Count unmatched braces in template
     */
    private function countBraces(string $template): int
    {
        $openBraces = substr_count($template, '{{');
        $closeBraces = substr_count($template, '}}');

        return $openBraces - $closeBraces;
    }
}
