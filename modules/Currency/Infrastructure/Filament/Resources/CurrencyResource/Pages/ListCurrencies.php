<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Filament\Resources\CurrencyResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Currency\Infrastructure\Filament\Resources\CurrencyResource;

class ListCurrencies extends ListRecords
{
    protected static string $resource = CurrencyResource::class;

    /**
     * @return array<int, \Filament\Actions\CreateAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
