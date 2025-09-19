<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Filament\Resources\SocialMediaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Shared\Infrastructure\Filament\Resources\SocialMediaResource;

class EditSocialMedia extends EditRecord
{
    protected static string $resource = SocialMediaResource::class;

    public function getTitle(): string
    {
        return 'Edit Social Media Link';
    }

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Social media link updated successfully';
    }
}
