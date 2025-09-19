<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Filament\Resources\CurrencyResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Currency\Domain\Model\Currency;
use Modules\Currency\Infrastructure\Filament\Resources\CurrencyResource;

class EditCurrency extends EditRecord
{
    protected static string $resource = CurrencyResource::class;

    /**
     * @return array<int, \Filament\Actions\DeleteAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn ($record) => $record->is_default),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If this is set as default, unset other defaults
        if ($data['is_default'] ?? false) {
            $record = $this->record;

            if ($record instanceof Currency) {
                Currency::where('is_default', true)
                    ->where('id', '!=', $record->id)
                    ->update(['is_default' => false]);
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
