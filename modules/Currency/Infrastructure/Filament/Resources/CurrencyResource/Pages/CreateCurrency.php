<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Filament\Resources\CurrencyResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Currency\Domain\Model\Currency;
use Modules\Currency\Infrastructure\Filament\Resources\CurrencyResource;

class CreateCurrency extends CreateRecord
{
    protected static string $resource = CurrencyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If this is set as default, unset other defaults
        if ($data['is_default'] ?? false) {
            Currency::where('is_default', true)
                ->update(['is_default' => false]);
        }

        return $data;
    }
}
