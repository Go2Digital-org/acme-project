<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Filament\Resources;

use DB;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Domain\Model\SocialMedia;
use Modules\Shared\Infrastructure\Filament\Resources\SocialMediaResource\Pages\CreateSocialMedia;
use Modules\Shared\Infrastructure\Filament\Resources\SocialMediaResource\Pages\EditSocialMedia;
use Modules\Shared\Infrastructure\Filament\Resources\SocialMediaResource\Pages\ListSocialMedia;

class SocialMediaResource extends Resource
{
    protected static ?string $model = SocialMedia::class;

    protected static ?string $navigationLabel = 'Social Media';

    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-share';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Content Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Social Media Details')
                ->description('Configure the social media platform and URL')
                ->columnSpanFull()
                ->collapsible()
                ->schema([
                    Select::make('platform')
                        ->options(SocialMedia::PLATFORMS)
                        ->required(),

                    TextInput::make('url')
                        ->required()
                        ->url()
                        ->maxLength(255),

                    TextInput::make('icon')
                        ->maxLength(100)
                        ->helperText('Leave empty to use default platform icon'),
                ]),

            Section::make('Display Settings')
                ->description('Configure how this social media link appears on the site')
                ->columnSpanFull()
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextInput::make('order')
                        ->numeric()
                        ->default(fn (): int|float => (DB::table('social_media')->max('order') ?? 0) + 1)
                        ->required(),

                    Toggle::make('is_active')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('platform')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => SocialMedia::PLATFORMS[$state] ?? $state,
                    ),

                TextColumn::make('url')
                    ->limit(50),

                TextColumn::make('order')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('platform')
                    ->options(SocialMedia::PLATFORMS),

                TernaryFilter::make('is_active')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
                BulkAction::make('activate')
                    ->label('Activate Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Collection $records): void {
                        /* @var Collection<int, SocialMedia> $records */
                        $records->each(function (Model $socialMedia, int|string $key): void {
                            /** @var SocialMedia $socialMedia */
                            $socialMedia->update(['is_active' => true]);
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Activate Selected Social Media Links')
                    ->modalDescription('Are you sure you want to activate the selected social media links? They will become visible on the website.')
                    ->modalSubmitActionLabel('Activate Links')
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('deactivate')
                    ->label('Deactivate Selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->action(function (Collection $records): void {
                        /* @var Collection<int, SocialMedia> $records */
                        $records->each(function (Model $socialMedia, int|string $key): void {
                            /** @var SocialMedia $socialMedia */
                            $socialMedia->update(['is_active' => false]);
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Deactivate Selected Social Media Links')
                    ->modalDescription('Are you sure you want to deactivate the selected social media links? They will no longer be visible on the website.')
                    ->modalSubmitActionLabel('Deactivate Links')
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('reorder')
                    ->label('Reorder Selected')
                    ->icon('heroicon-o-bars-3')
                    ->color('info')
                    ->form([
                        Repeater::make('order_updates')
                            ->label('Update Order')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Platform')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('current_order')
                                    ->label('Current Order')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('new_order')
                                    ->label('New Order')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1),
                            ])
                            ->columns(3)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->default(fn (Collection $records) => $records->map(function (Model $record): array {
                                /** @var SocialMedia $record */
                                return [
                                    'id' => $record->id,
                                    'name' => SocialMedia::PLATFORMS[$record->platform] ?? $record->platform,
                                    'current_order' => $record->order,
                                    'new_order' => $record->order,
                                ];
                            })->toArray()),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        /** @var array<string, mixed> $update */
                        foreach ($data['order_updates'] as $update) {
                            if (isset($update['id'], $update['new_order'])) {
                                $record = $records->firstWhere('id', $update['id']);

                                if ($record) {
                                    $record->update(['order' => (int) $update['new_order']]);
                                }
                            }
                        }
                    })
                    ->modalHeading('Reorder Selected Social Media Links')
                    ->modalSubmitActionLabel('Update Order')
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSocialMedia::route('/'),
            'create' => CreateSocialMedia::route('/create'),
            'edit' => EditSocialMedia::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<SocialMedia>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<SocialMedia> $query */
        $query = parent::getEloquentQuery();

        return $query->ordered();
    }
}
