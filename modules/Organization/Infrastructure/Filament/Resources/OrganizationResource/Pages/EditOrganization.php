<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Filament\Resources\OrganizationResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Infrastructure\Filament\Resources\OrganizationResource;

class EditOrganization extends EditRecord
{
    protected static string $resource = OrganizationResource::class;

    public function getTitle(): string
    {
        return 'Edit Organization';
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure record is an Organization instance before accessing properties
        if (! $this->record instanceof Organization) {
            return $data;
        }

        // Set verification date if marked as verified for the first time
        if ($data['is_verified'] && ! $this->record->is_verified && empty($data['verification_date'])) {
            $data['verification_date'] = now();
        }

        // Clear verification date if unverified
        if (! $data['is_verified']) {
            $data['verification_date'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
