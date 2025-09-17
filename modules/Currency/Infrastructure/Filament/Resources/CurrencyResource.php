<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Filament\Resources;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Currency\Domain\Model\Currency;
use Modules\Currency\Infrastructure\Filament\Resources\CurrencyResource\Pages\CreateCurrency;
use Modules\Currency\Infrastructure\Filament\Resources\CurrencyResource\Pages\EditCurrency;
use Modules\Currency\Infrastructure\Filament\Resources\CurrencyResource\Pages\ListCurrencies;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static ?int $navigationSort = 10;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Currency Information')
                    ->description('Basic currency details')
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        TextInput::make('code')
                            ->label('Currency Code')
                            ->required()
                            ->maxLength(3)
                            ->disabled(fn ($record): bool => $record !== null),
                        TextInput::make('name')
                            ->label('Currency Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('symbol')
                            ->label('Symbol')
                            ->required()
                            ->maxLength(10),
                        TextInput::make('flag')
                            ->label('Flag Emoji')
                            ->maxLength(10)
                            ->helperText('Use country flag emoji'),
                    ])
                    ->columns(2),

                Section::make('Formatting')
                    ->description('Number formatting settings')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('decimal_places')
                            ->label('Decimal Places')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(4)
                            ->default(2),
                        TextInput::make('decimal_separator')
                            ->label('Decimal Separator')
                            ->maxLength(1)
                            ->default('.')
                            ->helperText('e.g., . or ,'),
                        TextInput::make('thousands_separator')
                            ->label('Thousands Separator')
                            ->maxLength(1)
                            ->default(',')
                            ->helperText('e.g., , or . or \''),
                        Select::make('symbol_position')
                            ->label('Symbol Position')
                            ->options([
                                'before' => 'Before amount ($100)',
                                'after' => 'After amount (100€)',
                            ])
                            ->default('before'),
                    ])
                    ->columns(2),

                Section::make('Settings')
                    ->description('Currency status and exchange rate')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Checkbox::make('is_active')
                            ->label('Active')
                            ->helperText('Show this currency to users')
                            ->default(true),
                        Checkbox::make('is_default')
                            ->label('Default Currency')
                            ->helperText('Use as the default currency')
                            ->disabled(fn ($record): bool => $record && $record->is_default),
                        TextInput::make('exchange_rate')
                            ->label('Exchange Rate (to EUR)')
                            ->numeric()
                            ->step(0.000001)
                            ->minValue(0.000001)
                            ->default(1)
                            ->helperText('Rate relative to EUR (base currency)'),
                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('flag')
                    ->label('Flag')
                    ->alignCenter(),
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('symbol')
                    ->label('Symbol')
                    ->alignCenter(),
                BadgeColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn ($state): string => $state ? 'Active' : 'Inactive')
                    ->colors([
                        'success' => fn ($state): bool => $state,
                        'danger' => fn ($state): bool => ! $state,
                    ]),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->alignCenter(),
                TextColumn::make('exchange_rate')
                    ->label('Exchange Rate')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 6))
                    ->alignCenter(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter(),
                BadgeColumn::make('paymentGateways')
                    ->label('Payment Gateways')
                    ->formatStateUsing(
                        function (Currency $record): string {
                            $gateways = $record->paymentGateways;

                            if ($gateways->count() === 0) {
                                return 'None';
                            }

                            $gatewayNames = $gateways->take(2)->pluck('name')->toArray();
                            $result = implode(', ', $gatewayNames);

                            return $gateways->count() > 2 ? $result . '...' : $result;
                        },
                    )
                    ->color(fn (Currency $record): string => $record->paymentGateways->count() > 0 ? 'success' : 'gray')
                    ->tooltip(
                        function (Currency $record): string {
                            $gateways = $record->paymentGateways;

                            if ($gateways->count() === 0) {
                                return 'No payment gateways support this currency';
                            }

                            $gatewayNames = $gateways->pluck('name')->toArray();

                            return 'Supported by: ' . implode(', ', $gatewayNames);
                        },
                    ),
            ])
            ->filters([
                TrashedFilter::make(),
                TernaryFilter::make('is_active')
                    ->label('Active'),
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
                        /* @var Collection<int, Currency> $records */
                        $records->each(function (Model $currency, int|string $key): void {
                            $currency->update(['is_active' => true]);
                        });
                    })
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('deactivate')
                    ->label('Deactivate Selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->action(function (Collection $records): void {
                        /* @var Collection<int, Currency> $records */
                        $records->each(function (Model $currency, int|string $key): void {
                            /** @var Currency $currency */
                            // Prevent deactivating the default currency
                            if (! $currency->is_default) {
                                $currency->update(['is_active' => false]);
                            }
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Deactivate Selected Currencies')
                    ->modalDescription('Note: The default currency cannot be deactivated.')
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('update_exchange_rates')
                    ->label('Update Exchange Rates')
                    ->icon('heroicon-o-currency-euro')
                    ->color('warning')
                    ->form([
                        TextInput::make('exchange_rate')
                            ->label('New Exchange Rate (to EUR)')
                            ->numeric()
                            ->step(0.000001)
                            ->minValue(0.000001)
                            ->required()
                            ->helperText('Rate relative to EUR (base currency)')
                            ->placeholder('e.g., 1.095 for USD to EUR'),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        /** @var Collection<int, Currency> $records */
                        $newRate = (float) $data['exchange_rate'];

                        $records->each(function (Currency $currency, int|string $key) use ($newRate): void {
                            $currency->update(['exchange_rate' => $newRate]);
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Update Exchange Rates')
                    ->modalDescription('This will update the exchange rate for all selected currencies.')
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCurrencies::route('/'),
            'create' => CreateCurrency::route('/create'),
            'edit' => EditCurrency::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Currency';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Currencies';
    }
}
