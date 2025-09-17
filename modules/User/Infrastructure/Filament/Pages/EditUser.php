<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Filament\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\User\Infrastructure\Filament\Resources\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        $url = $this->getResource()::getUrl('index');

        return is_string($url) ? $url : static::$resource::getUrl('index');
    }
}
