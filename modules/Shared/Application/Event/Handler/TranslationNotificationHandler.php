<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Event\Handler;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Application\Event\TranslationCompletedEvent;
use Modules\Shared\Infrastructure\Notifications\AdminNotificationService;

final readonly class TranslationNotificationHandler implements ShouldQueue
{
    public function __construct(
        private AdminNotificationService $notificationService,
    ) {}

    public function handle(TranslationCompletedEvent $event): void
    {
        // Only notify for priority translations
        if (! $event->isPriorityTranslation()) {
            return;
        }

        // Send notification to admins about translation completion
        // Transform array<int, string> to array<string, mixed> for PHPStan compliance
        // Create associative array with field names as both keys and values to preserve
        // field names for implode() operation in the notification service
        $transformedFields = array_combine($event->translatedFields, $event->translatedFields) ?: [];

        $this->notificationService->notifyTranslationCompleted(
            modelType: $event->modelType,
            modelId: $event->modelId,
            locale: $event->locale,
            translatedFields: $transformedFields,
            translatorId: $event->translatorId,
        );

        // Log translation completion for audit
        Log::info('Translation completed', $event->getAuditData());
    }
}
