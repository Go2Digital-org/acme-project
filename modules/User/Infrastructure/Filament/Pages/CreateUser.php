<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Filament\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\User\Infrastructure\Filament\Resources\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        $url = $this->getResource()::getUrl('index');

        return is_string($url) ? $url : static::$resource::getUrl('index');
    }
}
