<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Filament\Resources;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\Category\Domain\Model\Category;
use Modules\Category\Domain\ValueObject\CategoryStatus;
use Modules\Category\Infrastructure\Filament\Resources\CategoryResource\Pages\CreateCategory;
use Modules\Category\Infrastructure\Filament\Resources\CategoryResource\Pages\EditCategory;
use Modules\Category\Infrastructure\Filament\Resources\CategoryResource\Pages\ListCategories;
use Modules\Category\Infrastructure\Filament\Resources\CategoryResource\Pages\ViewCategory;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationLabel = 'Categories';

    protected static ?string $modelLabel = 'Category';

    protected static ?string $pluralModelLabel = 'Categories';

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-tag';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'CSR Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Category Information')
                    ->description('Basic category details and translations')
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        Translate::make()
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $context, ?string $state, $set) => $context === 'create' && $state ? $set('slug', Str::slug($state)) : null),
                                Textarea::make('description')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->helperText('Brief description of this category'),
                            ])
                            ->locales(['en', 'nl', 'fr'])
                            ->prefixLocaleLabel()
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->regex('/^[a-z0-9_-]+$/')
                            ->helperText('URL-friendly identifier (lowercase letters, numbers, underscores, hyphens only)'),
                        Select::make('status')
                            ->options([
                                CategoryStatus::ACTIVE->value => 'Active',
                                CategoryStatus::INACTIVE->value => 'Inactive',
                            ])
                            ->default(CategoryStatus::ACTIVE->value)
                            ->required(),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Categories will be ordered by this value (lower numbers appear first)'),
                    ])
                    ->columns(2),

                Section::make('Visual Design')
                    ->description('Colors and icons for UI representation')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        ColorPicker::make('color')
                            ->helperText('Category color for badges and UI elements')
                            ->hex()
                            ->nullable(),
                        Select::make('icon')
                            ->options([
                                'academic-cap' => 'Academic Cap (Education)',
                                'heart' => 'Heart (Health/Medical)',
                                'sparkles' => 'Sparkles (Environment)',
                                'home' => 'Home (Community)',
                                'exclamation-triangle' => 'Exclamation Triangle (Disaster Relief)',
                                'banknotes' => 'Bank Notes (Poverty Alleviation)',
                                'hand-raised' => 'Hand Raised (Animal Welfare)',
                                'scale' => 'Scale (Human Rights)',
                                'musical-note' => 'Musical Note (Arts & Culture)',
                                'trophy' => 'Trophy (Sports & Recreation)',
                                'tag' => 'Tag (Other)',
                                'building-office' => 'Building Office',
                                'globe-alt' => 'Globe',
                                'users' => 'Users',
                                'gift' => 'Gift',
                                'star' => 'Star',
                            ])
                            ->searchable()
                            ->nullable()
                            ->helperText('Icon to display with this category'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('icon')
                    ->icon(fn (Category $record): ?string => $record->icon ? 'heroicon-o-' . $record->icon : null)
                    ->color(fn (Category $record): string => $record->color ? 'primary' : 'gray')
                    ->size('lg'),
                TextColumn::make('name')
                    ->state(fn (Category $record): string => $record->getName())
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyMessage('Slug copied')
                    ->toggleable(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => CategoryStatus::ACTIVE->value,
                        'secondary' => CategoryStatus::INACTIVE->value,
                    ]),
                ColorColumn::make('color')
                    ->label('Color')
                    ->toggleable(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('campaigns_count')
                    ->label('Campaigns')
                    ->counts('campaigns')
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options([
                        CategoryStatus::ACTIVE->value => 'Active',
                        CategoryStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->multiple(),
            ])
            ->actions([
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
                        /** @var Collection<int, Category> $records */
                        $records->each(function (Category $category): void {
                            $category->activate();
                            $category->save();
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Activate Categories')
                    ->modalDescription('Are you sure you want to activate the selected categories?')
                    ->modalSubmitActionLabel('Activate')
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('deactivate')
                    ->label('Deactivate Selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->action(function (Collection $records): void {
                        /** @var Collection<int, Category> $records */
                        $records->each(function (Category $category): void {
                            $category->deactivate();
                            $category->save();
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Deactivate Categories')
                    ->modalDescription('Are you sure you want to deactivate the selected categories? This will hide them from campaign creation.')
                    ->modalSubmitActionLabel('Deactivate')
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->striped();
    }

    /**
     * @return array<string, mixed>
     */
    public static function getRelations(): array
    {
        return [
            // Relations will be added later if needed
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
            'view' => ViewCategory::route('/{record}'),
        ];
    }

    /** @return Builder<Category> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('campaigns');
    }

    public static function getNavigationBadge(): ?string
    {
        $inactiveCount = Category::where('status', CategoryStatus::INACTIVE)->count();

        return $inactiveCount > 0 ? (string) $inactiveCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
