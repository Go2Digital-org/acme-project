<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Filament\Resources\SocialMediaResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Shared\Infrastructure\Filament\Resources\SocialMediaResource;

class ListSocialMedia extends ListRecords
{
    protected static string $resource = SocialMediaResource::class;

    public function getTitle(): string
    {
        return 'Social Media Links';
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
