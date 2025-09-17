<?php

declare(strict_types=1);

namespace Modules\Localization\Application\Command;

final class SwitchLocaleCommand
{
    public function __construct(
        public string $locale,
        public ?string $userId = null
    ) {}
}
