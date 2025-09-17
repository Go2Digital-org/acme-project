<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Filament\Resources\SocialMediaResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Shared\Infrastructure\Filament\Resources\SocialMediaResource;

class CreateSocialMedia extends CreateRecord
{
    protected static string $resource = SocialMediaResource::class;

    public function getTitle(): string
    {
        return 'Create Social Media Link';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Social media link created successfully';
    }
}
