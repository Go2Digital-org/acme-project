<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Event;

use DateTimeInterface;

final class TranslationCompletedEvent extends AbstractDomainEvent
{
    public ?DateTimeInterface $occurredAt;

    public function __construct(
        public readonly string $modelType,
        public readonly int $modelId,
        public readonly string $locale,
        /** @var array<int, string> $translatedFields */
        public readonly array $translatedFields,
        public readonly ?int $translatorId = null,
        public readonly ?string $translationSource = 'manual',
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'translation.completed';
    }

    /**
     * Get audit data for translation completion.
     */
    /** @return array<array-key, mixed> */
    public function getAuditData(): array
    {
        return [
            'event' => $this->getEventName(),
            'model_type' => $this->modelType,
            'model_id' => $this->modelId,
            'locale' => $this->locale,
            'translated_fields' => $this->translatedFields,
            'translator_id' => $this->translatorId,
            'translation_source' => $this->translationSource,
            'occurred_at' => $this->occurredAt,
        ];
    }

    /**
     * Check if this is a priority translation (primary locales).
     *
     * @param  array<string>  $primaryLocales
     */
    public function isPriorityTranslation(array $primaryLocales = ['en', 'nl']): bool
    {
        return in_array($this->locale, $primaryLocales, true);
    }

    /**
     * Get translation summary.
     */
    public function getSummary(): string
    {
        $fieldsCount = count($this->translatedFields);
        $fieldsText = implode(', ', $this->translatedFields);

        return "Translation to {$this->locale} completed for {$this->modelType} #{$this->modelId}: {$fieldsCount} fields ({$fieldsText})";
    }
}
