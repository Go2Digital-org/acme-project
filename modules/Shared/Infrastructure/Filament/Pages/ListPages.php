<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Filament\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Modules\Shared\Domain\Model\Page;
use Modules\Shared\Infrastructure\Filament\Resources\PageResource;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    public function getTitle(): string
    {
        return 'Pages Management';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Page')
                ->icon('heroicon-o-plus'),

            Action::make('bulk_publish')
                ->label('Publish All Draft Pages')
                ->icon('heroicon-o-eye')
                ->color('success')
                ->requiresConfirmation()
                ->action(function (): void {
                    $count = Page::query()->where('status', 'draft')->update(['status' => 'published']);

                    Notification::make()
                        ->title("{$count} pages published successfully")
                        ->success()
                        ->send();
                }),
        ];
    }
}
