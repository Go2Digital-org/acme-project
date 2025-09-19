<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Filament\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Filament\Resources\PageResource;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    public function getTitle(): string
    {
        return 'Create New Page';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate slug if not provided and title exists
        if (empty($data['slug']) && ! empty($data['title'])) {
            // Use the English title if available
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
