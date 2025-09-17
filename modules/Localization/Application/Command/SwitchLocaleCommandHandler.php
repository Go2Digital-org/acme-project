<?php

declare(strict_types=1);

namespace Modules\Localization\Application\Command;

use Modules\Localization\Domain\ValueObject\Locale;

final readonly class SwitchLocaleCommandHandler
{
    /**
     * @return array{success: bool, locale: string, locale_name: string, user_id: ?string}
     */
    public function handle(SwitchLocaleCommand $command): array
    {
        $locale = new Locale($command->locale);

        // This would typically interact with a session service or user preference service
        // For now, we return the result
        return [
            'success' => true,
            'locale' => $locale->getCode(),
            'locale_name' => $locale->getName(),
            'user_id' => $command->userId,
        ];
    }
}
