<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Filament\Resources\CategoryResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Modules\Category\Domain\Model\Category;
use Modules\Category\Infrastructure\Filament\Resources\CategoryResource;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    /**
     * @return array<int, DeleteAction|ViewAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->before(function (): void {
                    /** @var Category $record */
                    $record = $this->record;

                    if ($record->hasActiveCampaigns()) {
                        $this->halt();
                        Notification::make()
                            ->title('Cannot delete category with active campaigns')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
