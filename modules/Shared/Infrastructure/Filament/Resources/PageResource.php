<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemasSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Shared\Domain\Model\Page;
use Modules\Shared\Infrastructure\Filament\Pages\CreatePage;
use Modules\Shared\Infrastructure\Filament\Pages\EditPage;
use Modules\Shared\Infrastructure\Filament\Pages\ListPages;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Content Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            SchemasSection::make('Page Information')
                ->description('Basic page details and settings')
                ->columnSpanFull()
                ->collapsible()
                ->schema([
                    Translate::make()
                        ->schema([
                            TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Enter page title...')
                                ->live(),
                        ])
                        ->locales(['en', 'nl', 'fr'])
                        ->columnSpanFull(),

                    TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->helperText('URL-friendly version of the page. Auto-generated from title if left empty.')
                        ->suffixAction(
                            Action::make('generate_slug')
                                ->icon('heroicon-m-arrow-path')
                                ->action(function (Get $get, $set): void {
                                    $title = $get('title.en') ?? $get('title.nl') ?? $get('title.fr');

                                    if ($title) {
                                        $set('slug', Str::slug($title));
                                    }
                                }),
                        ),

                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'published' => 'Published',
                        ])
                        ->default('draft')
                        ->required()
                        ->live(),

                    TextInput::make('order')
                        ->numeric()
                        ->default(0)
                        ->step(1)
                        ->helperText('Order for navigation (lower numbers appear first)'),
                ]),

            SchemasSection::make('Page Content')
                ->description('Page content in multiple languages')
                ->columnSpanFull()
                ->collapsible()
                ->collapsed()
                ->schema([
                    Translate::make()
                        ->schema([
                            // NOTE: RichEditor has compatibility issues with JSON translation arrays
                            // See: https://github.com/solutionforest/filament-translate-field/issues/26
                            // Using Textarea as workaround until the issue is resolved
                            Textarea::make('content')
                                ->rows(8)
                                ->placeholder('Write your page content here...'),
                        ])
                        ->locales(['en', 'nl', 'fr'])
                        ->columnSpanFull(),
                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')
                    ->label('#')
                    ->sortable()
                    ->width(60),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable(['title'])
                    ->sortable()
                    ->formatStateUsing(function (Page $record): string {
                        $title = $record->getTranslation('title', app()->getLocale())
                            ?? $record->getTranslation('title', 'en')
                            ?? 'Untitled';

                        return Str::limit($title, 50);
                    })
                    ->weight(FontWeight::Medium),

                IconColumn::make('translation_status')
                    ->label('Translations')
                    ->getStateUsing(function (Page $record): bool {
                        $locales = ['en', 'nl', 'fr'];
                        $completed = 0;

                        foreach ($locales as $locale) {
                            if (! in_array($record->getTranslation('title', $locale), [null, '', '0'], true)) {
                                $completed++;
                            }
                        }

                        return $completed === count($locales);
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(function (Page $record): string {
                        $locales = ['en', 'nl', 'fr'];
                        $completed = [];
                        $missing = [];

                        foreach ($locales as $locale) {
                            if (! in_array($record->getTranslation('title', $locale), [null, '', '0'], true)) {
                                $completed[] = strtoupper($locale);
                            } else {
                                $missing[] = strtoupper($locale);
                            }
                        }

                        return 'Complete: ' . implode(', ', $completed) .
                               ($missing === [] ? '' : ' | Missing: ' . implode(', ', $missing));
                    }),

                TextColumn::make('slug')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->copyable()
                    ->copyMessage('Slug copied!')
                    ->formatStateUsing(fn (?string $state): string => '/' . ($state ?? ''))
                    ->color('gray'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'published' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->since()
                    ->tooltip(fn (Page $record): string => $record->updated_at?->format('F j, Y \\a\\t g:i A') ?? 'Never updated'),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->multiple(),

                TernaryFilter::make('translations_complete')
                    ->label('Translation Status')
                    ->queries(
                        true: function (Builder $query): void {
                            $locales = ['en', 'nl', 'fr'];

                            foreach ($locales as $locale) {
                                $query->whereNotNull("title->{$locale}");
                            }
                        },
                        false: function (Builder $query): void {
                            $locales = ['en', 'nl', 'fr'];
                            $query->where(function (Builder $subQuery) use ($locales): void {
                                foreach ($locales as $locale) {
                                    $subQuery->orWhereNull("title->{$locale}");
                                }
                            });
                        },
                    )
                    ->trueLabel('Complete translations')
                    ->falseLabel('Incomplete translations')
                    ->placeholder('All pages'),

                Filter::make('search_translations')
                    ->form([
                        TextInput::make('search')
                            ->placeholder('Search in all languages...'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['search'],
                        fn (Builder $query, $search): Builder => $query
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('content', 'like', "%{$search}%"),
                    )),
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
                BulkAction::make('publish')
                    ->label('Publish Selected')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->action(function (Collection $records): void {
                        /* @var Collection<int, Page> $records */
                        $records->each(function (Model $page, int|string $key): void {
                            /** @var Page $page */
                            $page->update(['status' => 'published']);
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Publish Selected Pages')
                    ->modalDescription('Are you sure you want to publish the selected pages? They will become visible to the public.')
                    ->modalSubmitActionLabel('Publish Pages')
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('draft')
                    ->label('Mark as Draft')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->action(function (Collection $records): void {
                        /* @var Collection<int, Page> $records */
                        $records->each(function (Model $page, int|string $key): void {
                            /** @var Page $page */
                            $page->update(['status' => 'draft']);
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Mark Selected Pages as Draft')
                    ->modalDescription('Are you sure you want to mark the selected pages as draft? They will no longer be visible to the public.')
                    ->modalSubmitActionLabel('Mark as Draft')
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make()
                    ->label('Delete Selected')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Selected Pages')
                    ->modalDescription('Are you sure you want to delete the selected pages? This action cannot be undone.')
                    ->modalSubmitActionLabel('Delete Pages')
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('order', 'asc')
            ->reorderable('order')
            ->striped()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
