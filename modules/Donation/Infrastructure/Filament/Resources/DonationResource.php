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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Audit\Infrastructure\Filament\RelationManagers\AuditsRelationManager;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Infrastructure\Filament\Resources\DonationResource\Pages\CreateDonation;
use Modules\Donation\Infrastructure\Filament\Resources\DonationResource\Pages\EditDonation;
use Modules\Donation\Infrastructure\Filament\Resources\DonationResource\Pages\ListDonations;
use Modules\Donation\Infrastructure\Filament\Resources\DonationResource\Pages\ViewDonation;
use Modules\Shared\Domain\ValueObject\DonationStatus;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class DonationResource extends Resource
{
    protected static ?string $model = Donation::class;

    protected static ?string $navigationLabel = 'Donations';

    protected static ?string $modelLabel = 'Donation';

    protected static ?string $pluralModelLabel = 'Donations';

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-heart';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'CSR Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Donation Information')
                    ->description('Basic donation details and transaction info')
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        Select::make('campaign_id')
                            ->relationship('campaign', 'id')
                            ->getOptionLabelFromRecordUsing(function ($record): string {
                                $title = $record->getTitle();

                                return is_array($title) ? ($title['en'] ?? array_values($title)[0] ?? 'Untitled') : (string) $title;
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Campaign receiving this donation'),
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('User making the donation'),
                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('€')
                            ->minValue(1)
                            ->maxValue(100000)
                            ->helperText('Donation amount in euros'),
                        Select::make('currency')
                            ->options([
                                'EUR' => 'Euro (€)',
                                'USD' => 'US Dollar ($)',
                                'GBP' => 'British Pound (£)',
                            ])
                            ->default('EUR')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Payment Details')
                    ->description('Payment method and gateway information')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Select::make('payment_method')
                            ->options([
                                'credit_card' => 'Credit Card',
                                'bank_transfer' => 'Bank Transfer',
                                'paypal' => 'PayPal',
                                'stripe' => 'Stripe',
                                'mollie' => 'Mollie',
                            ])
                            ->required(),
                        Select::make('payment_gateway')
                            ->options([
                                'stripe' => 'Stripe',
                                'paypal' => 'PayPal',
                                'mollie' => 'Mollie',
                                'manual' => 'Manual Entry',
                            ])
                            ->required(),
                        TextInput::make('transaction_id')
                            ->maxLength(255)
                            ->helperText('External payment gateway transaction ID'),
                        TextInput::make('gateway_response_id')
                            ->maxLength(255)
                            ->helperText('Payment gateway response/reference ID'),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                            ])
                            ->default('pending')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Additional Settings')
                    ->description('Privacy and recurring donation settings')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Toggle::make('anonymous')
                            ->label('Anonymous Donation')
                            ->helperText('Hide donor name from public displays'),
                        Toggle::make('recurring')
                            ->label('Recurring Donation')
                            ->live()
                            ->helperText('Set up automatic recurring donations'),
                        Select::make('recurring_frequency')
                            ->options([
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'yearly' => 'Yearly',
                            ])
                            ->visible(fn ($get): bool => (bool) $get('recurring'))
                            ->helperText('How often to repeat the donation'),
                        Translate::make()
                            ->schema([
                                Textarea::make('notes')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->helperText('Optional message from the donor'),
                            ])
                            ->locales(['en', 'nl', 'fr'])
                            ->prefixLocaleLabel()
                            ->columnSpan(2),
                    ])
                    ->columns(2),

                Section::make('Timestamps')
                    ->description('Important dates and processing timeline')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        DateTimePicker::make('donated_at')
                            ->default(now())
                            ->helperText('When the donation was initiated'),
                        DateTimePicker::make('processed_at')
                            ->helperText('When payment processing began'),
                        DateTimePicker::make('completed_at')
                            ->helperText('When the donation was successfully completed'),
                        DateTimePicker::make('cancelled_at')
                            ->helperText('When the donation was cancelled'),
                        DateTimePicker::make('refunded_at')
                            ->helperText('When the donation was refunded'),
                        Textarea::make('failure_reason')
                            ->rows(6)
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'font-mono text-xs'])
                            ->helperText('Detailed failure information including error message, code, and stack trace'),
                        Textarea::make('refund_reason')
                            ->rows(2)
                            ->helperText('Reason for refund if donation was refunded'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->getStateUsing(function (Donation $record): string {
                        $campaign = $record->campaign;
                        if (! $campaign) {
                            return 'No Campaign';
                        }
                        $title = $campaign->title;
                        if (is_array($title)) {
                            return $title['en'] ?? array_values($title)[0] ?? 'Untitled';
                        }

                        return (string) $title;
                    })
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(30),
                TextColumn::make('user.name')
                    ->label('Donor')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(
                        fn (Donation $record): string => $record->anonymous ? 'Anonymous' : ($record->user->name ?? 'Unknown'),
                    ),
                TextColumn::make('amount')
                    ->money('EUR')
                    ->sortable()
                    ->weight('medium'),
                BadgeColumn::make('payment_method')
                    ->label('Method')
                    ->colors([
                        'primary' => ['credit_card', 'stripe'],
                        'success' => ['paypal'],
                        'warning' => ['bank_transfer'],
                        'secondary' => ['mollie'],
                    ]),
                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'processing',
                        'success' => 'completed',
                        'danger' => ['failed', 'cancelled'],
                        'gray' => 'refunded',
                    ]),
                IconColumn::make('anonymous')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye')
                    ->trueColor('secondary')
                    ->falseColor('primary')
                    ->tooltip(
                        fn (Donation $record): string => $record->anonymous ? 'Anonymous donation' : 'Public donation',
                    ),
                IconColumn::make('recurring')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(
                        fn (Donation $record): string => $record->recurring ? 'Recurring donation' : 'One-time donation',
                    ),
                TextColumn::make('donated_at')
                    ->label('Date')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                TextColumn::make('processed_at')
                    ->label('Processed')
                    ->dateTime('M j, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('transaction_id')
                    ->label('Transaction')
                    ->limit(15)
                    ->tooltip(fn (Donation $record): string => $record->transaction_id ?? '')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ])
                    ->multiple(),
                SelectFilter::make('payment_method')
                    ->options([
                        'credit_card' => 'Credit Card',
                        'bank_transfer' => 'Bank Transfer',
                        'paypal' => 'PayPal',
                        'stripe' => 'Stripe',
                        'mollie' => 'Mollie',
                    ])
                    ->multiple(),
                SelectFilter::make('campaign')
                    ->relationship('campaign', 'id', fn ($query) => $query)
                    ->getOptionLabelFromRecordUsing(function ($record): string {
                        $title = $record->getTitle();

                        return is_array($title) ? ($title['en'] ?? array_values($title)[0] ?? 'Untitled') : (string) $title;
                    })
                    ->searchable()
                    ->preload(),
                SelectFilter::make('anonymous')
                    ->options([
                        '1' => 'Anonymous',
                        '0' => 'Public',
                    ])
                    ->label('Privacy'),
                SelectFilter::make('recurring')
                    ->options([
                        '1' => 'Recurring',
                        '0' => 'One-time',
                    ])
                    ->label('Type'),
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
                BulkAction::make('mark_completed')
                    ->label('Mark as Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Collection $records): void {
                        /** @var Collection<int, Donation> $records */
                        $records->each(function (Donation $donation): void {
                            $donation->update([
                                'status' => 'completed',
                                'completed_at' => now(),
                            ]);
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Mark donations as completed')
                    ->modalDescription('Are you sure you want to mark the selected donations as completed?')
                    ->modalSubmitActionLabel('Mark as Completed')
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('mark_processing')
                    ->label('Mark as Processing')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->action(function (Collection $records): void {
                        /** @var Collection<int, Donation> $records */
                        $records->each(function (Donation $donation): void {
                            $donation->update([
                                'status' => 'processing',
                                'processed_at' => now(),
                            ]);
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Mark donations as processing')
                    ->modalDescription('Are you sure you want to mark the selected donations as processing?')
                    ->modalSubmitActionLabel('Mark as Processing')
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('mark_failed')
                    ->label('Mark as Failed')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('failure_reason')
                            ->label('Failure Reason')
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Please provide a reason for marking these donations as failed'),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        /** @var Collection<int, Donation> $records */
                        $records->each(function (Donation $donation) use ($data): void {
                            $donation->update([
                                'status' => 'failed',
                                'failure_reason' => $data['failure_reason'],
                            ]);
                        });
                    })
                    ->modalHeading('Mark donations as failed')
                    ->modalDescription('Please provide a reason for marking the selected donations as failed.')
                    ->modalSubmitActionLabel('Mark as Failed')
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('refund_selected')
                    ->label('Refund Selected')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->form([
                        Textarea::make('refund_reason')
                            ->label('Refund Reason')
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Please provide a reason for refunding these donations'),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        /** @var Collection<int, Donation> $records */
                        $records->each(function (Donation $donation) use ($data): void {
                            $donation->update([
                                'status' => 'refunded',
                                'refunded_at' => now(),
                                'refund_reason' => $data['refund_reason'],
                            ]);
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Refund selected donations')
                    ->modalDescription('Are you sure you want to refund the selected donations? This action cannot be undone.')
                    ->modalSubmitActionLabel('Process Refunds')
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('donated_at', 'desc')
            ->striped();
    }

    /**
     * @return array<int, string>
     */
    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListDonations::route('/'),
            'create' => CreateDonation::route('/create'),
            'edit' => EditDonation::route('/{record}/edit'),
            'view' => ViewDonation::route('/{record}'),
        ];
    }

    /**
     * @return Builder<Donation>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['campaign', 'user']);
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = DB::table('donations')->where('status', DonationStatus::PENDING)->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
