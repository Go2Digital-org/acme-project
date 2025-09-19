<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Filament\Resources\DonationResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Donation\Infrastructure\Filament\Resources\DonationResource;

class CreateDonation extends CreateRecord
{
    protected static string $resource = DonationResource::class;

    public function getTitle(): string
    {
        return 'Record New Donation';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default timestamp if not provided
        if (empty($data['donated_at'])) {
            $data['donated_at'] = now();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
