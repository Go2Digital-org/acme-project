<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Filament\Resources\DonationResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Donation\Infrastructure\Filament\Resources\DonationResource;

class ListDonations extends ListRecords
{
    protected static string $resource = DonationResource::class;

    public function getTitle(): string
    {
        return 'Donations';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Record Donation')
                ->icon('heroicon-o-plus'),
        ];
    }
}
