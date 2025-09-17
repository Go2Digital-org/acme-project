<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Filament\Resources;

use DB;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Currency\Domain\Model\Currency;
use Modules\Donation\Domain\Model\PaymentGateway;
use Modules\Donation\Domain\Service\PaymentGatewayConfigRegistry;
use Modules\Donation\Infrastructure\Filament\Resources\PaymentGatewayResource\Pages\CreatePaymentGateway;
use Modules\Donation\Infrastructure\Filament\Resources\PaymentGatewayResource\Pages\EditPaymentGateway;
use Modules\Donation\Infrastructure\Filament\Resources\PaymentGatewayResource\Pages\ListPaymentGateways;
use Modules\Donation\Infrastructure\Filament\Resources\PaymentGatewayResource\Pages\ViewPaymentGateway;

class PaymentGatewayResource extends Resource
{
    protected static ?string $model = PaymentGateway::class;

    protected static ?string $navigationLabel = 'Payment Gateways';

    protected static ?string $modelLabel = 'Payment Gateway';

    protected static ?string $pluralModelLabel = 'Payment Gateways';

    protected static ?int $navigationSort = 5;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-credit-card';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'CSR Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Basic Information')
                    ->description('Gateway identification and provider configuration')
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Display name for this payment gateway'),
                        Select::make('provider')
                            ->options(PaymentGatewayConfigRegistry::getProviderOptions())
                            ->required()
                            ->live()
                            ->helperText('Payment service provider')
                            ->afterStateUpdated(function ($state, callable $set): void {
                                // Reset settings when provider changes
                                $set('settings', []);

                                // Provider changed - settings will be reset

                                // Auto-detect test mode if API key is set
                                $apiKey = $set('api_key');

                                if ($apiKey) {
                                    $testMode = PaymentGatewayConfigRegistry::detectTestMode($state, $apiKey);

                                    if ($testMode !== null) {
                                        $set('test_mode', $testMode);
                                    }
                                }
                            }),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Enable this gateway for processing payments'),
                        Toggle::make('test_mode')
                            ->label('Test Mode')
                            ->helperText('Enable test/sandbox mode for this gateway')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('API Credentials')
                    ->description('Secure authentication keys and secrets')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('api_key')
                            ->label(fn ($get): string => match ($get('provider')) {
                                'paypal' => 'Client Secret',
                                default => 'API Key',
                            })
                            ->password()
                            ->revealable()
                            ->required()
                            ->helperText(fn ($get): string => match ($get('provider')) {
                                'mollie' => 'API key from your Mollie dashboard (test_ or live_)',
                                'stripe' => 'Secret API key from Stripe (sk_test_ or sk_live_)',
                                'paypal' => 'Client Secret from your PayPal app',
                                default => 'Secret API key from your payment provider',
                            })
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $get, callable $set): void {
                                if ($state) {
                                    $provider = $get('provider');
                                    $testMode = PaymentGatewayConfigRegistry::detectTestMode($provider, $state);

                                    if ($testMode !== null) {
                                        $set('test_mode', $testMode);
                                    }
                                }
                            }),
                        TextInput::make('webhook_secret')
                            ->label('Webhook Secret')
                            ->password()
                            ->revealable()
                            ->required(fn ($get): bool => PaymentGatewayConfigRegistry::requiresWebhookSecret($get('provider') ?? ''))
                            ->hidden(fn ($get): bool => ! PaymentGatewayConfigRegistry::requiresWebhookSecret($get('provider') ?? ''))
                            ->helperText('Secret for validating webhook signatures'),
                    ])
                    ->columns(2),

                Section::make('Provider Settings')
                    ->description('Provider-specific configuration and endpoints')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema(fn ($get): array => match ($get('provider')) {
                        'paypal' => [
                            TextInput::make('settings.client_id')
                                ->label('Client ID')
                                ->required()
                                ->helperText('Client ID from your PayPal app'),
                            KeyValue::make('settings')
                                ->label('Additional Settings')
                                ->keyLabel('Setting Key')
                                ->valueLabel('Setting Value')
                                ->helperText('Optional: webhook_id, brand_name')
                                ->addable()
                                ->deletable()
                                ->reorderable(false)
                                ->default([]),
                        ],
                        'stripe' => [
                            TextInput::make('settings.publishable_key')
                                ->label('Publishable Key')
                                ->helperText('Public key for client-side Stripe integration (pk_test_ or pk_live_)'),
                            KeyValue::make('settings')
                                ->label('Additional Settings')
                                ->keyLabel('Setting Key')
                                ->valueLabel('Setting Value')
                                ->helperText('Optional: webhook_url, statement_descriptor')
                                ->addable()
                                ->deletable()
                                ->reorderable(false)
                                ->default([]),
                        ],
                        'mollie' => [
                            KeyValue::make('settings')
                                ->label('Configuration Settings')
                                ->keyLabel('Setting Key')
                                ->valueLabel('Setting Value')
                                ->helperText('Optional: webhook_url, description_prefix')
                                ->addable()
                                ->deletable()
                                ->reorderable(false)
                                ->default([]),
                        ],
                        default => [
                            KeyValue::make('settings')
                                ->label('Configuration Settings')
                                ->keyLabel('Setting Key')
                                ->valueLabel('Setting Value')
                                ->helperText('Provider-specific settings')
                                ->addable()
                                ->deletable()
                                ->reorderable(false)
                                ->default([]),
                        ],
                    }),

                Section::make('Payment Configuration')
                    ->description('Currency support and transaction limits')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Select::make('currencies')
                            ->label('Supported Currencies')
                            ->relationship('currencies', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn () => Currency::active()
                                ->orderBy('sort_order')
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn ($currency): array => [
                                    $currency->id => $currency->getDisplayName() . ' (' . $currency->code . ')',
                                ])
                                ->toArray())
                            ->helperText('Select currencies this gateway can process'),
                        TextInput::make('min_amount')
                            ->label('Minimum Amount')
                            ->required()
                            ->numeric()
                            ->step('0.01')
                            ->minValue(0.01)
                            ->default(1.00)
                            ->helperText('Minimum transaction amount'),
                        TextInput::make('max_amount')
                            ->label('Maximum Amount')
                            ->required()
                            ->numeric()
                            ->step('0.01')
                            ->minValue(0.01)
                            ->default(10000.00)
                            ->helperText('Maximum transaction amount'),
                        TextInput::make('priority')
                            ->label('Priority')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Higher priority gateways are used first (0 = lowest)'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                BadgeColumn::make('provider')
                    ->colors([
                        'primary' => PaymentGateway::PROVIDER_STRIPE,
                        'success' => PaymentGateway::PROVIDER_MOLLIE,
                    ])
                    ->icons([
                        'heroicon-o-credit-card' => PaymentGateway::PROVIDER_STRIPE,
                        'heroicon-o-banknotes' => PaymentGateway::PROVIDER_MOLLIE,
                    ]),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(
                        fn (PaymentGateway $record): string => $record->is_active ? 'Gateway is active' : 'Gateway is inactive',
                    ),
                IconColumn::make('test_mode')
                    ->label('Mode')
                    ->boolean()
                    ->trueIcon('heroicon-o-beaker')
                    ->falseIcon('heroicon-o-shield-check')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(
                        fn (PaymentGateway $record): string => $record->test_mode ? 'Test/Sandbox mode' : 'Production mode',
                    ),
                BadgeColumn::make('currencies')
                    ->label('Currencies')
                    ->formatStateUsing(
                        fn (PaymentGateway $record): string => $record->currencies->isNotEmpty()
                            ? $record->currencies->take(3)->pluck('code')->join(', ') . ($record->currencies->count() > 3 ? '...' : '')
                            : 'N/A',
                    )
                    ->color('secondary')
                    ->tooltip(
                        fn (PaymentGateway $record): string => $record->currencies->isNotEmpty()
                            ? $record->currencies->pluck('code')->join(', ')
                            : 'No currencies configured',
                    ),
                TextColumn::make('min_amount')
                    ->label('Min')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('max_amount')
                    ->label('Max')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('priority')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 10 => 'success',
                        $state >= 5 => 'warning',
                        default => 'gray',
                    })
                    ->tooltip('Higher priority = used first'),
                BadgeColumn::make('configuration_status')
                    ->label('Config')
                    ->getStateUsing(
                        fn (PaymentGateway $record): string => $record->isConfigured() ? 'configured' : 'incomplete',
                    )
                    ->colors([
                        'success' => 'configured',
                        'danger' => 'incomplete',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'configured',
                        'heroicon-o-exclamation-triangle' => 'incomplete',
                    ]),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('provider')
                    ->options(PaymentGatewayConfigRegistry::getProviderOptions()),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All gateways')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                TernaryFilter::make('test_mode')
                    ->label('Mode')
                    ->placeholder('All modes')
                    ->trueLabel('Test mode')
                    ->falseLabel('Production mode'),
                SelectFilter::make('configuration_status')
                    ->label('Configuration')
                    ->options([
                        'configured' => 'Fully configured',
                        'incomplete' => 'Incomplete',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'configured') {
                            /** @var Builder<PaymentGateway> $query */
                            return $query->configured();
                        }

                        if ($data['value'] === 'incomplete') {
                            return $query->whereNotIn(
                                'id',
                                PaymentGateway::query()->configured()->pluck('id'),
                            );
                        }

                        return $query;
                    }),
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
                        /** @var Collection<int, PaymentGateway> $records */
                        $records->each(function (PaymentGateway $gateway): void {
                            $gateway->activate();
                        });
                    })
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('deactivate')
                    ->label('Deactivate Selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->action(function (Collection $records): void {
                        /** @var Collection<int, PaymentGateway> $records */
                        $records->each(function (PaymentGateway $gateway): void {
                            $gateway->deactivate();
                        });
                    })
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('priority', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            // Relations can be added here if needed (e.g., payments, transactions)
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentGateways::route('/'),
            'create' => CreatePaymentGateway::route('/create'),
            'edit' => EditPaymentGateway::route('/{record}/edit'),
            'view' => ViewPaymentGateway::route('/{record}'),
        ];
    }

    /**
     * @return Builder<PaymentGateway>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<PaymentGateway> $query */
        $query = parent::getEloquentQuery();

        return $query
            ->orderBy('priority', 'desc')
            ->orderBy('name');
    }

    public static function getNavigationBadge(): ?string
    {
        $activeCount = DB::table('payment_gateways')->where('is_active', true)->count();

        return $activeCount > 0 ? (string) $activeCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
