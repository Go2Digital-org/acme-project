<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Filament\Resources\DonationResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Donation\Infrastructure\Filament\Resources\DonationResource;

class ViewDonation extends ViewRecord
{
    protected static string $resource = DonationResource::class;

    public function getTitle(): string
    {
        return 'View Donation';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-o-pencil'),
            DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }
}
