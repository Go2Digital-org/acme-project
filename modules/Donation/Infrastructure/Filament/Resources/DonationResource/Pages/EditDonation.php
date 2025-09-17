<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Filament\Resources\DonationResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Donation\Infrastructure\Filament\Resources\DonationResource;

class EditDonation extends EditRecord
{
    protected static string $resource = DonationResource::class;

    public function getTitle(): string
    {
        return 'Edit Donation';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->icon('heroicon-o-eye'),
            DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
