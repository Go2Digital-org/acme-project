<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Filament\Resources\OrganizationResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Organization\Infrastructure\Filament\Resources\OrganizationResource;

class ListOrganizations extends ListRecords
{
    protected static string $resource = OrganizationResource::class;

    public function getTitle(): string
    {
        return 'Organizations';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Organization')
                ->icon('heroicon-o-plus'),
        ];
    }
}
