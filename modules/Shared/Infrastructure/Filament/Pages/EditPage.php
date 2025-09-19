<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Filament\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Filament\Resources\PageResource;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    public function getTitle(): string
    {
        return 'Edit Page';
    }

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-generate slug if empty and title changed
        if (empty($data['slug']) && ! empty($data['title'])) {
            $title = is_array($data['title']) ? ($data['title']['en'] ?? reset($data['title'])) : $data['title'];
            $data['slug'] = Str::slug($title);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
