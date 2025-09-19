<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Exception;

use Exception;

abstract class ApiException extends Exception
{
    protected int $statusCode = 400;

    protected string $errorCode = 'API_ERROR';

    protected ?string $translationKey = null;

    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        string $message = '',
        /** @var array<string, mixed> */
        protected array $details = [],
        ?int $statusCode = null,
        ?string $errorCode = null,
        ?string $translationKey = null,
    ) {
        parent::__construct($message);

        if ($statusCode !== null) {
            $this->statusCode = $statusCode;
        }

        if ($errorCode !== null) {
            $this->errorCode = $errorCode;
        }

        if ($translationKey !== null) {
            $this->translationKey = $translationKey;
        }
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get localized error message.
     */
    public function getLocalizedMessage(string $locale = 'en'): string
    {
        if ($this->translationKey) {
            return __($this->translationKey, [], $locale);
        }

        return $this->getMessage();
    }

    /**
     * @deprecated Use getDetails() instead
     */
    /**
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->getDetails();
    }
}
