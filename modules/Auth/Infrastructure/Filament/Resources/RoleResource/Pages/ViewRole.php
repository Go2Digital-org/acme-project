<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Filament\Resources\RoleResource\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Auth\Infrastructure\Filament\Resources\RoleResource;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    /**
     * @return array<int, EditAction>
     */
    protected function getActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
