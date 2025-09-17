<?php

declare(strict_types=1);

namespace Modules\Import\Domain\ValueObject;

enum ImportRecordStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case SKIPPED = 'skipped';

    public function isProcessed(): bool
    {
        return $this !== self::PENDING;
    }

    public function isSuccessful(): bool
    {
        return $this === self::SUCCESS;
    }
}
