<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Filament\Resources\CampaignResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Campaign\Infrastructure\Filament\Resources\CampaignResource;

class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('campaigns.new_campaign'))
                ->icon('heroicon-o-plus'),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('campaigns.all'))
                ->badge($this->getResource()::getEloquentQuery()->count()),

            'needs_approval' => Tab::make(__('campaigns.needs_approval'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CampaignStatus::PENDING_APPROVAL->value))
                ->badge($this->getResource()::getEloquentQuery()->where('status', CampaignStatus::PENDING_APPROVAL->value)->count()),

            'active' => Tab::make(__('campaigns.active'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CampaignStatus::ACTIVE->value))
                ->badge($this->getResource()::getEloquentQuery()->where('status', CampaignStatus::ACTIVE->value)->count()),

            'draft' => Tab::make(__('campaigns.draft'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CampaignStatus::DRAFT->value))
                ->badge($this->getResource()::getEloquentQuery()->where('status', CampaignStatus::DRAFT->value)->count()),

            'completed' => Tab::make(__('campaigns.completed_tab'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CampaignStatus::COMPLETED->value))
                ->badge($this->getResource()::getEloquentQuery()->where('status', CampaignStatus::COMPLETED->value)->count()),
        ];
    }
}
